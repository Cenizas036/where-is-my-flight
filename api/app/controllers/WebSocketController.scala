package controllers

import javax.inject._
import play.api.mvc._
import play.api.libs.json._
import play.api.libs.streams.ActorFlow
import akka.actor.ActorSystem
import akka.stream.Materializer
import actors.WebSocketActor

/**
 * WebSocketController — Manages persistent WebSocket connections
 * for real-time flight board and individual flight tracking.
 *
 * Architecture:
 *   Browser ←→ WebSocket ←→ WebSocketActor ←→ Redis pub/sub ←→ Kafka
 *
 * Each connected client gets a WebSocketActor that subscribes to the
 * relevant Redis channel. When Kafka pushes a new flight event,
 * it gets published to Redis and all connected actors push the
 * delta update to their clients.
 */
@Singleton
class WebSocketController @Inject()(
  cc: ControllerComponents
)(implicit system: ActorSystem, mat: Materializer) extends AbstractController(cc) {

  /**
   * WebSocket for the live departure/arrival board.
   * Subscribes to: wimf:flights:updates:{airportIata}
   * 
   * Messages sent to client:
   * - { type: "flight_update", data: { ...flight } }
   * - { type: "gate_update",   data: { flight_id, gate, source } }
   * - { type: "prediction",    data: { flight_id, probability, cause } }
   * - { type: "remove",        data: { flight_id } }  // flight departed/cancelled
   */
  def boardStream(airportIata: String): WebSocket = WebSocket.accept[JsValue, JsValue] { request =>
    val channel = s"wimf:flights:updates:${airportIata.toUpperCase}"
    
    ActorFlow.actorRef { out =>
      WebSocketActor.props(out, channel, airportIata.toUpperCase)
    }
  }

  /**
   * WebSocket for a single flight's real-time updates.
   * Subscribes to: wimf:flights:updates:{flightId}
   * 
   * More granular — pushes status changes, gate updates,
   * prediction refreshes for just this one flight.
   */
  def flightStream(flightId: String): WebSocket = WebSocket.accept[JsValue, JsValue] { request =>
    val channel = s"wimf:flights:updates:{$flightId}"
    
    ActorFlow.actorRef { out =>
      WebSocketActor.props(out, channel, flightId)
    }
  }
}
