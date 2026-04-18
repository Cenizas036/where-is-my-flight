package wimf

import com.raquo.laminar.api.L._
import org.scalajs.dom
import wimf.components._
import wimf.services._

/**
 * WHERE IS MY FLIGHT — Scala.js Entry Point
 *
 * This is the main module that initializes all reactive UI components.
 * It reads configuration from window.WIMF_CONFIG (set by Laravel Blade)
 * and mounts components onto their respective DOM mount points.
 *
 * Components:
 *   - FlightBoard:     Live departure/arrival board (mounts on #flight-board-root)
 *   - PredictionCard:  Delay probability badges (mounts on .prediction-mount)
 *   - GateEditWidget:  Community gate editing form (mounts on #gate-edit-root)
 *   - DelayBadge:      Inline delay indicators
 *
 * The WebSocketClient manages a persistent connection and pushes
 * events through Airstream EventBus instances that the components observe.
 */
object Main {

  def main(args: Array[String]): Unit = {
    // Read server-provided config
    val config = readConfig()

    // Initialize WebSocket client (shared across all components)
    val wsClient = new WebSocketClient(config.wsEndpoint)

    // Mount FlightBoard if the mount point exists
    Option(dom.document.getElementById("flight-board-root")).foreach { el =>
      val airport = el.getAttribute("data-airport")
      val boardType = el.getAttribute("data-board-type")

      val board = new FlightBoard(
        airport = airport,
        boardType = boardType,
        wsClient = wsClient,
        apiClient = new ApiClient(config.apiBase, config.csrfToken)
      )

      // Replace server-rendered content with reactive component
      render(el, board.render())
    }

    // Mount GateEditWidget if present
    Option(dom.document.getElementById("gate-edit-root")).foreach { el =>
      val flightId = el.getAttribute("data-flight-id")

      val widget = new GateEditWidget(
        flightId = flightId,
        apiClient = new ApiClient(config.apiBase, config.csrfToken),
        wsClient = wsClient
      )

      render(el, widget.render())
    }

    // Mount PredictionCards on all prediction mount points
    dom.document.querySelectorAll(".prediction-mount").foreach { node =>
      val el = node.asInstanceOf[dom.html.Element]
      val flightId = el.getAttribute("data-flight-id")

      if (flightId != null && flightId.nonEmpty) {
        val card = new PredictionCard(
          flightId = flightId,
          wsClient = wsClient,
          apiClient = new ApiClient(config.apiBase, config.csrfToken)
        )

        render(el, card.render())
      }
    }

    // Connect WebSocket after all components are mounted
    wsClient.connect()

    dom.console.log("[WIMF] Scala.js frontend initialized")
  }

  /**
   * Read configuration from the window.WIMF_CONFIG object
   * set by the Laravel Blade template.
   */
  private def readConfig(): WIMFConfig = {
    val jsConfig = dom.window.asInstanceOf[js.Dynamic].WIMF_CONFIG

    WIMFConfig(
      wsEndpoint = jsConfig.wsEndpoint.asInstanceOf[String],
      csrfToken = jsConfig.csrfToken.asInstanceOf[String],
      apiBase = jsConfig.apiBase.asInstanceOf[String],
      boardRefreshRate = jsConfig.boardRefreshRate.asInstanceOf[Int],
      isAuthenticated = jsConfig.isAuthenticated.asInstanceOf[Boolean]
    )
  }
}

/**
 * Configuration passed from Laravel to Scala.js via window object.
 */
case class WIMFConfig(
  wsEndpoint: String,
  csrfToken: String,
  apiBase: String,
  boardRefreshRate: Int,
  isAuthenticated: Boolean
)

// Expose to window for Blade template integration
@scala.scalajs.js.annotation.JSExportTopLevel("WIMFFrontend")
object WIMFFrontend {
  import scala.scalajs.js
  import scala.scalajs.js.annotation._

  @JSExport
  val FlightBoard = new js.Object {
    def mount(el: dom.html.Element, config: js.Dynamic): Unit = {
      dom.console.log("[WIMF] FlightBoard.mount called from Blade template")
      // Component mounting handled by Main — this is a fallback entry point
    }
  }
}
