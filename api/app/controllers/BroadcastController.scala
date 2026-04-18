package controllers

import javax.inject._
import play.api.mvc._
import play.api.libs.json._
import services.RedisPubSubService
import scala.concurrent.ExecutionContext

/**
 * BroadcastController — Receives push requests from the Laravel web layer
 * and broadcasts them through Redis pub/sub to all connected WebSocket clients.
 *
 * Flow: Laravel → HTTP POST → BroadcastController → Redis pub/sub → WebSocketActors → Browsers
 *
 * This is the bridge that lets Laravel-side actions (like approving a gate
 * contribution) trigger real-time updates on the live board.
 */
@Singleton
class BroadcastController @Inject()(
  cc: ControllerComponents,
  redis: RedisPubSubService
)(implicit ec: ExecutionContext) extends AbstractController(cc) {

  /**
   * Broadcast a gate update to all clients viewing the relevant airport board.
   * Called by Laravel after a gate contribution is approved or auto-approved.
   */
  def gateUpdate(): Action[JsValue] = Action(parse.json) { request =>
    val body = request.body
    val flightId = (body \ "flight_id").as[String]
    val airportIata = (body \ "airport_iata").asOpt[String]
    val gateNumber = (body \ "gate_number").as[String]
    val terminal = (body \ "terminal").asOpt[String]
    val confidence = (body \ "confidence").asOpt[Double].getOrElse(0.5)
    val contributor = (body \ "contributor").asOpt[String].getOrElse("Anonymous")

    val payload = Json.obj(
      "type" -> "gate_update",
      "data" -> Json.obj(
        "flight_id"   -> flightId,
        "gate_number" -> gateNumber,
        "terminal"    -> terminal,
        "confidence"  -> confidence,
        "contributor" -> contributor,
        "source"      -> "community"
      ),
      "timestamp" -> java.time.Instant.now().toString
    )

    // Broadcast to airport channel
    airportIata.foreach { iata =>
      redis.publish(s"wimf:flights:updates:$iata", payload.toString)
    }

    // Broadcast to flight-specific channel
    redis.publish(s"wimf:flights:updates:$flightId", payload.toString)

    // Update gate cache
    redis.cacheFlightState(s"gate:$flightId", Json.obj(
      "gate_number" -> gateNumber,
      "terminal"    -> terminal,
      "confidence"  -> confidence,
      "source"      -> "community"
    ).toString, ttlSeconds = 3600)

    Ok(Json.obj("broadcast" -> "sent", "flight_id" -> flightId))
  }

  /**
   * Broadcast a flight status update.
   * Called by the Kafka consumer or directly for manual updates.
   */
  def flightUpdate(): Action[JsValue] = Action(parse.json) { request =>
    val body = request.body
    val flightId = (body \ "flight_id").as[String]
    val airportIata = (body \ "airport_iata").asOpt[String]

    val payload = Json.obj(
      "type" -> "flight_update",
      "data" -> body,
      "timestamp" -> java.time.Instant.now().toString
    )

    airportIata.foreach { iata =>
      redis.publish(s"wimf:flights:updates:$iata", payload.toString)
    }

    redis.publish(s"wimf:flights:updates:$flightId", payload.toString)

    Ok(Json.obj("broadcast" -> "sent", "flight_id" -> flightId))
  }
}
