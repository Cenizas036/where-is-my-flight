package wimf.components

import com.raquo.laminar.api.L._
import org.scalajs.dom
import wimf.services._
import io.circe.Json
import scala.scalajs.concurrent.JSExecutionContext.Implicits.queue

/**
 * PredictionCard — Displays AI delay probability for a single flight.
 *
 * Visual elements:
 *   - Animated progress bar color-coded by risk level
 *   - Delay probability percentage
 *   - Estimated delay in minutes (if likely delayed)
 *   - Primary cause badge (weather, ATC, congestion, etc.)
 *   - Confidence interval range
 *
 * Updates in real-time via WebSocket prediction events from Spark.
 */
class PredictionCard(
  flightId: String,
  wsClient: WebSocketClient,
  apiClient: ApiClient
) {

  // ── Reactive State ──
  private val delayProbability: Var[Option[Double]] = Var(None)
  private val estimatedDelay: Var[Option[Int]] = Var(None)
  private val primaryCause: Var[Option[String]] = Var(None)
  private val modelVersion: Var[String] = Var("")
  private val isLoading: Var[Boolean] = Var(true)

  /**
   * Render the prediction card component.
   */
  def render(): HtmlElement = {
    // Load initial prediction data
    loadPrediction()

    // Subscribe to real-time prediction updates
    val predictionSub = wsClient.predictionUpdates.events.foreach { event =>
      if (event.flightId == flightId) {
        delayProbability.set(Some(event.delayProbability))
        estimatedDelay.set(Some(event.estimatedDelay))
        primaryCause.set(Some(event.primaryCause))
        isLoading.set(false)
      }
    }

    div(
      cls := "prediction-card",

      child <-- isLoading.signal.combineWith(delayProbability.signal).map {
        case (true, _) =>
          renderSkeleton()

        case (false, None) =>
          renderNoPrediction()

        case (false, Some(prob)) =>
          renderPrediction(prob)
      }
    )
  }

  /**
   * Render the full prediction display.
   */
  private def renderPrediction(probability: Double): HtmlElement = {
    val pct = (probability * 100).toInt
    val (bgClass, textClass, label) =
      if (pct > 60)      ("prediction-high", "text-red-400", "High Risk")
      else if (pct > 30)  ("prediction-mid", "text-yellow-400", "Moderate")
      else                ("prediction-low", "text-green-400", "Low Risk")

    div(
      cls := "space-y-2 scale-in",

      // Probability bar + percentage
      div(
        cls := "flex items-center space-x-3",

        // Bar
        div(
          cls := "prediction-bar flex-1",
          div(
            cls := s"prediction-fill $bgClass",
            width := s"$pct%",
            styleAttr := s"--prediction-width: $pct%"
          )
        ),

        // Percentage
        span(cls := s"text-sm font-mono font-bold $textClass min-w-[3rem] text-right", s"$pct%")
      ),

      // Details row
      div(
        cls := "flex items-center justify-between",

        // Risk label
        span(cls := s"text-xs font-semibold $textClass", label),

        // Estimated delay (if significant)
        child <-- estimatedDelay.signal.map {
          case Some(mins) if mins > 0 =>
            span(cls := s"text-xs $textClass font-mono",
              s"~${mins}min delay"
            )
          case _ => span()
        }
      ),

      // Cause badge
      child <-- primaryCause.signal.map {
        case Some(cause) if cause != "none" && cause.nonEmpty =>
          div(
            cls := "flex items-center space-x-1.5 mt-1",
            renderCauseIcon(cause),
            span(cls := "text-xs text-gray-500 capitalize", cause.replace("_", " "))
          )
        case _ => div()
      }
    )
  }

  /**
   * Render cause-specific icon.
   */
  private def renderCauseIcon(cause: String): HtmlElement = {
    val (icon, color) = cause.toLowerCase match {
      case "weather"           => ("⛈", "text-blue-400")
      case "atc"               => ("📡", "text-purple-400")
      case "congestion"        => ("🔴", "text-orange-400")
      case "aircraft_rotation" => ("✈", "text-yellow-400")
      case "crew"              => ("👨‍✈", "text-cyan-400")
      case _                   => ("ℹ", "text-gray-400")
    }
    span(cls := s"text-xs $color", icon)
  }

  /**
   * Render a loading skeleton.
   */
  private def renderSkeleton(): HtmlElement = {
    div(
      cls := "space-y-2",
      div(cls := "skeleton skeleton-text w-full", styleAttr := "height: 6px"),
      div(cls := "skeleton skeleton-text w-1/2", styleAttr := "height: 10px"),
    )
  }

  /**
   * Render when no prediction is available.
   */
  private def renderNoPrediction(): HtmlElement = {
    span(cls := "text-xs text-gray-600 italic", "No prediction")
  }

  /**
   * Load the initial prediction via AJAX.
   */
  private def loadPrediction(): Unit = {
    apiClient.getFlightPrediction(flightId).foreach { json =>
      val prob = json.hcursor.get[Double]("delay_probability").toOption
      val delay = json.hcursor.get[Int]("estimated_delay_min").toOption
      val cause = json.hcursor.get[String]("primary_cause").toOption
      val version = json.hcursor.get[String]("model_version").getOrElse("")

      delayProbability.set(prob)
      estimatedDelay.set(delay.filter(_ > 0))
      primaryCause.set(cause.filter(_ != "none"))
      modelVersion.set(version)
      isLoading.set(false)
    }.recover { case _: Exception =>
      isLoading.set(false)
      delayProbability.set(None)
    }
  }
}
