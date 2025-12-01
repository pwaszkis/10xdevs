#!/bin/bash

##############################################################################
# Memory Monitor with Auto-Restart
#
# Monitors system memory usage and restarts Docker containers if memory
# exceeds critical threshold (85%)
#
# Usage:
#   Run manually: ./scripts/monitor-memory.sh
#   Run via cron: */5 * * * * /var/www/vibetravels/scripts/monitor-memory.sh >> /var/log/memory-monitor.log 2>&1
##############################################################################

set -e

# Configuration
MEMORY_THRESHOLD=85  # Restart if memory usage exceeds this percentage
PROJECT_DIR="/var/www/vibetravels"
LOG_FILE="/var/log/memory-monitor.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# Colors for output
RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
NC='\033[0m' # No Color

# Get memory usage percentage
MEMORY_USED=$(free | grep Mem | awk '{print int($3/$2 * 100)}')

echo "[$DATE] Memory usage: ${MEMORY_USED}%"

# Check if memory usage exceeds threshold
if [ "$MEMORY_USED" -ge "$MEMORY_THRESHOLD" ]; then
    echo -e "${RED}[$DATE] CRITICAL: Memory usage at ${MEMORY_USED}% (threshold: ${MEMORY_THRESHOLD}%)${NC}"
    echo "[$DATE] Restarting Docker containers to free memory..."

    # Navigate to project directory
    cd "$PROJECT_DIR" || exit 1

    # Restart containers
    docker compose -f docker-compose.production.yml restart

    # Wait for containers to start
    sleep 10

    # Check container status
    docker compose -f docker-compose.production.yml ps

    echo -e "${GREEN}[$DATE] Containers restarted successfully${NC}"

    # Send alert (optional - configure email/webhook)
    # echo "Memory critical on przem-podroze.pl - restarted containers" | mail -s "Memory Alert" admin@example.com

else
    echo -e "${GREEN}[$DATE] Memory usage OK (${MEMORY_USED}%)${NC}"
fi

# Show top memory-consuming containers
echo "[$DATE] Top memory consumers:"
docker stats --no-stream --format "table {{.Name}}\t{{.MemUsage}}\t{{.MemPerc}}" | head -7
