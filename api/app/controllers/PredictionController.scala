package controllers

import javax.inject._
import play.api.mvc._
import play.api.libs.json._
import services.{FlightDataService, RedisPubSubService}
import models._
import scala.concurrent.{ExecutionContext, Future}

/**
 * PredictionController — Serves delay prediction data from the Spark ML pipeline.
 *
 * Predictions are pre-computed by the Spark job and stored in:
 * 1. PostgreSQL predictions table (persistent)
 * 2. Redis cache (fast access, 5-min TTL)
 *
 * This controller reads from cache first, falls back to DB,
 * and can trigger a refresh for on-demand re-prediction.
 */
@Singleton
class PredictionController @Inject()(
  cc: ControllerComponents,
  flightService: FlightDataService,
  redis: RedisPubSubService
)(implicit ec: ExecutionContext) extends AbstractController(cc) {

  /**
   * Get the latest delay prediction for a specific flight.
   * Returns: delay_probability, estimated_delay_min, cause, confidence interval.
   */
  def getForFlight(flightId: String): Action[AnyContent] = Action.async {
    // Try Redis cache first
    val cached = redis.getCachedFlightState(s"prediction:$flightId")

    cached match {
      case Some(data) =>
        Future.successful(Ok(Json.parse(data)))

      case None =>
        // Fetch from DB via service
        flightService.getPredictionsForFlights(Seq(flightId)).map { predictions =>
          predictions.get(flightId) match {
            case Some(pred) =>
              val json = Json.toJson(pred)
              // Cache for fast subsequent access
              redis.cacheFlightState(s"prediction:$flightId", json.toString, ttlSeconds = 300)
              Ok(json)

            case None =>
              // No prediction available yet — return default low-risk
              Ok(Json.obj(
                "flight_id"        -> flightId,
                "delay_probability" -> 0.0,
                "estimated_delay_min" -> 0,
                "primary_cause"    -> "none",
                "model_version"    -> "pending",
                "message"          -> "Prediction not yet available for this flight"
              ))
          }
        }
    }
  }

  /**
   * Trigger a prediction refresh for a flight.
   * This enqueues the flight for re-scoring on the next Spark batch,
   * or runs an immediate lightweight prediction if Spark isn't available.
   */
  def refresh(flightId: String): Action[AnyContent] = Action.async {
    // Invalidate the cache
    redis.cacheFlightState(s"prediction:$flightId", "", ttlSeconds = 1)

    // Publish a prediction request to Redis (Spark consumer picks it up)
    val request = Json.obj(
      "type"      -> "prediction_request",
      "flight_id" -> flightId,
      "timestamp" -> java.time.Instant.now().toString
    )
    redis.publish("wimf:predictions:requests", request.toString)

    Future.successful(Accepted(Json.obj(
      "status"    -> "queued",
      "flight_id" -> flightId,
      "message"   -> "Prediction refresh queued"
    )))
  }
}
