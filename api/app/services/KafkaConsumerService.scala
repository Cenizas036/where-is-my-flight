package services

import javax.inject._
import play.api.{Configuration, Logging}
import scala.concurrent.{ExecutionContext, Future}
import java.util.{Properties, UUID}
import org.apache.kafka.clients.consumer.{ConsumerConfig, KafkaConsumer}
import org.apache.kafka.common.serialization.StringDeserializer
import play.api.libs.json._
import scala.jdk.CollectionConverters._
import java.time.Duration

/**
 * KafkaConsumerService — Ingests raw flight events from Kafka and pushes
 * them through Redis pub/sub for WebSocket distribution.
 *
 * Kafka topics:
 *   - flight-events: Status changes, delays, gate assignments from external APIs
 *   - gate-updates:  Community gate contributions (from Laravel)
 *
 * Flow:
 *   External API → Kafka Producer → [flight-events topic]
 *                                       ↓
 *   KafkaConsumerService (this) polls from topic
 *                                       ↓
 *   Process event → Update DB → Publish to Redis pub/sub
 *                                       ↓
 *   WebSocketActor picks up from Redis → Push to browser
 */
@Singleton
class KafkaConsumerService @Inject()(
  config: Configuration,
  redisService: RedisPubSubService,
  flightService: FlightDataService
)(implicit ec: ExecutionContext) extends Logging {

  private val bootstrapServers = config.get[String]("kafka.bootstrap-servers")
  private val groupId = config.get[String]("kafka.group-id")
  private val flightEventsTopic = config.get[String]("kafka.topics.flight-events")
  private val gateUpdatesTopic = config.get[String]("kafka.topics.gate-updates")

  @volatile private var running = true

  /**
   * Start consuming from Kafka topics.
   * Called on application start via Play's lifecycle hooks.
   */
  def start(): Unit = {
    logger.info("Starting Kafka consumer service...")

    // Flight events consumer (runs in its own thread)
    val flightConsumerThread = new Thread(() => consumeFlightEvents(), "kafka-flight-consumer")
    flightConsumerThread.setDaemon(true)
    flightConsumerThread.start()

    // Gate updates consumer
    val gateConsumerThread = new Thread(() => consumeGateUpdates(), "kafka-gate-consumer")
    gateConsumerThread.setDaemon(true)
    gateConsumerThread.start()

    logger.info(s"Kafka consumers started for topics: $flightEventsTopic, $gateUpdatesTopic")
  }

  /**
   * Stop all consumers gracefully.
   */
  def stop(): Unit = {
    logger.info("Stopping Kafka consumer service...")
    running = false
  }

  /**
   * Consume flight status events from external API producers.
   */
  private def consumeFlightEvents(): Unit = {
    val consumer = createConsumer(s"$groupId-flights")
    consumer.subscribe(java.util.Collections.singletonList(flightEventsTopic))

    while (running) {
      try {
        val records = consumer.poll(Duration.ofMillis(1000))
        
        records.asScala.foreach { record =>
          try {
            val event = Json.parse(record.value())
            processFlightEvent(event)
          } catch {
            case e: Exception =>
              logger.error(s"Error processing flight event: ${e.getMessage}", e)
          }
        }
      } catch {
        case e: Exception if running =>
          logger.error(s"Kafka consumer error: ${e.getMessage}", e)
          Thread.sleep(5000) // backoff on error
      }
    }

    consumer.close()
  }

  /**
   * Consume gate updates (from Laravel community contributions).
   */
  private def consumeGateUpdates(): Unit = {
    val consumer = createConsumer(s"$groupId-gates")
    consumer.subscribe(java.util.Collections.singletonList(gateUpdatesTopic))

    while (running) {
      try {
        val records = consumer.poll(Duration.ofMillis(1000))
        
        records.asScala.foreach { record =>
          try {
            val event = Json.parse(record.value())
            processGateUpdate(event)
          } catch {
            case e: Exception =>
              logger.error(s"Error processing gate update: ${e.getMessage}", e)
          }
        }
      } catch {
        case e: Exception if running =>
          logger.error(s"Kafka gate consumer error: ${e.getMessage}", e)
          Thread.sleep(5000)
      }
    }

    consumer.close()
  }

  /**
   * Process a single flight event:
   * 1. Update the flight record in DB
   * 2. Check if prediction needs refresh
   * 3. Publish delta to Redis for WebSocket broadcast
   */
  private def processFlightEvent(event: JsValue): Unit = {
    val flightId = (event \ "flight_id").asOpt[String]
    val airportIata = (event \ "airport_iata").asOpt[String]
    val eventType = (event \ "event_type").asOpt[String].getOrElse("status_change")

    logger.debug(s"Processing flight event: type=$eventType, flight=$flightId")

    // Publish to airport-level channel (for board updates)
    airportIata.foreach { iata =>
      val channel = s"wimf:flights:updates:$iata"
      val payload = Json.obj(
        "type"      -> "flight_update",
        "data"      -> event,
        "timestamp" -> java.time.Instant.now().toString
      )
      redisService.publish(channel, payload.toString)
    }

    // Publish to flight-specific channel (for detail page)
    flightId.foreach { fid =>
      val channel = s"wimf:flights:updates:$fid"
      val payload = Json.obj(
        "type"      -> eventType,
        "data"      -> event,
        "timestamp" -> java.time.Instant.now().toString
      )
      redisService.publish(channel, payload.toString)
    }
  }

  /**
   * Process a community gate update:
   * Broadcast to relevant airport and flight channels.
   */
  private def processGateUpdate(event: JsValue): Unit = {
    val flightId = (event \ "flight_id").asOpt[String]
    val airportIata = (event \ "airport_iata").asOpt[String]

    val payload = Json.obj(
      "type"      -> "gate_update",
      "data"      -> event,
      "timestamp" -> java.time.Instant.now().toString
    )

    airportIata.foreach { iata =>
      redisService.publish(s"wimf:flights:updates:$iata", payload.toString)
    }

    flightId.foreach { fid =>
      redisService.publish(s"wimf:flights:updates:$fid", payload.toString)
    }
  }

  /**
   * Create a Kafka consumer with standard configuration.
   */
  private def createConsumer(groupId: String): KafkaConsumer[String, String] = {
    val props = new Properties()
    props.put(ConsumerConfig.BOOTSTRAP_SERVERS_CONFIG, bootstrapServers)
    props.put(ConsumerConfig.GROUP_ID_CONFIG, groupId)
    props.put(ConsumerConfig.KEY_DESERIALIZER_CLASS_CONFIG, classOf[StringDeserializer].getName)
    props.put(ConsumerConfig.VALUE_DESERIALIZER_CLASS_CONFIG, classOf[StringDeserializer].getName)
    props.put(ConsumerConfig.AUTO_OFFSET_RESET_CONFIG, "latest")
    props.put(ConsumerConfig.ENABLE_AUTO_COMMIT_CONFIG, "true")
    props.put(ConsumerConfig.MAX_POLL_RECORDS_CONFIG, "100")

    new KafkaConsumer[String, String](props)
  }
}
