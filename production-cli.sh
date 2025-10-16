#!/bin/bash

# VibeTravels Production CLI Helper
# Source this file to get convenient aliases for production commands
# Usage: source production-cli.sh  (or add to ~/.bashrc)

# Colors for output
export PROD_CLI_GREEN='\033[0;32m'
export PROD_CLI_YELLOW='\033[1;33m'
export PROD_CLI_BLUE='\033[0;34m'
export PROD_CLI_NC='\033[0m' # No Color

# Set production directory
export PROD_DIR="/var/www/vibetravels"
export COMPOSE_FILE="docker-compose.production.yml"

# Quick navigation
alias cdprod="cd ${PROD_DIR}"
alias cdlogs="cd ${PROD_DIR}/storage/logs"

# Docker Compose shortcuts
alias dc="docker compose -f ${PROD_DIR}/${COMPOSE_FILE}"
alias dcp="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} ps"
alias dcl="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} logs -f"
alias dcr="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} restart"

# Laravel Artisan
alias art="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec app php artisan"
alias artisan="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec app php artisan"

# Database shortcuts
alias mysql="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec mysql mysql -u vibetravels -p\$(grep DB_PASSWORD ${PROD_DIR}/.env | cut -d '=' -f2) vibetravels"
alias mysqlroot="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec mysql mysql -u root -p\$(grep DB_PASSWORD ${PROD_DIR}/.env | cut -d '=' -f2)"
alias mysqldump="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec mysql mysqldump -u vibetravels -p\$(grep DB_PASSWORD ${PROD_DIR}/.env | cut -d '=' -f2) vibetravels"

# Redis shortcuts
alias redis="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec redis redis-cli"
alias redis-keys="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec redis redis-cli KEYS '*'"
alias redis-flush="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec redis redis-cli FLUSHDB"

# Queue management
alias queue-monitor="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec app php artisan queue:monitor redis"
alias queue-failed="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec app php artisan queue:failed"
alias queue-retry="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec app php artisan queue:retry"
alias queue-flush="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec app php artisan queue:flush"
alias queue-restart="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec app php artisan queue:restart"
alias queue-work="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec app php artisan queue:work --once"

# Logs shortcuts
alias logs-app="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} logs -f app"
alias logs-worker="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} logs -f worker"
alias logs-nginx="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} logs -f nginx"
alias logs-scheduler="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} logs -f scheduler"
alias logs-laravel="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec app tail -f storage/logs/laravel.log"

# Cache management
alias cache-clear="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec app php artisan cache:clear"
alias cache-flush="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec app php artisan optimize:clear"
alias cache-rebuild="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec app bash -c 'php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache'"

# Tinker (Laravel REPL)
alias tinker="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec app php artisan tinker"

# Common Laravel commands
alias migrate="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec app php artisan migrate"
alias migrate-fresh="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec app php artisan migrate:fresh"
alias migrate-rollback="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec app php artisan migrate:rollback"
alias db-show="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec app php artisan db:show"
alias db-seed="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec app php artisan db:seed"

# Health checks
alias health="curl -s http://localhost/health | jq ."
alias health-check="${PROD_DIR}/check-production-health.sh"

# Composer
alias composer="docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec app composer"

# Functions for more complex operations

# Quick database query
dbquery() {
    if [ -z "$1" ]; then
        echo -e "${PROD_CLI_YELLOW}Usage: dbquery 'SELECT * FROM users LIMIT 5'${PROD_CLI_NC}"
        return 1
    fi

    docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec mysql mysql \
        -u vibetravels \
        -p$(grep DB_PASSWORD ${PROD_DIR}/.env | cut -d '=' -f2) \
        vibetravels \
        -e "$1"
}

# Check queue length
queue-length() {
    local queue="${1:-default}"
    echo -e "${PROD_CLI_BLUE}Queue: $queue${PROD_CLI_NC}"
    docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec redis redis-cli LLEN "vibetravels_database_queues:$queue"
}

