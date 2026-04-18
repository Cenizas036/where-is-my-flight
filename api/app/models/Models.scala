package models

import play.api.libs.json._
import play.api.libs.functional.syntax._

/**
 * WHERE IS MY FLIGHT — Scala Domain Models
 * 
 * These models are used across the Play API, Kafka consumers,
 * and WebSocket actors.
 */

/**
 * FlightBoardEntry — A flight as it appears on the departure/arrival board.
 * This is the primary DTO between the API and the frontend.
 */
case class FlightBoardEntry(
  id: String,
  flightNumber: String,
  airlineName: Option[String],
  airlineLogo: Option[String],
  departureIata: String,
  departureName: String,
  arrivalIata: String,
  arrivalName: String,
  scheduledTime: String,           // ISO-8601 datetime
  estimatedTime: Option[String],
  actualTime: Option[String],
  status: String,
  gate: Option[String],
  terminal: Option[String],
  baggageClaim: Option[String],
  delayMinutes: Int,
  delayReason: Option[String],
  aircraftType: Option[String]
)

object FlightBoardEntry {
  implicit val format: OFormat[FlightBoardEntry] = Json.format[FlightBoardEntry]
}

/**
 * PredictionResult — Output from the Spark delay prediction model.
 */
case class PredictionResult(
  flightId: String,
  delayProbability: Double,        // 0.0 to 1.0
  estimatedDelayMin: Int,
  confidenceIntervalLow: Option[Int],
  confidenceIntervalHigh: Option[Int],
  primaryCause: String,            // weather, atc, aircraft_rotation, crew, congestion
  secondaryCause: Option[String],
  modelVersion: String,
  weatherCondition: Option[String],
  windSpeedKts: Option[Double],
  visibilityMiles: Option[Double],
  createdAt: String
)

object PredictionResult {
  implicit val format: OFormat[PredictionResult] = Json.format[PredictionResult]
}

/**
 * CommunityGate — Gate info from community contributions.
 */
case class CommunityGate(
  flightId: String,
  gateNumber: String,
  terminal: Option[String],
  confidenceScore: Double,
  contributorName: String,
  contributorTrustLevel: Int,
  corroborationCount: Int,
  source: String = "community",
  createdAt: String
)

object CommunityGate {
  implicit val format: OFormat[CommunityGate] = Json.format[CommunityGate]
}

/**
 * FlightEvent — Raw event from Kafka (external API → Kafka → Play).
 */
case class FlightEvent(
  eventType: String,               // status_change, delay_update, gate_change, cancellation
  flightId: String,
  flightNumber: String,
  airportIata: String,
  previousStatus: Option[String],
  newStatus: String,
  delayMinutes: Option[Int],
  gate: Option[String],
  terminal: Option[String],
  reason: Option[String],
  timestamp: String
)

object FlightEvent {
  implicit val format: OFormat[FlightEvent] = Json.format[FlightEvent]
}

/**
 * GateUpdate — Community gate contribution event.
 */
case class GateUpdate(
  flightId: String,
  gateNumber: String,
  terminal: Option[String],
  confidence: Double,
  contributorName: String,
  source: String = "community",
  timestamp: String
)

object GateUpdate {
  implicit val format: OFormat[GateUpdate] = Json.format[GateUpdate]
}

/**
 * WeatherData — Weather conditions at an airport, used for predictions.
 */
case class WeatherData(
  airportIata: String,
  condition: String,               // clear, cloudy, rain, thunderstorm, snow, fog
  temperatureC: Double,
  windSpeedKts: Double,
  windDirection: Int,
  visibilityMiles: Double,
  ceilingFeet: Option[Int],
  precipitationMm: Double,
  humidity: Int,
  pressure: Double,
  timestamp: String
)

object WeatherData {
  implicit val format: OFormat[WeatherData] = Json.format[WeatherData]
}
