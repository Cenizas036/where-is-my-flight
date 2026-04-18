package controllers

import javax.inject._
import play.api.mvc._
import play.api.libs.json._
import services.{FlightDataService, TrustScoringService, RedisPubSubService}
import models._
import scala.concurrent.{ExecutionContext, Future}

/**
 * GateController (Scala/Play) — Handles gate-related API requests
 * proxied from Laravel or called directly for internal operations.
 *
 * Note: The primary gate CRUD is in the Laravel GateController.
 * This Play-side controller handles:
 * - Gate info retrieval (community + official merged)
 * - Internal gate broadcast triggers
 * - Gate contribution scoring adjustments
 */
@Singleton
class GateController @Inject()(
  cc: ControllerComponents,
  flightService: FlightDataService,
  trustService: TrustScoringService,
  redis: RedisPubSubService
)(implicit ec: ExecutionContext) extends AbstractController(cc) {

  /**
   * Get combined gate info (official + community) for a flight.
   */
  def gateInfo(flightId: String): Action[AnyContent] = Action.async {
    for {
      communityGates <- flightService.getCommunityGates(Seq(flightId))
      flightOpt      <- flightService.getFlightById(flightId)
    } yield {
      val official = flightOpt.map { f =>
        Json.obj(
          "gate"     -> f.gate,
          "terminal" -> f.terminal,
          "source"   -> "official"
        )
      }.getOrElse(Json.obj("source" -> "none"))

      val community = communityGates.get(flightId).map { cg =>
        Json.obj(
          "gate"              -> cg.gateNumber,
          "terminal"          -> cg.terminal,
          "confidence"        -> cg.confidenceScore,
          "contributor"       -> cg.contributorName,
          "corroborations"    -> cg.corroborationCount,
          "source"            -> "community",
          "created_at"        -> cg.createdAt
        )
      }

      Ok(Json.obj(
        "flight_id" -> flightId,
        "official"  -> official,
        "community" -> community
      ))
    }
  }

  /**
   * Submit a gate update (called internally by Laravel via HTTP).
   */
  def submit(): Action[JsValue] = Action(parse.json).async { request =>
    val body = request.body
    val flightId = (body \ "flight_id").as[String]
    val gateNumber = (body \ "gate_number").as[String]
    val terminal = (body \ "terminal").asOpt[String]
    val confidence = (body \ "confidence").asOpt[Double].getOrElse(0.5)

    // Cache the gate data
    val gateJson = Json.obj(
      "flight_id"   -> flightId,
      "gate_number" -> gateNumber,
      "terminal"    -> terminal,
      "confidence"  -> confidence,
      "source"      -> "community",
      "updated_at"  -> java.time.Instant.now().toString
    )

    redis.cacheFlightState(s"gate:$flightId", gateJson.toString, ttlSeconds = 3600)

    Future.successful(Created(Json.obj(
      "status"    -> "accepted",
      "flight_id" -> flightId,
      "gate"      -> gateNumber
    )))
  }

  /**
   * Handle a corroboration event (agree/dispute a gate contribution).
   */
  def corroborate(contributionId: String): Action[JsValue] = Action(parse.json).async { request =>
    val agrees = (request.body \ "agrees").asOpt[Boolean].getOrElse(true)
    val currentConfidence = (request.body \ "current_confidence").asOpt[Double].getOrElse(0.5)
    val agreeCount = (request.body \ "agree_count").asOpt[Int].getOrElse(0)
    val disputeCount = (request.body \ "dispute_count").asOpt[Int].getOrElse(0)

    val newAgrees = if (agrees) agreeCount + 1 else agreeCount
    val newDisputes = if (!agrees) disputeCount + 1 else disputeCount

    val newConfidence = trustService.recalculateConfidence(currentConfidence, newAgrees, newDisputes)
    val action = trustService.determineModerationAction(newConfidence)

    Future.successful(Ok(Json.obj(
      "contribution_id"  -> contributionId,
      "new_confidence"   -> newConfidence,
      "moderation_action" -> action.toString,
      "agrees"           -> newAgrees,
      "disputes"         -> newDisputes
    )))
  }
}
