package wimf.services

import org.scalajs.dom
import com.raquo.airstream.core.EventStream
import com.raquo.airstream.eventbus.EventBus
import io.circe.parser._
import io.circe.Json
import scala.scalajs.js
import scala.scalajs.js.timers._

/**
 * WebSocketClient — Manages the persistent WebSocket connection
 * to the Play Framework backend.
 *
 * Architecture:
 *   Browser ←→ WebSocket ←→ Play WebSocketController ←→ Redis pub/sub ←→ Kafka
 *
 * This client:
 * 1. Maintains a persistent connection with auto-reconnect
 * 2. Distributes incoming events through typed EventBus streams
 * 3. Handles heartbeat ping/pong for connection health
 * 4. Provides a clean API for components to subscribe to specific event types
 *
 * Event Types (from server):
 *   - flight_update:  A flight's status, time, or gate changed
 *   - gate_update:    A community gate contribution was published
 *   - prediction:     New delay prediction from Spark
 *   - remove:         Flight departed/cancelled, remove from board
 *   - heartbeat:      Keep-alive ping from server
 *   - connected:      Initial connection acknowledgment
 */
class WebSocketClient(wsEndpoint: String) {

  // ── Event Buses (components subscribe to these) ──
  val flightUpdates: EventBus[FlightUpdateEvent] = new EventBus[FlightUpdateEvent]
  val gateUpdates: EventBus[GateUpdateEvent] = new EventBus[GateUpdateEvent]
  val predictionUpdates: EventBus[PredictionUpdateEvent] = new EventBus[PredictionUpdateEvent]
  val connectionStatus: EventBus[ConnectionStatus] = new EventBus[ConnectionStatus]
  val rawMessages: EventBus[Json] = new EventBus[Json]

  private var ws: Option[dom.WebSocket] = None
  private var reconnectAttempts = 0
  private val maxReconnectAttempts = 10
  private val baseReconnectDelay = 1000 // ms
  private var heartbeatTimer: Option[SetTimeoutHandle] = None

  /**
   * Open the WebSocket connection.
   */
  def connect(): Unit = {
    if (ws.exists(_.readyState == dom.WebSocket.OPEN)) {
      dom.console.log("[WS] Already connected")
      return
    }

    dom.console.log(s"[WS] Connecting to $wsEndpoint...")
    connectionStatus.writer.onNext(ConnectionStatus.Connecting)

    try {
      val socket = new dom.WebSocket(wsEndpoint)

      socket.onopen = { (_: dom.Event) =>
        dom.console.log("[WS] Connected")
        reconnectAttempts = 0
        connectionStatus.writer.onNext(ConnectionStatus.Connected)
        startHeartbeat()
      }

      socket.onmessage = { (event: dom.MessageEvent) =>
        handleMessage(event.data.toString)
      }

      socket.onclose = { (event: dom.CloseEvent) =>
        dom.console.log(s"[WS] Disconnected: code=${event.code}, reason=${event.reason}")
        connectionStatus.writer.onNext(ConnectionStatus.Disconnected)
        stopHeartbeat()
        scheduleReconnect()
      }

      socket.onerror = { (_: dom.Event) =>
        dom.console.error("[WS] Connection error")
        connectionStatus.writer.onNext(ConnectionStatus.Error)
      }

      ws = Some(socket)
    } catch {
      case e: Exception =>
        dom.console.error(s"[WS] Failed to connect: ${e.getMessage}")
        scheduleReconnect()
    }
  }

  /**
   * Send a message to the server.
   */
  def send(message: String): Unit = {
    ws match {
      case Some(socket) if socket.readyState == dom.WebSocket.OPEN =>
        socket.send(message)
      case _ =>
        dom.console.warn("[WS] Cannot send — not connected")
    }
  }

  /**
   * Subscribe to updates for a specific flight.
   */
  def subscribeToFlight(flightId: String): Unit = {
    send(s"""{"type":"subscribe_flight","flight_id":"$flightId"}""")
  }

  /**
   * Unsubscribe from a specific flight.
   */
  def unsubscribeFromFlight(flightId: String): Unit = {
    send(s"""{"type":"unsubscribe_flight","flight_id":"$flightId"}""")
  }

  /**
   * Close the connection.
   */
  def disconnect(): Unit = {
    stopHeartbeat()
    ws.foreach(_.close(1000, "Client closing"))
    ws = None
  }

  // ── Private ──

