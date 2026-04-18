package wimf.components

import com.raquo.laminar.api.L._
import org.scalajs.dom
import wimf.services._
import wimf.models._
import io.circe.Json
import scala.scalajs.concurrent.JSExecutionContext.Implicits.queue

/**
 * FlightBoard — The core reactive component for the live departure/arrival board.
 *
 * This component:
 * 1. Starts with server-rendered flight data (from Blade template)
 * 2. Subscribes to WebSocket events for real-time delta updates
 * 3. Applies flight updates reactively — no full-page refresh
 * 4. Animates row additions, status changes, and gate updates
 * 5. Falls back to polling if WebSocket disconnects
 *
 * State Management:
 *   Uses Airstream Var[Map[String, FlightRow]] as the single source of truth.
 *   WebSocket events (from EventBus) map to state transformations.
 */
class FlightBoard(
  airport: String,
  boardType: String,
  wsClient: WebSocketClient,
  apiClient: ApiClient
) {

  // ── Reactive State ──

  /** All flights currently on the board, keyed by flight ID */
  private val flights: Var[Map[String, FlightRow]] = Var(Map.empty)

  /** Connection status indicator */
  private val isConnected: Var[Boolean] = Var(false)

  /** Last update timestamp */
  private val lastUpdate: Var[String] = Var("")

  /** Search/filter text */
  private val filterText: Var[String] = Var("")

  /**
   * Render the complete flight board as a Laminar element.
   */
  def render(): HtmlElement = {
    // Subscribe to WebSocket events
    val flightUpdateSub = wsClient.flightUpdates.events.foreach { event =>
      handleFlightUpdate(event)
    }

    val gateUpdateSub = wsClient.gateUpdates.events.foreach { event =>
      handleGateUpdate(event)
    }

    val predictionSub = wsClient.predictionUpdates.events.foreach { event =>
      handlePredictionUpdate(event)
    }

    val connectionSub = wsClient.connectionStatus.events.foreach {
      case ConnectionStatus.Connected    => isConnected.set(true)
      case ConnectionStatus.Disconnected => isConnected.set(false)
      case _                             => ()
    }

    // Load initial data
    loadInitialData()

    div(
      cls := "flight-board-reactive",

      // Connection status indicator
      div(
        cls := "flex items-center justify-between mb-4",

        // Live indicator
        div(
          cls := "flex items-center space-x-2",
          child <-- isConnected.signal.map { connected =>
            if (connected) {
              span(
                cls := "flex items-center space-x-1.5 text-xs text-green-400",
                span(cls := "w-2 h-2 rounded-full bg-green-400 animate-pulse"),
                "Live"
              )
            } else {
              span(
                cls := "flex items-center space-x-1.5 text-xs text-yellow-400",
                span(cls := "w-2 h-2 rounded-full bg-yellow-400"),
                "Reconnecting..."
              )
            }
          }
        ),

        // Filter input
        div(
          cls := "relative",
          input(
            cls := "px-3 py-1.5 rounded-lg bg-gray-900 border border-gray-700 text-white text-sm placeholder-gray-500 outline-none focus:border-blue-500 w-48",
            placeholder := "Filter flights...",
            onInput.mapToValue --> filterText.writer
          )
        )
      ),

      // Flight table
      div(
        cls := "rounded-2xl bg-gray-900/50 border border-gray-800/50 overflow-hidden shadow-2xl",

        // Header row
        div(
          cls := "grid grid-cols-12 gap-4 px-6 py-3 bg-gray-900 border-b border-gray-800 text-xs font-semibold text-gray-500 uppercase tracking-wider",
          div(cls := "col-span-2", "Flight"),
          div(cls := "col-span-3", if (boardType == "departures") "Destination" else "Origin"),
          div(cls := "col-span-2", "Scheduled"),
          div(cls := "col-span-1", "Gate"),
          div(cls := "col-span-2", "Status"),
          div(cls := "col-span-2", "Prediction")
        ),

        // Flight rows — reactive
        children <-- flights.signal
          .combineWith(filterText.signal)
          .map { case (flightMap, filter) =>
            val filtered = if (filter.isEmpty) flightMap.values.toSeq
            else flightMap.values.filter { f =>
              f.flightNumber.toLowerCase.contains(filter.toLowerCase) ||
              f.destination.toLowerCase.contains(filter.toLowerCase)
            }.toSeq

            filtered
              .sortBy(_.scheduledTime)
              .map(renderFlightRow)
          }
      ),

      // Subscriptions (lifecycle management)
      onMountCallback { _ =>
        dom.console.log(s"[FlightBoard] Mounted for $airport ($boardType)")
      },
    )
  }

  /**
   * Render a single flight row.
   */
  private def renderFlightRow(flight: FlightRow): HtmlElement = {
    div(
      cls := "grid grid-cols-12 gap-4 px-6 py-4 border-b border-gray-800/30 hover:bg-gray-800/30 transition-all duration-300",
      dataAttr("flight-id") := flight.id,

      // Flight number
      div(
        cls := "col-span-2",
        a(
          cls := "font-mono font-bold text-white hover:text-blue-400 transition-colors cursor-pointer",
          href := s"/flight/${flight.flightNumber}",
          flight.flightNumber
        ),
        p(cls := "text-xs text-gray-500 mt-0.5", flight.airline)
      ),

      // Destination
      div(
        cls := "col-span-3",
        p(cls := "font-medium text-gray-200", flight.destination),
        p(cls := "text-xs text-gray-500 font-mono", flight.destinationIata)
      ),

      // Time
      div(
        cls := "col-span-2",
        p(cls := "font-mono text-gray-200", flight.scheduledTime),
        flight.estimatedTime.filter(_ != flight.scheduledTime).map { est =>
          p(cls := "text-xs text-yellow-400 font-mono", s"Est: $est")
        }.getOrElse(emptyNode)
      ),

      // Gate
      div(
        cls := "col-span-1",
        flight.gate.map { gate =>
          span(
            cls := s"inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-800 font-mono font-bold text-sm ${if (flight.gateSource == "community") "text-blue-400 border border-blue-600/30" else "text-white"}",
            gate
          )
        }.getOrElse(span(cls := "text-gray-600 text-sm", "TBA"))
      ),

      // Status
      div(
        cls := "col-span-2",
        renderStatusBadge(flight.status, flight.delayMinutes)
      ),

      // Prediction
      div(
        cls := "col-span-2",
        flight.delayProbability.map { prob =>
          renderPredictionBadge(prob, flight.primaryCause)
        }.getOrElse(emptyNode)
      )
    )
  }

  /**
   * Render a color-coded status badge.
   */
  private def renderStatusBadge(status: String, delayMinutes: Option[Int]): HtmlElement = {
    val (colorClass, dotClass) = status match {
      case "scheduled"  => ("text-gray-400 bg-gray-800", "")
      case "boarding"   => ("text-purple-400 bg-purple-500/10 border border-purple-500/20", "bg-purple-400 animate-pulse")
      case "departed"   => ("text-green-400 bg-green-500/10", "")
      case "in_air"     => ("text-blue-400 bg-blue-500/10", "bg-blue-400 animate-pulse")
      case "landed"     => ("text-cyan-400 bg-cyan-500/10", "")
      case "arrived"    => ("text-green-400 bg-green-500/10", "")
      case "delayed"    => ("text-yellow-400 bg-yellow-500/10 border border-yellow-500/20", "bg-yellow-400")
      case "cancelled"  => ("text-red-400 bg-red-500/10 border border-red-500/20", "")
      case _            => ("text-gray-400 bg-gray-800", "")
    }

    div(
      span(
        cls := s"inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold $colorClass",
        if (dotClass.nonEmpty) span(cls := s"w-1.5 h-1.5 rounded-full $dotClass mr-1.5") else emptyNode,
        status.replace("_", " ").capitalize
      ),
      delayMinutes.filter(_ > 0).map { mins =>
        p(cls := "text-xs text-yellow-400 mt-1", s"+${mins}min")
      }.getOrElse(emptyNode)
    )
  }

  /**
   * Render a delay prediction mini-badge with progress bar.
   */
  private def renderPredictionBadge(probability: Double, cause: Option[String]): HtmlElement = {
    val pct = (probability * 100).toInt
    val colorClass = if (pct > 60) "bg-red-400" else if (pct > 30) "bg-yellow-400" else "bg-green-400"
    val textClass = if (pct > 60) "text-red-400" else if (pct > 30) "text-yellow-400" else "text-green-400"

    div(
      div(
        cls := "flex items-center space-x-2",
        div(
          cls := "w-12 h-1.5 rounded-full bg-gray-800 overflow-hidden",
          div(cls := s"h-full rounded-full $colorClass transition-all duration-500", width := s"$pct%")
        ),
        span(cls := s"text-xs font-mono $textClass", s"$pct%")
      ),
      cause.filter(_ != "none").map { c =>
        p(cls := "text-xs text-gray-500 mt-0.5 capitalize", c)
      }.getOrElse(emptyNode)
    )
  }

  // ── Event Handlers ──

  private def handleFlightUpdate(event: FlightUpdateEvent): Unit = {
    if (event.status == "removed") {
      flights.update(_ - event.flightId)
    } else {
      flights.update { current =>
        current.get(event.flightId) match {
          case Some(existing) =>
            val updated = existing.copy(
              status = if (event.status.nonEmpty) event.status else existing.status,
              gate = event.gate.orElse(existing.gate),
              delayMinutes = event.delayMinutes.orElse(existing.delayMinutes),
              estimatedTime = event.estimatedTime.orElse(existing.estimatedTime)
            )
            current + (event.flightId -> updated)
          case None =>
            current // Unknown flight, ignore
        }
      }
    }
    lastUpdate.set(new scala.scalajs.js.Date().toISOString())
  }

  private def handleGateUpdate(event: GateUpdateEvent): Unit = {
    flights.update { current =>
      current.get(event.flightId) match {
        case Some(existing) =>
          val updated = existing.copy(
            gate = Some(event.gateNumber),
            gateSource = "community"
          )
          current + (event.flightId -> updated)
        case None => current
      }
    }
  }

  private def handlePredictionUpdate(event: PredictionUpdateEvent): Unit = {
    flights.update { current =>
      current.get(event.flightId) match {
        case Some(existing) =>
          val updated = existing.copy(
            delayProbability = Some(event.delayProbability),
            primaryCause = Some(event.primaryCause)
          )
          current + (event.flightId -> updated)
        case None => current
      }
    }
  }

  /**
   * Load initial flight data via API (supplements server-rendered content).
   */
  private def loadInitialData(): Unit = {
    apiClient.getFlightBoard(airport).foreach { json =>
      val flightList = json.hcursor.downField("flights").as[List[Json]].getOrElse(Nil)

      val rows = flightList.flatMap { f =>
        for {
          id <- f.hcursor.get[String]("id").toOption
        } yield {
          val row = FlightRow(
            id = id,
            flightNumber = f.hcursor.get[String]("flight_number").getOrElse(""),
            airline = f.hcursor.get[String]("airline_name").getOrElse(""),
            destination = f.hcursor.get[String]("destination_name").getOrElse(""),
            destinationIata = f.hcursor.get[String]("destination_iata").getOrElse(""),
            scheduledTime = f.hcursor.get[String]("scheduled_time").getOrElse(""),
            estimatedTime = f.hcursor.get[String]("estimated_time").toOption,
            status = f.hcursor.get[String]("status").getOrElse("scheduled"),
            gate = f.hcursor.get[String]("gate").toOption,
            gateSource = f.hcursor.get[String]("gate_source").getOrElse("official"),
            delayMinutes = f.hcursor.get[Int]("delay_minutes").toOption,
            delayProbability = f.hcursor.get[Double]("delay_probability").toOption,
            primaryCause = f.hcursor.get[String]("primary_cause").toOption
          )
          id -> row
        }
      }.toMap

      flights.set(rows)
      dom.console.log(s"[FlightBoard] Loaded ${rows.size} flights for $airport")
    }
  }
}

/**
 * Internal state model for a single flight row.
 */
case class FlightRow(
  id: String,
  flightNumber: String,
  airline: String,
  destination: String,
  destinationIata: String,
  scheduledTime: String,
  estimatedTime: Option[String],
  status: String,
  gate: Option[String],
  gateSource: String = "official",
  delayMinutes: Option[Int] = None,
  delayProbability: Option[Double] = None,
  primaryCause: Option[String] = None
)
