package actors

import akka.actor._
import play.api.libs.json._
import services.RedisPubSubService

/**
 * WebSocketActor — Per-client actor that bridges Redis pub/sub → WebSocket.
 *
 * Each connected browser gets one of these actors. The actor:
 * 1. Subscribes to the relevant Redis channel on start
 * 2. Forwards all Redis messages to the client WebSocket as JSON
 * 3. Handles heartbeat pings to detect stale connections
 * 4. Cleans up Redis subscription on disconnect
 *
 * This is the key piece making the "live board" feel instant —
 * Kafka → Redis pub/sub → WebSocketActor → Browser, all within milliseconds.
 */
object WebSocketActor {
  def props(out: ActorRef, channel: String, identifier: String): Props =
    Props(new WebSocketActor(out, channel, identifier))

  // Messages
  case class RedisMessage(payload: String)
  case object Heartbeat
  case object Disconnect
}

class WebSocketActor(
  out: ActorRef,
  channel: String,
  identifier: String
) extends Actor with ActorLogging {

  import WebSocketActor._
  import scala.concurrent.duration._

  // Inject Redis service (in production, use Guice-assisted injection)
  private val redis = RedisPubSubService.getInstance()
  private var subscriptionId: Option[String] = None

  // Heartbeat scheduler
  private val heartbeatScheduler = context.system.scheduler.scheduleAtFixedRate(
    initialDelay = 30.seconds,
    interval = 30.seconds,
    receiver = self,
    message = Heartbeat
  )(context.dispatcher)

  override def preStart(): Unit = {
    log.info(s"WebSocket connected: channel=$channel, identifier=$identifier")

    // Subscribe to Redis channel and forward messages to this actor
    subscriptionId = Some(
      redis.subscribe(channel) { message =>
        self ! RedisMessage(message)
      }
    )

    // Send welcome message
    out ! Json.obj(
      "type"       -> "connected",
      "channel"    -> channel,
      "identifier" -> identifier,
      "timestamp"  -> java.time.Instant.now().toString
    )
  }

  override def postStop(): Unit = {
    log.info(s"WebSocket disconnected: channel=$channel")
    heartbeatScheduler.cancel()

    // Unsubscribe from Redis
    subscriptionId.foreach(id => redis.unsubscribe(id))
  }

  def receive: Receive = {
    // ── Incoming from Redis pub/sub ──
    case RedisMessage(payload) =>
      try {
        val json = Json.parse(payload)
        out ! json
      } catch {
        case e: Exception =>
          log.warning(s"Failed to parse Redis message: ${e.getMessage}")
      }

    // ── Incoming from client (Scala.js) ──
    case msg: JsValue =>
      val msgType = (msg \ "type").asOpt[String].getOrElse("unknown")
      
      msgType match {
        case "ping" =>
          out ! Json.obj("type" -> "pong", "timestamp" -> java.time.Instant.now().toString)
        
        case "subscribe_flight" =>
          // Client wants updates for a specific flight
          val flightId = (msg \ "flight_id").asOpt[String]
          flightId.foreach { fid =>
            val flightChannel = s"wimf:flights:updates:$fid"
            redis.subscribe(flightChannel) { message =>
              self ! RedisMessage(message)
            }
          }
        
        case "unsubscribe_flight" =>
          // Client no longer wants updates for this flight
          val flightId = (msg \ "flight_id").asOpt[String]
          flightId.foreach { fid =>
            log.info(s"Client unsubscribed from flight $fid")
          }
        
        case _ =>
          log.debug(s"Unknown message type from client: $msgType")
      }

    // ── Heartbeat ──
    case Heartbeat =>
      out ! Json.obj(
        "type"      -> "heartbeat",
        "timestamp" -> java.time.Instant.now().toString
      )

    // ── Disconnect ──
    case Disconnect =>
      context.stop(self)
  }
}
