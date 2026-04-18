package services

import javax.inject._
import play.api.{Configuration, Logging}
import io.lettuce.core.{RedisClient, RedisURI}
import io.lettuce.core.pubsub.{RedisPubSubListener, StatefulRedisPubSubConnection}
import io.lettuce.core.pubsub.api.sync.RedisPubSubCommands
import scala.collection.concurrent.TrieMap
import java.util.UUID

/**
 * RedisPubSubService — Manages Redis pub/sub for real-time event distribution.
 *
 * This is the bridge between Kafka events and WebSocket clients:
 *   Kafka Consumer → RedisPubSubService.publish() → Redis
 *   Redis → RedisPubSubService.subscribe() → WebSocketActor → Browser
 *
 * Also serves as a cache layer for frequently accessed flight states,
 * reducing load on PostgreSQL and external APIs.
 */
@Singleton
class RedisPubSubService @Inject()(config: Configuration) extends Logging {

  private val redisHost = config.get[String]("redis.host")
  private val redisPort = config.get[Int]("redis.port")

  // Redis clients
  private lazy val redisClient: RedisClient = {
    val uri = RedisURI.builder()
      .withHost(redisHost)
      .withPort(redisPort)
      .build()
    RedisClient.create(uri)
  }

  // Connection for publishing
  private lazy val pubConnection = redisClient.connect()
  private lazy val pubCommands = pubConnection.sync()

  // Connection for subscribing (separate connection required by Redis)
  private lazy val subConnection: StatefulRedisPubSubConnection[String, String] = 
    redisClient.connectPubSub()

  // Active subscriptions: subscriptionId → callback
  private val subscriptions = TrieMap.empty[String, String => Unit]

  // Register the pub/sub listener once
  private lazy val listener = {
    val l = new RedisPubSubListener[String, String] {
      override def message(channel: String, message: String): Unit = {
        // Forward to all callbacks subscribed to this channel
        subscriptions.values.foreach { callback =>
          try { callback(message) }
          catch { case e: Exception => logger.error(s"Subscription callback error: ${e.getMessage}") }
        }
      }
      override def message(pattern: String, channel: String, message: String): Unit = {}
      override def subscribed(channel: String, count: Long): Unit = 
        logger.info(s"Subscribed to Redis channel: $channel (active: $count)")
      override def unsubscribed(channel: String, count: Long): Unit =
        logger.info(s"Unsubscribed from Redis channel: $channel (active: $count)")
      override def psubscribed(pattern: String, count: Long): Unit = {}
      override def punsubscribed(pattern: String, count: Long): Unit = {}
    }
    subConnection.addListener(l)
    l
  }

  // Ensure listener is initialized
  listener

  // ─────────────────────────────────────────────
  // PUB/SUB Operations
  // ─────────────────────────────────────────────

  /**
   * Publish a message to a Redis channel.
   * Called by KafkaConsumerService when new events arrive.
   */
  def publish(channel: String, message: String): Unit = {
    try {
      pubCommands.publish(channel, message)
      logger.debug(s"Published to $channel: ${message.take(100)}...")
    } catch {
      case e: Exception =>
        logger.error(s"Redis publish error on $channel: ${e.getMessage}", e)
    }
  }

  /**
   * Subscribe to a Redis channel with a callback.
   * Returns a subscription ID for later unsubscription.
   */
  def subscribe(channel: String)(callback: String => Unit): String = {
    val subscriptionId = UUID.randomUUID().toString
    subscriptions.put(subscriptionId, callback)
    
    subConnection.sync().subscribe(channel)
    logger.info(s"New subscription: id=$subscriptionId, channel=$channel")
    
    subscriptionId
  }

  /**
   * Unsubscribe by subscription ID.
   */
  def unsubscribe(subscriptionId: String): Unit = {
    subscriptions.remove(subscriptionId)
    logger.info(s"Removed subscription: $subscriptionId")
  }

  // ─────────────────────────────────────────────
  // Cache Operations (Redis as cache layer)
  // ─────────────────────────────────────────────

  /**
   * Cache a flight state to avoid hammering external APIs.
   */
  def cacheFlightState(flightId: String, state: String, ttlSeconds: Long = 60): Unit = {
    try {
      pubCommands.setex(s"wimf:cache:flight:$flightId", ttlSeconds, state)
    } catch {
      case e: Exception => logger.error(s"Redis cache set error: ${e.getMessage}")
    }
  }

  /**
   * Get a cached flight state.
   */
  def getCachedFlightState(flightId: String): Option[String] = {
    try {
      Option(pubCommands.get(s"wimf:cache:flight:$flightId"))
    } catch {
      case e: Exception =>
        logger.error(s"Redis cache get error: ${e.getMessage}")
        None
    }
  }

  /**
   * Cache airport board data.
   */
  def cacheBoardData(airportIata: String, data: String, ttlSeconds: Long = 30): Unit = {
    try {
      pubCommands.setex(s"wimf:cache:board:$airportIata", ttlSeconds, data)
    } catch {
      case e: Exception => logger.error(s"Redis board cache error: ${e.getMessage}")
    }
  }

  def getCachedBoardData(airportIata: String): Option[String] = {
    try {
      Option(pubCommands.get(s"wimf:cache:board:$airportIata"))
    } catch {
      case e: Exception => None
    }
  }

  /**
   * Cleanup on application shutdown.
   */
  def shutdown(): Unit = {
    logger.info("Shutting down Redis connections...")
    subscriptions.clear()
    subConnection.close()
    pubConnection.close()
    redisClient.shutdown()
  }

  // Singleton accessor for actor usage
  RedisPubSubService.setInstance(this)
}

object RedisPubSubService {
  @volatile private var instance: RedisPubSubService = _
  
  private[services] def setInstance(svc: RedisPubSubService): Unit = { instance = svc }
  def getInstance(): RedisPubSubService = instance
}
