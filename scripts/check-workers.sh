#!/bin/bash

##############################################################################
# Worker Health Check Script
#
# Checks if queue workers are running and processing jobs
# Restarts worker container if unhealthy
#
# Usage:
#   Run manually: ./scripts/check-workers.sh
#   Run via cron: */10 * * * * /var/www/vibetravels/scripts/check-workers.sh >> /var/log/worker-monitor.log 2>&1
##############################################################################

set -e

PROJECT_DIR="/var/www/vibetravels"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# Colors for output
RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
NC='\033[0m' # No Color

cd "$PROJECT_DIR" || exit 1

echo "[$DATE] Checking worker health..."

# Check if worker container is running
WORKER_STATUS=$(docker compose -f docker-compose.production.yml ps worker --format json | jq -r '.[0].Health' 2>/dev/null || echo "unknown")

echo "[$DATE] Worker status: $WORKER_STATUS"

# Check if worker is unhealthy or not running
if [ "$WORKER_STATUS" != "healthy" ]; then
    echo -e "${RED}[$DATE] WARNING: Worker is $WORKER_STATUS${NC}"

    # Check worker logs for Redis connection errors
    REDIS_ERRORS=$(docker compose -f docker-compose.production.yml logs --tail=20 worker 2>/dev/null | grep -c "getaddrinfo for redis failed" || echo "0")

    if [ "$REDIS_ERRORS" -gt "5" ]; then
        echo -e "${RED}[$DATE] CRITICAL: Worker has Redis connection errors (count: $REDIS_ERRORS)${NC}"
        echo "[$DATE] Restarting worker container..."

        docker compose -f docker-compose.production.yml restart worker
        sleep 5

        echo -e "${GREEN}[$DATE] Worker restarted${NC}"
    else
        echo "[$DATE] Worker logs look OK, no restart needed"
    fi
else
    echo -e "${GREEN}[$DATE] Worker is healthy${NC}"
fi

# Check queue sizes
echo "[$DATE] Queue status:"
DEFAULT_QUEUE=$(docker compose -f docker-compose.production.yml exec -T redis redis-cli LLEN queues:default 2>/dev/null || echo "0")
AI_QUEUE=$(docker compose -f docker-compose.production.yml exec -T redis redis-cli LLEN queues:ai-generation 2>/dev/null || echo "0")

echo "  - default queue: $DEFAULT_QUEUE jobs"
echo "  - ai-generation queue: $AI_QUEUE jobs"

# Warn if queues are backing up
TOTAL_JOBS=$((DEFAULT_QUEUE + AI_QUEUE))
if [ "$TOTAL_JOBS" -gt "10" ]; then
    echo -e "${YELLOW}[$DATE] WARNING: Queue backlog detected ($TOTAL_JOBS jobs)${NC}"
fi
