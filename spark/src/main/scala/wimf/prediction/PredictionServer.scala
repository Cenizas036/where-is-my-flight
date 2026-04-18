package wimf.prediction

import io.lettuce.core.RedisClient
import io.circe.syntax._
import io.circe.generic.auto._
import java.time.Instant
import java.sql.{DriverManager, ResultSet}

/**
 * PredictionServer — Lightweight prediction service that runs between
 * full Spark batch jobs to serve on-demand prediction requests.
 *
 * Listens to Redis pub/sub channel `wimf:predictions:requests` and
 * computes approximate predictions using cached model coefficients
 * and simple heuristics.
 *
 * This bridges the gap between Spark batch runs (every 15-30 min)
 * and real-time prediction needs.
 */
object PredictionServer {

  case class PredictionResult(
    flightId: String,
    delayProbability: Double,
    estimatedDelayMin: Int,
    primaryCause: String,
    modelVersion: String,
    timestamp: String
  )

  def main(args: Array[String]): Unit = {
    println("═══════════════════════════════════════════")
    println(" WIMF — Prediction Server (Lightweight)")
    println("═══════════════════════════════════════════")

    val redisHost = sys.env.getOrElse("REDIS_HOST", "redis")
    val redisPort = sys.env.getOrElse("REDIS_PORT", "6379")
    val dbUrl = sys.env.getOrElse("DB_URL", "jdbc:postgresql://postgres:5432/wimf")
    val dbUser = sys.env.getOrElse("DB_USER", "wimf_user")
    val dbPass = sys.env.getOrElse("DB_PASS", "wimf_secret_change_me")

    val redisClient = RedisClient.create(s"redis://$redisHost:$redisPort")
    val pubConnection = redisClient.connect()
    val subConnection = redisClient.connectPubSub()

    val featureExtractor = new FeatureExtractor()

    // Subscribe to prediction requests
    subConnection.addListener(new io.lettuce.core.pubsub.RedisPubSubAdapter[String, String] {
      override def message(channel: String, message: String): Unit = {
        try {
          val parsed = io.circe.parser.parse(message).getOrElse(io.circe.Json.Null)
          val flightId = parsed.hcursor.get[String]("flight_id").getOrElse("")

          if (flightId.nonEmpty) {
            val prediction = computeLightweightPrediction(flightId, dbUrl, dbUser, dbPass, featureExtractor)

            // Cache the result
            val resultJson = prediction.asJson.noSpaces
            pubConnection.sync().setex(
              s"wimf:cache:flight:prediction:$flightId",
              300L,
              resultJson
            )

            // Publish to flight channel for live update
            val broadcast = s"""{"type":"prediction","data":$resultJson,"timestamp":"${Instant.now()}"}"""
            pubConnection.sync().publish(s"wimf:flights:updates:$flightId", broadcast)

            println(s"[Prediction] $flightId → ${prediction.delayProbability}% (${prediction.primaryCause})")
          }
        } catch {
          case e: Exception =>
            println(s"[Error] Processing prediction request: ${e.getMessage}")
        }
      }
    })

    subConnection.sync().subscribe("wimf:predictions:requests")
    println("Listening for prediction requests on wimf:predictions:requests")

    // Keep alive
    while (true) {
      Thread.sleep(1000)
    }
  }

  /**
   * Compute a lightweight prediction using heuristics and historical averages.
   * This is the fast path — used between full Spark batch runs.
   */
  private def computeLightweightPrediction(
    flightId: String,
    dbUrl: String,
    dbUser: String,
    dbPass: String,
    featureExtractor: FeatureExtractor
  ): PredictionResult = {
    var probability = 0.15  // base delay probability
    var estimatedDelay = 0
    var cause = "none"

    try {
      val conn = DriverManager.getConnection(dbUrl, dbUser, dbPass)

      // Get flight details
      val flightStmt = conn.prepareStatement(
        """SELECT f.flight_number, f.scheduled_departure, f.delay_minutes,
           dep.iata_code as dep_iata, arr.iata_code as arr_iata,
           al.iata_code as airline_iata
           FROM flights f
           JOIN airports dep ON f.departure_airport_id = dep.id
           JOIN airports arr ON f.arrival_airport_id = arr.id
           LEFT JOIN airlines al ON f.airline_id = al.id
           WHERE f.id = ?::uuid"""
      )
      flightStmt.setString(1, flightId)
      val flightRs = flightStmt.executeQuery()

      if (flightRs.next()) {
        val depIata = flightRs.getString("dep_iata")
        val arrIata = flightRs.getString("arr_iata")
        val airlineIata = Option(flightRs.getString("airline_iata")).getOrElse("XX")

        // Get historical delay rate for this route
        val histStmt = conn.prepareStatement(
          """SELECT
               AVG(delay_minutes) as avg_delay,
               COUNT(*) FILTER (WHERE delay_minutes > 15) * 1.0 / NULLIF(COUNT(*), 0) as delay_rate
             FROM historical_delays
             WHERE departure_iata = ? AND arrival_iata = ?
             AND flight_date > CURRENT_DATE - INTERVAL '90 days'"""
        )
        histStmt.setString(1, depIata)
        histStmt.setString(2, arrIata)
        val histRs = histStmt.executeQuery()

        if (histRs.next()) {
          val avgDelay = histRs.getDouble("avg_delay")
          val delayRate = histRs.getDouble("delay_rate")

          if (!histRs.wasNull()) {
            probability = Math.min(0.95, Math.max(0.05, delayRate))
            estimatedDelay = Math.max(0, avgDelay.toInt)
          }
        }

        // Determine cause
        val hourOfDay = java.time.ZonedDateTime.now().getHour
        cause = featureExtractor.determinePrimaryCause(
          weatherCondition = "clear",  // would fetch from weather API
          windSpeedKts = 10.0,
          visibilityMiles = 10.0,
          congestion = 0.3,
          hourOfDay = hourOfDay
        )

        histStmt.close()
      }

      flightStmt.close()
      conn.close()
    } catch {
      case e: Exception =>
        println(s"[Error] DB lookup for prediction: ${e.getMessage}")
    }

    PredictionResult(
      flightId = flightId,
      delayProbability = Math.round(probability * 10000.0) / 10000.0,
      estimatedDelayMin = estimatedDelay,
      primaryCause = cause,
      modelVersion = "1.0.0-lightweight",
      timestamp = Instant.now().toString
    )
  }
}
