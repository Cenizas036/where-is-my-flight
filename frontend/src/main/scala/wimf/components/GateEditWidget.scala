package wimf.components

import com.raquo.laminar.api.L._
import org.scalajs.dom
import wimf.services._
import io.circe.Json
import scala.scalajs.concurrent.JSExecutionContext.Implicits.queue

/**
 * GateEditWidget — Community gate contribution form.
 *
 * Allows authenticated passengers to submit or update gate information,
 * corroborate existing contributions (agree/dispute), and see the
 * current community consensus for a flight's gate.
 *
 * Architecture:
 *   User submits → AJAX POST via ApiClient → Laravel GateController
 *   → Trust scoring → Redis → WebSocket → All viewers see update live
 *
 * Trust System Integration:
 *   - Shows the user's trust level and contribution history
 *   - Displays confidence score for each community gate report
 *   - High-trust users see their edits go live immediately
 */
class GateEditWidget(
  flightId: String,
  apiClient: ApiClient,
  wsClient: WebSocketClient
) {

  // ── Reactive State ──
  private val gateInput: Var[String] = Var("")
  private val terminalInput: Var[String] = Var("")
  private val isSubmitting: Var[Boolean] = Var(false)
  private val submitResult: Var[Option[String]] = Var(None)
  private val submitError: Var[Option[String]] = Var(None)
  private val communityGates: Var[List[CommunityGateEntry]] = Var(Nil)
  private val officialGate: Var[Option[String]] = Var(None)
  private val isExpanded: Var[Boolean] = Var(false)

  /**
   * Render the complete gate edit widget.
   */
  def render(): HtmlElement = {
    // Load existing gate info on mount
    loadGateInfo()

    // Listen for live gate updates via WebSocket
    val gateUpdateSub = wsClient.gateUpdates.events.foreach { event =>
      if (event.flightId == flightId) {
        // Add or update the community gate entry
        communityGates.update { current =>
          val entry = CommunityGateEntry(
            gateNumber = event.gateNumber,
            terminal = event.terminal,
            confidence = event.confidence,
            contributor = event.contributor,
            isLive = true
          )
          entry :: current.filterNot(_.gateNumber == event.gateNumber)
        }
      }
    }

    div(
      cls := "gate-edit-widget glass-card rounded-2xl overflow-hidden",

      // ── Header ──
      div(
        cls := "flex items-center justify-between px-5 py-4 cursor-pointer hover:bg-gray-800/30 transition-colors",
        onClick --> { _ => isExpanded.update(!_) },

        div(
          cls := "flex items-center space-x-3",
          // Gate icon
          span(cls := "text-xl", "🚪"),
          div(
            p(cls := "text-sm font-semibold text-gray-200", "Gate Information"),
            child <-- officialGate.signal.map {
              case Some(gate) => span(cls := "text-xs text-gray-500", s"Official: $gate")
              case None       => span(cls := "text-xs text-gray-500", "No official gate assigned")
            }
          )
        ),

        // Toggle arrow
        child <-- isExpanded.signal.map { expanded =>
          span(
            cls := s"text-gray-500 transition-transform duration-200 ${if (expanded) "rotate-180" else ""}",
            "▼"
          )
        }
      ),

      // ── Collapsible Body ──
      child <-- isExpanded.signal.map { expanded =>
        if (!expanded) div() else {
          div(
            cls := "px-5 pb-5 space-y-4 fade-in-up",

            // ── Existing Community Gates ──
            child <-- communityGates.signal.map { gates =>
              if (gates.isEmpty) div()
              else div(
                cls := "space-y-2",
                p(cls := "text-xs font-semibold text-gray-400 uppercase tracking-wider", "Community Reports"),
                gates.map(renderCommunityGate): _*
              )
            },

            // ── Submission Form ──
            div(
              cls := "space-y-3 pt-2",
              p(cls := "text-xs font-semibold text-gray-400 uppercase tracking-wider", "Report Gate"),

              // Gate + Terminal input row
              div(
                cls := "flex items-center space-x-3",
                div(
                  cls := "flex-1",
                  label(cls := "text-xs text-gray-500 mb-1 block", "Gate"),
                  input(
                    cls := "w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-700 text-white font-mono text-center uppercase text-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20 outline-none transition-all wimf-input",
                    placeholder := "e.g. A12",
                    maxLength := 10,
                    onInput.mapToValue --> gateInput.writer
                  )
                ),
                div(
                  cls := "w-24",
                  label(cls := "text-xs text-gray-500 mb-1 block", "Terminal"),
                  input(
                    cls := "w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-700 text-white font-mono text-center uppercase focus:border-blue-500 outline-none transition-all wimf-input",
                    placeholder := "T1",
                    maxLength := 5,
                    onInput.mapToValue --> terminalInput.writer
                  )
                )
              ),

              // Submit button
              child <-- isSubmitting.signal.combineWith(gateInput.signal).map {
                case (submitting, gate) =>
                  button(
                    cls := s"w-full py-2.5 rounded-lg font-semibold text-sm transition-all ${
                      if (gate.trim.isEmpty || submitting)
                        "bg-gray-800 text-gray-500 cursor-not-allowed"
                      else
                        "bg-blue-600 text-white hover:bg-blue-500 shadow-lg shadow-blue-600/20 cursor-pointer"
                    }",
                    disabled := gate.trim.isEmpty || submitting,
                    if (submitting) "Submitting..." else "Submit Gate Update",
                    onClick --> { _ =>
                      if (gate.trim.nonEmpty && !submitting) submitGateUpdate()
                    }
                  )
              },

              // Result messages
              child <-- submitResult.signal.map {
                case Some(msg) =>
                  div(cls := "text-xs text-green-400 bg-green-500/10 rounded-lg px-3 py-2", msg)
                case None => div()
              },

              child <-- submitError.signal.map {
                case Some(msg) =>
                  div(cls := "text-xs text-red-400 bg-red-500/10 rounded-lg px-3 py-2", msg)
                case None => div()
              }
            )
          )
        }
      }
    )
  }

  /**
   * Render a single community gate entry with corroboration actions.
   */
  private def renderCommunityGate(entry: CommunityGateEntry): HtmlElement = {
    val pct = (entry.confidence * 100).toInt
    val colorClass = if (pct > 80) "text-green-400" else if (pct > 50) "text-yellow-400" else "text-gray-400"

    div(
      cls := "flex items-center justify-between rounded-lg bg-gray-900/50 border border-gray-800/50 px-4 py-3",

      div(
        cls := "flex items-center space-x-3",
        // Gate badge
        span(
          cls := "gate-badge gate-badge-community text-lg",
          entry.gateNumber
        ),
        div(
          entry.terminal.map(t => p(cls := "text-xs text-gray-500", s"Terminal $t")).getOrElse(emptyNode),
          p(cls := "text-xs text-gray-600", s"by ${entry.contributor}")
        )
      ),

      div(
        cls := "flex items-center space-x-3",
        // Confidence
        span(cls := s"text-xs font-mono $colorClass", s"$pct%"),

        // Corroborate buttons
        button(
          cls := "px-2 py-1 rounded text-xs bg-green-900/30 text-green-400 hover:bg-green-900/50 transition-colors",
          "✓ Agree",
          onClick --> { _ => corroborate(entry, agrees = true) }
        ),
        button(
          cls := "px-2 py-1 rounded text-xs bg-red-900/30 text-red-400 hover:bg-red-900/50 transition-colors",
          "✗ Dispute",
          onClick --> { _ => corroborate(entry, agrees = false) }
        )
      )
    )
  }

  // ── Actions ──

  private def loadGateInfo(): Unit = {
    apiClient.getGateInfo(flightId).foreach { json =>
      // Parse official gate
      val official = json.hcursor.downField("official").get[String]("gate").toOption
      officialGate.set(official)

      // Parse community gates
      json.hcursor.downField("community").as[List[Json]].toOption.foreach { gates =>
        val entries = gates.flatMap { g =>
          for {
            gate <- g.hcursor.get[String]("gate").toOption
          } yield CommunityGateEntry(
            gateNumber = gate,
            terminal = g.hcursor.get[String]("terminal").toOption,
            confidence = g.hcursor.get[Double]("confidence").getOrElse(0.5),
            contributor = g.hcursor.get[String]("contributor").getOrElse("Unknown"),
            isLive = true
          )
        }
        communityGates.set(entries)
      }
    }
  }

  private def submitGateUpdate(): Unit = {
    val gate = gateInput.now()
    val terminal = terminalInput.now()

    if (gate.trim.isEmpty) return

    isSubmitting.set(true)
    submitResult.set(None)
    submitError.set(None)

    val termOpt = if (terminal.trim.nonEmpty) Some(terminal.trim.toUpperCase) else None

    apiClient.submitGateUpdate(flightId, gate.trim.toUpperCase, termOpt).foreach { json =>
      isSubmitting.set(false)
      val isLive = json.hcursor.get[Boolean]("is_live").getOrElse(false)
      if (isLive) {
        submitResult.set(Some("✓ Gate update is live! Thank you."))
      } else {
        submitResult.set(Some("✓ Submitted — awaiting verification."))
      }
      // Clear inputs
      gateInput.set("")
      terminalInput.set("")
    }.recover { case e: Exception =>
      isSubmitting.set(false)
      submitError.set(Some(s"Failed: ${e.getMessage}"))
    }
  }

  private def corroborate(entry: CommunityGateEntry, agrees: Boolean): Unit = {
    // In production, would need the contribution ID
    // For now, post to the corroboration endpoint
    apiClient.post(s"/gates/flight/$flightId/corroborate", s"""{"agrees":$agrees}""").foreach { json =>
      // Refresh gate info
      loadGateInfo()
    }.recover { case e: Exception =>
      dom.console.error(s"[GateEdit] Corroboration failed: ${e.getMessage}")
    }
  }
}

/**
 * Internal model for a community gate entry.
 */
case class CommunityGateEntry(
  gateNumber: String,
  terminal: Option[String],
  confidence: Double,
  contributor: String,
  isLive: Boolean
)
