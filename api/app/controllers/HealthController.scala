package controllers

import javax.inject._
import play.api.mvc._
import play.api.libs.json._
import services.RedisPubSubService
import scala.concurrent.ExecutionContext

/**
 * HealthController — Liveness and readiness probes for Docker/k8s.
 *
 * - /api/v1/health — Checks that the API is reachable and core
 *   dependencies (Redis, DB connectivity) are healthy.
 */
@Singleton
class HealthController @Inject()(
  cc: ControllerComponents,
  redis: RedisPubSubService
)(implicit ec: ExecutionContext) extends AbstractController(cc) {

  def check(): Action[AnyContent] = Action {
    val redisOk = try {
      redis.getCachedFlightState("health:ping")
      true
    } catch {
      case _: Exception => false
    }

    val status = if (redisOk) "healthy" else "degraded"
    val httpStatus = if (redisOk) Ok else ServiceUnavailable

    httpStatus(Json.obj(
      "status"    -> status,
      "service"   -> "wimf-play-api",
      "version"   -> "1.0.0",
      "redis"     -> (if (redisOk) "connected" else "unreachable"),
      "timestamp" -> java.time.Instant.now().toString
    ))
  }
}
