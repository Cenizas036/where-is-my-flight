package controllers

import javax.inject._
import play.api.mvc._
import play.api.libs.json._
import services.FlightDataService
import models._
import scala.concurrent.{ExecutionContext, Future}

/**
 * FlightController — Handles flight board queries, search, and status.
 * 
 * This is the core API consumed by both the Laravel API gateway
 * and directly by Scala.js components via AJAX.
 */
@Singleton
class FlightController @Inject()(
  cc: ControllerComponents,
  flightService: FlightDataService
)(implicit ec: ExecutionContext) extends AbstractController(cc) {

  /**
   * Get the departure/arrival board for an airport.
   * Returns flights with predictions and community gate data merged.
   */
  def board(airportIata: String, boardType: Option[String]): Action[AnyContent] = Action.async {
    val bType = boardType.getOrElse("departures")
    
    for {
      flights     <- flightService.getFlightBoard(airportIata.toUpperCase, bType)
      predictions <- flightService.getPredictionsForFlights(flights.map(_.id))
      gateData    <- flightService.getCommunityGates(flights.map(_.id))
    } yield {
      val enrichedFlights = flights.map { flight =>
        val prediction = predictions.get(flight.id)
        val communityGate = gateData.get(flight.id)
        
        Json.obj(
          "id"                -> flight.id,
          "flight_number"     -> flight.flightNumber,
          "airline_name"      -> flight.airlineName,
          "airline_logo"      -> flight.airlineLogo,
          "departure_iata"    -> flight.departureIata,
          "departure_name"    -> flight.departureName,
          "arrival_iata"      -> flight.arrivalIata,
          "arrival_name"      -> flight.arrivalName,
          "destination_iata"  -> (if (bType == "departures") flight.arrivalIata else flight.departureIata),
          "destination_name"  -> (if (bType == "departures") flight.arrivalName else flight.departureName),
          "scheduled_time"    -> flight.scheduledTime,
          "estimated_time"    -> flight.estimatedTime,
          "status"            -> flight.status,
          "gate"              -> communityGate.map(_.gateNumber).orElse(flight.gate),
          "gate_source"       -> (if (communityGate.isDefined) "community" else "official"),
          "terminal"          -> flight.terminal,
          "delay_minutes"     -> flight.delayMinutes,
          "delay_reason"      -> flight.delayReason,
          "aircraft_type"     -> flight.aircraftType,
          "delay_probability" -> prediction.map(_.delayProbability),
          "estimated_delay"   -> prediction.map(_.estimatedDelayMin),
          "primary_cause"     -> prediction.map(_.primaryCause),
        )
      }

      Ok(Json.obj(
        "airport"    -> airportIata.toUpperCase,
        "board_type" -> bType,
        "flights"    -> enrichedFlights,
        "count"      -> enrichedFlights.size,
        "updated_at" -> java.time.Instant.now().toString
      ))
    }
  }

  /**
   * Search flights by number or route.
   */
  def search(q: String): Action[AnyContent] = Action.async {
    flightService.searchFlights(q).map { results =>
      Ok(Json.obj(
        "query"   -> q,
        "results" -> results.map(f => Json.toJson(f)),
        "count"   -> results.size
      ))
    }
  }

  /**
   * Get single flight details.
   */
  def show(flightId: String): Action[AnyContent] = Action.async {
    flightService.getFlightById(flightId).map {
      case Some(flight) => Ok(Json.toJson(flight))
      case None         => NotFound(Json.obj("error" -> "Flight not found"))
    }
  }

  /**
   * Get current status of a flight (lightweight, for polling fallback).
   */
  def status(flightId: String): Action[AnyContent] = Action.async {
    flightService.getFlightStatus(flightId).map {
      case Some(status) => Ok(Json.toJson(status))
      case None         => NotFound(Json.obj("error" -> "Flight not found"))
    }
  }

  /**
   * Get airport information.
   */
  def airportInfo(iata: String): Action[AnyContent] = Action.async {
    flightService.getAirportInfo(iata.toUpperCase).map {
      case Some(airport) => Ok(Json.toJson(airport))
      case None          => NotFound(Json.obj("error" -> "Airport not found"))
    }
  }
}
