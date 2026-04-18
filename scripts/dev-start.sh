#!/bin/bash
# ================================================================
#  WHERE IS MY FLIGHT — Development Start Script
# ================================================================
#  Starts all services and initializes the development environment.
#
#  Usage: ./scripts/dev-start.sh
#  Options:
#    --build     Force rebuild all containers
#    --clean     Remove volumes and start fresh
#    --no-spark  Skip Spark services (faster startup)
# ================================================================

set -e

COMPOSE_FILE="docker-compose.yml"
PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$PROJECT_DIR"

echo "═══════════════════════════════════════════"
echo " WHERE IS MY FLIGHT — Dev Environment"
echo "═══════════════════════════════════════════"

# Parse arguments
BUILD_FLAG=""
CLEAN=false
NO_SPARK=false

for arg in "$@"; do
    case $arg in
        --build)  BUILD_FLAG="--build" ;;
        --clean)  CLEAN=true ;;
        --no-spark) NO_SPARK=true ;;
    esac
done

# Clean if requested
if [ "$CLEAN" = true ]; then
    echo "Cleaning volumes..."
    docker-compose down -v
    echo "✓ Volumes removed"
fi

# Copy .env if not exists
if [ ! -f .env ]; then
    echo "Creating .env from .env.example..."
    cp .env.example .env
    echo "✓ .env created — edit it with your API keys"
fi

# Determine services to start
if [ "$NO_SPARK" = true ]; then
    SERVICES="nginx laravel-web play-api postgres redis zookeeper kafka"
    echo "Starting without Spark..."
else
    SERVICES=""
    echo "Starting all services..."
fi

# Start Docker services
echo ""
echo "── Docker Compose ──"
docker-compose up -d $BUILD_FLAG $SERVICES

# Wait for services
echo ""
echo "── Waiting for services ──"

echo -n "PostgreSQL... "
until docker exec wimf-postgres pg_isready -q 2>/dev/null; do
    sleep 1
done
echo "✓"

echo -n "Redis... "
until docker exec wimf-redis redis-cli ping 2>/dev/null | grep -q PONG; do
    sleep 1
done
echo "✓"

echo -n "Kafka... "
sleep 5
echo "✓ (waited 5s)"

# Initialize Kafka topics
echo ""
echo "── Kafka Topics ──"
docker exec wimf-kafka bash -c "
  kafka-topics --create --if-not-exists --bootstrap-server kafka:9092 --topic flight-events --partitions 6 --replication-factor 1 2>/dev/null && echo '✓ flight-events' || echo '· flight-events exists'
  kafka-topics --create --if-not-exists --bootstrap-server kafka:9092 --topic gate-updates --partitions 3 --replication-factor 1 2>/dev/null && echo '✓ gate-updates' || echo '· gate-updates exists'
  kafka-topics --create --if-not-exists --bootstrap-server kafka:9092 --topic prediction-requests --partitions 2 --replication-factor 1 2>/dev/null && echo '✓ prediction-requests' || echo '· prediction-requests exists'
"

echo ""
echo "═══════════════════════════════════════════"
echo " ✓ WHERE IS MY FLIGHT is running!"
echo "═══════════════════════════════════════════"
echo ""
echo " Web App:      http://localhost"
echo " Laravel:      http://localhost:8000"
echo " Play API:     http://localhost:9000"
echo " Spark UI:     http://localhost:8080"
echo " PostgreSQL:   localhost:5432"
echo " Redis:        localhost:6379"
echo " Kafka:        localhost:29092"
echo ""
echo " Logs: docker-compose logs -f <service>"
echo " Stop: docker-compose down"
echo ""
