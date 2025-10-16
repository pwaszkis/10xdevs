#!/bin/bash

# VibeTravels Production Health Check Script
# Run this on production server to verify everything is configured correctly

set -e

echo "🔍 VibeTravels Production Health Check"
echo "========================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running in correct directory
if [ ! -f "docker-compose.production.yml" ]; then
    echo -e "${RED}❌ Error: docker-compose.production.yml not found!${NC}"
    echo "   Please run this script from /var/www/vibetravels directory"
    exit 1
fi

echo "📋 1. Checking .env configuration..."
if [ ! -f .env ]; then
    echo -e "${RED}❌ .env file not found!${NC}"
    echo "   Copy .env.production.example to .env and configure it"
    exit 1
fi

# Check critical env variables
check_env() {
    local key=$1
    local expected=$2
    local actual=$(grep "^${key}=" .env | cut -d '=' -f2)

    if [ "$actual" == "$expected" ]; then
        echo -e "  ${GREEN}✓${NC} ${key}=${expected}"
    else
        echo -e "  ${YELLOW}⚠${NC}  ${key}=${actual} (expected: ${expected})"
    fi
}

check_env "DB_HOST" "mysql"
check_env "REDIS_HOST" "redis"
check_env "CACHE_STORE" "redis"
check_env "QUEUE_CONNECTION" "redis"
check_env "SESSION_DRIVER" "redis"

echo ""
echo "🐳 2. Checking Docker containers..."
docker compose -f docker-compose.production.yml ps --format "table {{.Name}}\t{{.Status}}\t{{.Ports}}"

echo ""
echo "🔗 3. Testing service connections..."

# Test MySQL
if docker compose -f docker-compose.production.yml exec -T mysql mysqladmin ping -h localhost -u root -p$(grep DB_PASSWORD .env | cut -d '=' -f2) 2>/dev/null | grep -q "mysqld is alive"; then
    echo -e "  ${GREEN}✓${NC} MySQL is responding"
else
    echo -e "  ${RED}✗${NC} MySQL connection failed"
fi

# Test Redis
if docker compose -f docker-compose.production.yml exec -T redis redis-cli PING 2>/dev/null | grep -q "PONG"; then
    echo -e "  ${GREEN}✓${NC} Redis is responding"
else
    echo -e "  ${RED}✗${NC} Redis connection failed"
fi

# Test Laravel database connection
if docker compose -f docker-compose.production.yml exec -T app php artisan db:show 2>/dev/null | grep -q "Connection"; then
    echo -e "  ${GREEN}✓${NC} Laravel can connect to database"
else
    echo -e "  ${RED}✗${NC} Laravel database connection failed"
fi

echo ""
echo "📊 4. Checking queue configuration..."

# Check worker container
if docker compose -f docker-compose.production.yml ps worker | grep -q "Up"; then
    echo -e "  ${GREEN}✓${NC} Worker container is running"

    # Check queue lengths
    DEFAULT_QUEUE=$(docker compose -f docker-compose.production.yml exec -T redis redis-cli LLEN "vibetravels_database_queues:default" 2>/dev/null || echo "0")
    AI_QUEUE=$(docker compose -f docker-compose.production.yml exec -T redis redis-cli LLEN "vibetravels_database_queues:ai-generation" 2>/dev/null || echo "0")

    echo "  📦 Queue lengths:"
    echo "     default: $DEFAULT_QUEUE jobs"
    echo "     ai-generation: $AI_QUEUE jobs"
else
    echo -e "  ${RED}✗${NC} Worker container is not running"
fi

# Check failed jobs
FAILED_JOBS=$(docker compose -f docker-compose.production.yml exec -T app php artisan queue:failed --format=json 2>/dev/null | jq length 2>/dev/null || echo "0")
if [ "$FAILED_JOBS" -gt "0" ]; then
    echo -e "  ${YELLOW}⚠${NC}  There are $FAILED_JOBS failed jobs"
else
    echo -e "  ${GREEN}✓${NC} No failed jobs"
fi

echo ""
echo "🌐 5. Testing application endpoints..."

# Test health endpoint
if curl -s -f http://localhost/health >/dev/null 2>&1 || curl -s -f -k https://localhost/health >/dev/null 2>&1; then
    echo -e "  ${GREEN}✓${NC} /health endpoint responding"
else
    echo -e "  ${RED}✗${NC} /health endpoint not responding"
fi

# Test Livewire assets
if curl -s -f http://localhost/livewire/livewire.min.js >/dev/null 2>&1 || curl -s -f -k https://localhost/livewire/livewire.min.js >/dev/null 2>&1; then
    echo -e "  ${GREEN}✓${NC} Livewire assets accessible"
else
    echo -e "  ${YELLOW}⚠${NC}  Livewire assets not found (might need route:cache clear)"
fi

echo ""
echo "📝 6. Checking logs for recent errors..."
RECENT_ERRORS=$(docker compose -f docker-compose.production.yml exec -T app tail -100 storage/logs/laravel.log 2>/dev/null | grep -c "ERROR" || echo "0")
if [ "$RECENT_ERRORS" -gt "0" ]; then
    echo -e "  ${YELLOW}⚠${NC}  Found $RECENT_ERRORS ERROR entries in recent logs"
    echo "     Run: docker compose -f docker-compose.production.yml exec app tail -50 storage/logs/laravel.log"
else
    echo -e "  ${GREEN}✓${NC} No recent errors in logs"
fi

echo ""
echo "========================================"
echo "🎉 Health check complete!"
echo ""
echo "📚 Useful commands:"
echo "  View logs:        docker compose -f docker-compose.production.yml logs -f"
echo "  Restart services: docker compose -f docker-compose.production.yml restart"
echo "  Check queues:     docker compose -f docker-compose.production.yml exec app php artisan queue:monitor redis"
echo "  Retry failed:     docker compose -f docker-compose.production.yml exec app php artisan queue:retry all"
