package services

import javax.inject._
import play.api.{Configuration, Logging}
import play.api.libs.json._
import scala.concurrent.{ExecutionContext, Future}

/**
 * FlightDataService — Aggregates flight data from multiple sources.
 * 
 * Data priority:
 * 1. Redis cache (freshest, sub-second)
 * 2. PostgreSQL (persistent, reliable)
 * 3. External API (AviationStack/FlightAware — rate-limited)
 */
@Singleton
class FlightDataService @Inject()(
  config: Configuration,
  redisService: RedisPubSubService
)(implicit ec: ExecutionContext) extends Logging {

  import models._

  /**
   * Get the flight board for an airport.
   * Checks Redis cache first, falls back to DB.
   */
  def getFlightBoard(airportIata: String, boardType: String = "departures"): Future[Seq[FlightBoardEntry]] = {
    // Try Redis cache first
    redisService.getCachedBoardData(airportIata) match {
      case Some(cached) =>
        Future.successful(Json.parse(cached).as[Seq[FlightBoardEntry]])
      case None =>
        // Fetch from DB and cache
        fetchBoardFromDb(airportIata, boardType).map { flights =>
          redisService.cacheBoardData(airportIata, Json.toJson(flights).toString)
          flights
        }
    }
  }

  /**
   * Get predictions for a list of flights.
   */
  def getPredictionsForFlights(flightIds: Seq[String]): Future[Map[String, PredictionResult]] = {
    Future {
      // In production, query the predictions table
      // For now, return cached/computed predictions
      flightIds.flatMap { id =>
        redisService.getCachedFlightState(s"prediction:$id").map { cached =>
          id -> Json.parse(cached).as[PredictionResult]
        }
      }.toMap
    }
  }

  /**
   * Get community gate data for a list of flights.
   */
  def getCommunityGates(flightIds: Seq[String]): Future[Map[String, CommunityGate]] = {
    Future {
      flightIds.flatMap { id =>
        redisService.getCachedFlightState(s"gate:$id").map { cached =>
          id -> Json.parse(cached).as[CommunityGate]
        }
      }.toMap
    }
  }

  /**
   * Search flights by number or route.
   */
  def searchFlights(query: String): Future[Seq[FlightBoardEntry]] = {
    Future {
      // In production, query PostgreSQL with ILIKE
      logger.info(s"Searching flights: $query")
      Seq.empty[FlightBoardEntry] // Placeholder — wire up Slick query
    }
  }

  /**
   * Get a single flight by ID.
   */
  def getFlightById(flightId: String): Future[Option[FlightBoardEntry]] = {
    Future {
      redisService.getCachedFlightState(flightId).map { cached =>
        Json.parse(cached).as[FlightBoardEntry]
      }
    }
  }

  /**
   * Get lightweight flight status.
   */
  def getFlightStatus(flightId: String): Future[Option[JsObject]] = {
    Future {
      redisService.getCachedFlightState(flightId).map { cached =>
        Json.parse(cached).as[JsObject]
      }
    }
  }

  /**
   * Get airport information.
   */
  def getAirportInfo(iata: String): Future[Option[JsObject]] = {
    Future {
      // In production, query airports table
      Some(Json.obj(
        "iata_code" -> iata,
        "name"      -> s"$iata Airport",
        "city"      -> "Unknown",
        "country"   -> "Unknown"
      ))
    }
  }

  /**
   * Fetch board data from PostgreSQL.
   */
  private def fetchBoardFromDb(airportIata: String, boardType: String): Future[Seq[FlightBoardEntry]] = {
    Future {
      // In production, this would be a Slick query against the v_live_departures view
      logger.info(s"Fetching board from DB: $airportIata ($boardType)")
      Seq.empty[FlightBoardEntry]
    }
  }
}
