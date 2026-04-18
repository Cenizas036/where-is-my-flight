package wimf.services

import org.scalajs.dom
import scala.concurrent.{Future, Promise}
import scala.scalajs.js
import scala.scalajs.js.JSON
import io.circe.parser._
import io.circe.Json

/**
 * ApiClient — HTTP client for AJAX requests from Scala.js to the Laravel API.
 *
 * Used when WebSocket isn't available (fallback polling) or for
 * one-off requests like gate submissions and flight search.
 */
class ApiClient(baseUrl: String, csrfToken: String) {

  import scala.scalajs.concurrent.JSExecutionContext.Implicits.queue

  /**
   * GET request returning parsed JSON.
   */
  def get(path: String): Future[Json] = {
    val promise = Promise[Json]()
    
    val xhr = new dom.XMLHttpRequest()
    xhr.open("GET", s"$baseUrl$path")
    xhr.setRequestHeader("Accept", "application/json")
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest")
    
    xhr.onload = { (_: dom.Event) =>
      if (xhr.status >= 200 && xhr.status < 300) {
        parse(xhr.responseText) match {
          case Right(json) => promise.success(json)
          case Left(err)   => promise.failure(new Exception(s"JSON parse error: ${err.getMessage}"))
        }
      } else {
        promise.failure(new Exception(s"HTTP ${xhr.status}: ${xhr.statusText}"))
      }
    }
    
    xhr.onerror = { (_: dom.Event) =>
      promise.failure(new Exception("Network error"))
    }
    
    xhr.send()
    promise.future
  }

  /**
   * POST request with JSON body.
   */
  def post(path: String, body: String): Future[Json] = {
    val promise = Promise[Json]()
    
    val xhr = new dom.XMLHttpRequest()
    xhr.open("POST", s"$baseUrl$path")
    xhr.setRequestHeader("Content-Type", "application/json")
    xhr.setRequestHeader("Accept", "application/json")
    xhr.setRequestHeader("X-CSRF-TOKEN", csrfToken)
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest")
    
    xhr.onload = { (_: dom.Event) =>
      if (xhr.status >= 200 && xhr.status < 300) {
        parse(xhr.responseText) match {
          case Right(json) => promise.success(json)
          case Left(err)   => promise.failure(new Exception(s"JSON parse error: ${err.getMessage}"))
        }
      } else {
        promise.failure(new Exception(s"HTTP ${xhr.status}: ${xhr.statusText}"))
      }
    }
    
    xhr.onerror = { (_: dom.Event) =>
      promise.failure(new Exception("Network error"))
    }
    
    xhr.send(body)
    promise.future
  }

  // ── Convenience Methods ──

  def getFlightBoard(airportIata: String): Future[Json] =
    get(s"/flights/board/$airportIata")

  def getFlightStatus(flightId: String): Future[Json] =
    get(s"/flights/$flightId/status")

  def getFlightPrediction(flightId: String): Future[Json] =
    get(s"/flights/$flightId/prediction")

  def getGateInfo(flightId: String): Future[Json] =
    get(s"/gates/flight/$flightId")

  def submitGateUpdate(flightId: String, gate: String, terminal: Option[String]): Future[Json] = {
    val body = s"""{"flight_id":"$flightId","gate_number":"$gate"${terminal.map(t => s""","terminal":"$t"""").getOrElse("")}}"""
    post("/gates/submit", body)
  }

  def corroborateGate(contributionId: String, agrees: Boolean): Future[Json] =
    post(s"/gates/$contributionId/corroborate", s"""{"agrees":$agrees}""")

  def searchFlights(query: String): Future[Json] =
    get(s"/flights/search?q=$query")
}
