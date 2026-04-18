#!/bin/bash
# ================================================================
#  WHERE IS MY FLIGHT — Kafka Topic Initialization
# ================================================================
#  Run this script after Kafka is up to create the required topics.
#  Usage: docker exec wimf-kafka /opt/kafka-setup/create-topics.sh
# ================================================================

KAFKA_BROKER="${KAFKA_BROKER:-kafka:9092}"

echo "═══════════════════════════════════════════"
echo " WIMF — Creating Kafka Topics"
echo "═══════════════════════════════════════════"

# Wait for Kafka to be ready
echo "Waiting for Kafka at $KAFKA_BROKER..."
cub kafka-ready 1 30 -b $KAFKA_BROKER 2>/dev/null || sleep 10

# ── Flight Events Topic ──
# Raw flight status events from external APIs (AviationStack/FlightAware)
kafka-topics --create \
  --bootstrap-server $KAFKA_BROKER \
  --topic flight-events \
  --partitions 6 \
  --replication-factor 1 \
  --config retention.ms=86400000 \
  --config cleanup.policy=delete \
  --if-not-exists

echo "✓ Created topic: flight-events (6 partitions, 24h retention)"

# ── Gate Updates Topic ──
# Community gate contributions from Laravel
kafka-topics --create \
  --bootstrap-server $KAFKA_BROKER \
  --topic gate-updates \
  --partitions 3 \
  --replication-factor 1 \
  --config retention.ms=43200000 \
  --config cleanup.policy=delete \
  --if-not-exists

echo "✓ Created topic: gate-updates (3 partitions, 12h retention)"

# ── Prediction Requests Topic ──
# On-demand prediction requests from the API
kafka-topics --create \
  --bootstrap-server $KAFKA_BROKER \
  --topic prediction-requests \
  --partitions 2 \
  --replication-factor 1 \
  --config retention.ms=3600000 \
  --config cleanup.policy=delete \
  --if-not-exists

echo "✓ Created topic: prediction-requests (2 partitions, 1h retention)"

# ── Prediction Results Topic ──
# Completed predictions from Spark
kafka-topics --create \
  --bootstrap-server $KAFKA_BROKER \
  --topic prediction-results \
  --partitions 2 \
  --replication-factor 1 \
  --config retention.ms=7200000 \
  --config cleanup.policy=delete \
  --if-not-exists

echo "✓ Created topic: prediction-results (2 partitions, 2h retention)"

# ── Weather Updates Topic ──
# Weather data feed for prediction correlation
kafka-topics --create \
  --bootstrap-server $KAFKA_BROKER \
  --topic weather-updates \
  --partitions 2 \
  --replication-factor 1 \
  --config retention.ms=3600000 \
  --config cleanup.policy=compact \
  --if-not-exists

echo "✓ Created topic: weather-updates (2 partitions, compacted)"

echo ""
echo "═══════════════════════════════════════════"
echo " All Kafka topics created successfully"
echo "═══════════════════════════════════════════"

# List all topics
echo ""
echo "Current topics:"
kafka-topics --list --bootstrap-server $KAFKA_BROKER