# Show all queue lengths
queue-status() {
    echo -e "${PROD_CLI_BLUE}📊 Queue Status:${PROD_CLI_NC}"
    echo -e "  default: $(queue-length default)"
    echo -e "  ai-generation: $(queue-length ai-generation)"
}

# Backup database
db-backup() {
    local backup_name="backup-$(date +%Y%m%d-%H%M%S).sql.gz"
    local backup_dir="${PROD_DIR}/backups"

    mkdir -p "$backup_dir"

    echo -e "${PROD_CLI_BLUE}Creating backup: $backup_name${PROD_CLI_NC}"

    docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec mysql mysqldump \
        -u vibetravels \
        -p$(grep DB_PASSWORD ${PROD_DIR}/.env | cut -d '=' -f2) \
        vibetravels | gzip > "${backup_dir}/${backup_name}"

    if [ $? -eq 0 ]; then
        echo -e "${PROD_CLI_GREEN}✓ Backup created: ${backup_dir}/${backup_name}${PROD_CLI_NC}"
        ls -lh "${backup_dir}/${backup_name}"
    else
        echo -e "${PROD_CLI_YELLOW}✗ Backup failed${PROD_CLI_NC}"
    fi
}

# Restore database from backup
db-restore() {
    if [ -z "$1" ]; then
        echo -e "${PROD_CLI_YELLOW}Usage: db-restore /path/to/backup.sql.gz${PROD_CLI_NC}"
        echo -e "Available backups:"
        ls -lh ${PROD_DIR}/backups/*.sql.gz 2>/dev/null || echo "  No backups found"
        return 1
    fi

    if [ ! -f "$1" ]; then
        echo -e "${PROD_CLI_YELLOW}✗ File not found: $1${PROD_CLI_NC}"
        return 1
    fi

    echo -e "${PROD_CLI_YELLOW}⚠️  WARNING: This will replace the current database!${PROD_CLI_NC}"
    read -p "Are you sure? (type 'yes' to continue): " confirm

    if [ "$confirm" != "yes" ]; then
        echo "Cancelled."
        return 0
    fi

    echo -e "${PROD_CLI_BLUE}Restoring from: $1${PROD_CLI_NC}"

    gunzip < "$1" | docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec -T mysql mysql \
        -u vibetravels \
        -p$(grep DB_PASSWORD ${PROD_DIR}/.env | cut -d '=' -f2) \
        vibetravels

    if [ $? -eq 0 ]; then
        echo -e "${PROD_CLI_GREEN}✓ Restore completed${PROD_CLI_NC}"
    else
        echo -e "${PROD_CLI_YELLOW}✗ Restore failed${PROD_CLI_NC}"
    fi
}

# Quick container shell access
shell() {
    local container="${1:-app}"
    echo -e "${PROD_CLI_BLUE}Opening shell in $container container...${PROD_CLI_NC}"
    docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec "$container" /bin/sh
}

# Restart specific service
restart() {
    if [ -z "$1" ]; then
        echo -e "${PROD_CLI_YELLOW}Usage: restart [service]${PROD_CLI_NC}"
        echo -e "Available services: app, nginx, mysql, redis, worker, scheduler"
        return 1
    fi

    echo -e "${PROD_CLI_BLUE}Restarting $1...${PROD_CLI_NC}"
    docker compose -f ${PROD_DIR}/${COMPOSE_FILE} restart "$1"
}

# Show container stats
stats() {
    docker compose -f ${PROD_DIR}/${COMPOSE_FILE} ps --format "table {{.Name}}\t{{.Status}}\t{{.Ports}}"
    echo ""
    docker stats --no-stream $(docker compose -f ${PROD_DIR}/${COMPOSE_FILE} ps -q)
}

# Check failed jobs details
queue-failed-details() {
    docker compose -f ${PROD_DIR}/${COMPOSE_FILE} exec app php artisan queue:failed --format=json | jq '.'
}

# Watch logs (auto-refresh)
watch-logs() {
    local service="${1:-app}"
    watch -n 2 "docker compose -f ${PROD_DIR}/${COMPOSE_FILE} logs --tail=30 $service"
}

# Quick deploy (pull and restart)
quick-deploy() {
    echo -e "${PROD_CLI_BLUE}🚀 Quick Deploy${PROD_CLI_NC}"

    cd ${PROD_DIR}

    echo "1. Pulling latest changes..."
    git pull origin main

    echo "2. Pulling Docker images..."
    docker compose -f ${COMPOSE_FILE} pull

    echo "3. Restarting services..."
    docker compose -f ${COMPOSE_FILE} up -d

    echo "4. Running migrations..."
    docker compose -f ${COMPOSE_FILE} exec app php artisan migrate --force

    echo "5. Clearing cache..."
    docker compose -f ${COMPOSE_FILE} exec app php artisan optimize:clear
    docker compose -f ${COMPOSE_FILE} exec app php artisan config:cache

    echo "6. Restarting workers..."
    docker compose -f ${COMPOSE_FILE} restart worker

    echo -e "${PROD_CLI_GREEN}✓ Deploy complete!${PROD_CLI_NC}"
}

# Show helpful commands
prod-help() {
    cat << 'EOF'
╔═══════════════════════════════════════════════════════════════╗
║          VibeTravels Production CLI Helper                    ║
╚═══════════════════════════════════════════════════════════════╝

📂 Navigation:
  cdprod              - Go to production directory
  cdlogs              - Go to logs directory

🐳 Docker:
  dc [command]        - Docker compose shortcut
  dcp                 - Show container status
  dcl                 - Follow all logs
  dcr                 - Restart all containers
  stats               - Show container stats

🔧 Laravel Artisan:
  art [command]       - Run artisan command
  artisan [command]   - Run artisan command (alias)
  tinker              - Open Laravel Tinker REPL
  migrate             - Run database migrations
  db-show             - Show database info

🗄️  Database:
  mysql               - Open MySQL CLI
  mysqlroot           - Open MySQL CLI as root
  dbquery "SQL"       - Execute SQL query
  db-backup           - Create database backup
  db-restore [file]   - Restore from backup

🔴 Redis:
  redis               - Open Redis CLI
  redis-keys          - Show all Redis keys
  redis-flush         - Flush Redis database (⚠️  careful!)

📬 Queue:
  queue-monitor       - Monitor queue status
  queue-status        - Show queue lengths
  queue-failed        - List failed jobs
  queue-retry [id]    - Retry failed job
  queue-work          - Process one job

📋 Logs:
  logs-app            - Follow app logs
  logs-worker         - Follow worker logs
  logs-nginx          - Follow nginx logs
  logs-laravel        - Follow Laravel log file
  watch-logs [service] - Auto-refresh logs

💾 Cache:
  cache-clear         - Clear application cache
  cache-flush         - Clear all caches
  cache-rebuild       - Clear and rebuild all caches

🔍 Health:
  health              - Check application health endpoint
  health-check        - Run full health check script

🚀 Deployment:
  quick-deploy        - Pull changes and restart services

🐚 Shell Access:
  shell [container]   - Open shell in container (default: app)
  restart [service]   - Restart specific service

📚 Help:
  prod-help           - Show this help message

══════════════════════════════════════════════════════════════════

Examples:
  dbquery "SELECT COUNT(*) FROM users"
  queue-length ai-generation
  db-backup
  art queue:failed
  shell worker
  restart nginx

EOF
}

# Print welcome message
echo -e "${PROD_CLI_GREEN}✓ Production CLI Helper loaded!${PROD_CLI_NC}"
echo -e "  Type ${PROD_CLI_BLUE}prod-help${PROD_CLI_NC} to see available commands"
echo -e "  Current directory: ${PROD_CLI_BLUE}${PROD_DIR}${PROD_CLI_NC}"
