package wimf.models

/**
 * WHERE IS MY FLIGHT — Scala.js Frontend Models
 *
 * Shared domain models used across Scala.js components.
 * These correspond to the JSON shapes returned by the API
 * and the event payloads from WebSocket.
 */

/**
 * A flight as displayed on the board.
 */
case class FlightInfo(
  id: String,
  flightNumber: String,
  airline: String,
  airlineLogo: Option[String],
  departureIata: String,
  departureName: String,
  arrivalIata: String,
  arrivalName: String,
  scheduledDeparture: String,
  estimatedDeparture: Option[String],
  actualDeparture: Option[String],
  scheduledArrival: String,
  estimatedArrival: Option[String],
  status: String,
  gate: Option[String],
  terminal: Option[String],
  gateSource: String = "official",
  baggageClaim: Option[String],
  delayMinutes: Int = 0,
  delayReason: Option[String],
  aircraftType: Option[String]
)

/**
 * Prediction data for a flight.
 */
case class PredictionInfo(
  flightId: String,
  delayProbability: Double,
  estimatedDelayMin: Int,
  confidenceIntervalLow: Option[Int],
  confidenceIntervalHigh: Option[Int],
  primaryCause: String,
  secondaryCause: Option[String],
  modelVersion: String,
  weatherCondition: Option[String]
)

/**
 * Gate contribution from the community.
 */
case class GateInfo(
  flightId: String,
  gateNumber: String,
  terminal: Option[String],
  confidence: Double,
  contributor: String,
  trustLevel: Int,
  corroborationCount: Int,
  source: String = "community",
  createdAt: String
)

/**
 * User's watched flight.
 */
case class WatchedFlight(
  flightId: String,
  flightNumber: String,
  departureIata: String,
  arrivalIata: String,
  scheduledDeparture: String,
  status: String,
  notifyGateChange: Boolean,
  notifyDelay: Boolean,
  notifyStatus: Boolean
)

/**
 * Airport reference data.
 */
case class AirportInfo(
  iataCode: String,
  name: String,
  city: String,
  country: String,
  timezone: String,
  totalGates: Option[Int]
)

/**
 * Delay cause — the contributing factors to a predicted delay.
 */
sealed trait DelayCause
object DelayCause {
  case object Weather extends DelayCause
  case object ATC extends DelayCause
  case object AircraftRotation extends DelayCause
  case object Crew extends DelayCause
  case object Congestion extends DelayCause
  case object None extends DelayCause

  def fromString(s: String): DelayCause = s.toLowerCase match {
    case "weather"           => Weather
    case "atc"               => ATC
    case "aircraft_rotation" => AircraftRotation
    case "crew"              => Crew
    case "congestion"        => Congestion
    case _                   => None
  }
}

/**
 * Board display mode.
 */
sealed trait BoardType
object BoardType {
  case object Departures extends BoardType
  case object Arrivals extends BoardType
}