  /**
   * Handle a raw WebSocket message, parse it, and route to the
   * appropriate EventBus based on the message type.
   */
  private def handleMessage(raw: String): Unit = {
    parse(raw) match {
      case Right(json) =>
        rawMessages.writer.onNext(json)

        val msgType = json.hcursor.get[String]("type").getOrElse("unknown")

        msgType match {
          case "flight_update" =>
            json.hcursor.get[Json]("data").foreach { data =>
              val event = FlightUpdateEvent(
                flightId = data.hcursor.get[String]("flight_id").getOrElse(""),
                flightNumber = data.hcursor.get[String]("flight_number").getOrElse(""),
                status = data.hcursor.get[String]("status").getOrElse(""),
                gate = data.hcursor.get[String]("gate").toOption,
                delayMinutes = data.hcursor.get[Int]("delay_minutes").toOption,
                estimatedTime = data.hcursor.get[String]("estimated_time").toOption,
                raw = data
              )
              flightUpdates.writer.onNext(event)
            }

          case "gate_update" =>
            json.hcursor.get[Json]("data").foreach { data =>
              val event = GateUpdateEvent(
                flightId = data.hcursor.get[String]("flight_id").getOrElse(""),
                gateNumber = data.hcursor.get[String]("gate_number").getOrElse(""),
                terminal = data.hcursor.get[String]("terminal").toOption,
                confidence = data.hcursor.get[Double]("confidence").getOrElse(0.0),
                contributor = data.hcursor.get[String]("contributor").getOrElse(""),
                source = data.hcursor.get[String]("source").getOrElse("community")
              )
              gateUpdates.writer.onNext(event)
            }

          case "prediction" =>
            json.hcursor.get[Json]("data").foreach { data =>
              val event = PredictionUpdateEvent(
                flightId = data.hcursor.get[String]("flight_id").getOrElse(""),
                delayProbability = data.hcursor.get[Double]("delay_probability").getOrElse(0.0),
                estimatedDelay = data.hcursor.get[Int]("estimated_delay_min").getOrElse(0),
                primaryCause = data.hcursor.get[String]("primary_cause").getOrElse("unknown")
              )
              predictionUpdates.writer.onNext(event)
            }

          case "heartbeat" | "pong" | "connected" =>
            // Connection health — no action needed
            ()

          case "remove" =>
            // Flight departed/cancelled — emit as update with remove flag
            json.hcursor.get[Json]("data").foreach { data =>
              val event = FlightUpdateEvent(
                flightId = data.hcursor.get[String]("flight_id").getOrElse(""),
                flightNumber = "",
                status = "removed",
                gate = None,
                delayMinutes = None,
                estimatedTime = None,
                raw = data
              )
              flightUpdates.writer.onNext(event)
            }

          case other =>
            dom.console.log(s"[WS] Unknown message type: $other")
        }

      case Left(error) =>
        dom.console.error(s"[WS] JSON parse error: ${error.getMessage}")
    }
  }

  /**
   * Auto-reconnect with exponential backoff.
   */
  private def scheduleReconnect(): Unit = {
    if (reconnectAttempts < maxReconnectAttempts) {
      val delay = Math.min(baseReconnectDelay * Math.pow(2, reconnectAttempts).toInt, 30000)
      reconnectAttempts += 1

      dom.console.log(s"[WS] Reconnecting in ${delay}ms (attempt $reconnectAttempts/$maxReconnectAttempts)")

      setTimeout(delay.toDouble) {
        connect()
      }
    } else {
      dom.console.error("[WS] Max reconnect attempts reached")
      connectionStatus.writer.onNext(ConnectionStatus.Failed)
    }
  }

  /**
   * Send periodic ping to keep connection alive.
   */
  private def startHeartbeat(): Unit = {
    stopHeartbeat()
    def ping(): Unit = {
      send("""{"type":"ping"}""")
      heartbeatTimer = Some(setTimeout(25000.0)(ping()))
    }
    heartbeatTimer = Some(setTimeout(25000.0)(ping()))
  }

  private def stopHeartbeat(): Unit = {
    heartbeatTimer.foreach(clearTimeout)
    heartbeatTimer = None
  }
}

// ── Event Types ──

case class FlightUpdateEvent(
  flightId: String,
  flightNumber: String,
  status: String,
  gate: Option[String],
  delayMinutes: Option[Int],
  estimatedTime: Option[String],
  raw: Json
)

case class GateUpdateEvent(
  flightId: String,
  gateNumber: String,
  terminal: Option[String],
  confidence: Double,
  contributor: String,
  source: String
)

case class PredictionUpdateEvent(
  flightId: String,
  delayProbability: Double,
  estimatedDelay: Int,
  primaryCause: String
)

sealed trait ConnectionStatus
object ConnectionStatus {
  case object Connecting extends ConnectionStatus
  case object Connected extends ConnectionStatus
  case object Disconnected extends ConnectionStatus
  case object Error extends ConnectionStatus
  case object Failed extends ConnectionStatus
}
