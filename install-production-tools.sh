#!/bin/bash

# VibeTravels Production Tools Installer
# Run this script on production server to set up convenient CLI tools

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BASHRC_FILE="$HOME/.bashrc"
CLI_HELPER="${SCRIPT_DIR}/production-cli.sh"

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║      VibeTravels Production Tools Installer                   ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

# Check if we're in the correct directory
if [ ! -f "$CLI_HELPER" ]; then
    echo "❌ Error: production-cli.sh not found in $SCRIPT_DIR"
    echo "   Please run this script from /var/www/vibetravels directory"
    exit 1
fi

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Error: Docker is not installed"
    exit 1
fi

# Check if docker-compose.production.yml exists
if [ ! -f "${SCRIPT_DIR}/docker-compose.production.yml" ]; then
    echo "❌ Error: docker-compose.production.yml not found"
    exit 1
fi

echo "✓ Prerequisites check passed"
echo ""

# Install jq if not present (for JSON parsing)
if ! command -v jq &> /dev/null; then
    echo "📦 Installing jq (JSON parser)..."
    if command -v apt-get &> /dev/null; then
        sudo apt-get update && sudo apt-get install -y jq
    elif command -v yum &> /dev/null; then
        sudo yum install -y jq
    else
        echo "⚠️  Could not install jq automatically. Please install manually."
        echo "   Some features may not work without jq."
    fi
fi

# Backup existing .bashrc
if [ -f "$BASHRC_FILE" ]; then
    cp "$BASHRC_FILE" "${BASHRC_FILE}.backup.$(date +%Y%m%d-%H%M%S)"
    echo "✓ Backed up existing .bashrc"
fi

# Check if already installed
if grep -q "VibeTravels Production CLI Helper" "$BASHRC_FILE" 2>/dev/null; then
    echo "⚠️  Production CLI Helper is already installed in .bashrc"
    read -p "Do you want to reinstall? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Installation cancelled."
        exit 0
    fi

    # Remove old installation
    sed -i '/# VibeTravels Production CLI Helper/,/# End VibeTravels Production CLI Helper/d' "$BASHRC_FILE"
fi

# Add to .bashrc
cat >> "$BASHRC_FILE" << EOF

# VibeTravels Production CLI Helper
# Auto-load production tools
if [ -f "${CLI_HELPER}" ]; then
    source "${CLI_HELPER}"
fi
# End VibeTravels Production CLI Helper
EOF

echo "✓ Added CLI helper to .bashrc"
echo ""

# Create useful scripts directory
SCRIPTS_DIR="${SCRIPT_DIR}/scripts"
mkdir -p "$SCRIPTS_DIR"

# Create quick status script
cat > "${SCRIPTS_DIR}/status.sh" << 'EOFSTATUS'
#!/bin/bash
cd /var/www/vibetravels
source production-cli.sh

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║          VibeTravels Production Status                        ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

echo "🐳 Container Status:"
dcp
echo ""

echo "📊 Queue Status:"
queue-status
echo ""

echo "❌ Failed Jobs:"
queue-failed | head -5
echo ""

echo "💾 Disk Usage:"
df -h / | grep -v "tmpfs"
echo ""

echo "🧠 Memory Usage:"
free -h | grep -E "Mem:|Swap:"
echo ""

echo "🌐 Application Health:"
health 2>/dev/null || echo "  ⚠️  Health endpoint not responding"
EOFSTATUS

chmod +x "${SCRIPTS_DIR}/status.sh"
echo "✓ Created quick status script: ${SCRIPTS_DIR}/status.sh"

# Create maintenance mode script
cat > "${SCRIPTS_DIR}/maintenance.sh" << 'EOFMAINT'
#!/bin/bash
cd /var/www/vibetravels
source production-cli.sh

MODE="${1:-status}"

case "$MODE" in
    on)
        echo "🔧 Enabling maintenance mode..."
        art down --render="errors::503"
        echo "✓ Maintenance mode enabled"
        echo "  Users will see a maintenance page"
        ;;
    off)
        echo "✅ Disabling maintenance mode..."
        art up
        echo "✓ Application is back online"
        ;;
    status)
        if art down --show 2>/dev/null | grep -q "Application is in maintenance mode"; then
            echo "🔧 Maintenance mode is ON"
        else
            echo "✅ Application is running normally"
        fi
        ;;
    *)
        echo "Usage: maintenance.sh [on|off|status]"
        exit 1
        ;;
esac
EOFMAINT

chmod +x "${SCRIPTS_DIR}/maintenance.sh"
echo "✓ Created maintenance mode script: ${SCRIPTS_DIR}/maintenance.sh"

# Create log viewer script
cat > "${SCRIPTS_DIR}/logs.sh" << 'EOFLOGS'
#!/bin/bash
cd /var/www/vibetravels
source production-cli.sh

SERVICE="${1:-app}"
LINES="${2:-50}"

echo "📋 Viewing logs for: $SERVICE (last $LINES lines)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

case "$SERVICE" in
    laravel|app)
        docker compose -f docker-compose.production.yml logs --tail="$LINES" app
        ;;
    worker|queue)
        docker compose -f docker-compose.production.yml logs --tail="$LINES" worker
        ;;
    nginx|web)
        docker compose -f docker-compose.production.yml logs --tail="$LINES" nginx
        ;;
    mysql|db|database)
        docker compose -f docker-compose.production.yml logs --tail="$LINES" mysql
        ;;
    redis|cache)
        docker compose -f docker-compose.production.yml logs --tail="$LINES" redis
        ;;
    *)
        echo "Available services: laravel, worker, nginx, mysql, redis"
        exit 1
        ;;
esac
EOFLOGS

chmod +x "${SCRIPTS_DIR}/logs.sh"
echo "✓ Created log viewer script: ${SCRIPTS_DIR}/logs.sh"

# Create symbolic links for easy access
ln -sf "${SCRIPTS_DIR}/status.sh" "${SCRIPT_DIR}/prod-status"
ln -sf "${SCRIPTS_DIR}/maintenance.sh" "${SCRIPT_DIR}/prod-maintenance"
ln -sf "${SCRIPTS_DIR}/logs.sh" "${SCRIPT_DIR}/prod-logs"
ln -sf "${SCRIPT_DIR}/check-production-health.sh" "${SCRIPT_DIR}/prod-health"

echo "✓ Created command shortcuts:"
echo "  ./prod-status       - Quick status overview"
echo "  ./prod-health       - Run health check"
echo "  ./prod-maintenance  - Toggle maintenance mode"
echo "  ./prod-logs         - View service logs"
echo ""

# Source the CLI helper for current session
source "$CLI_HELPER"

echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║                  Installation Complete!                       ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""
echo "🎉 Production tools installed successfully!"
echo ""
echo "📝 What's been added:"
echo "  • CLI helper with aliases and functions"
echo "  • Quick status script (./prod-status)"
echo "  • Health check script (./prod-health)"
echo "  • Maintenance mode toggle (./prod-maintenance on/off)"
echo "  • Log viewer (./prod-logs [service])"
echo ""
echo "🚀 Quick Start:"
echo "  1. Reload your shell: source ~/.bashrc"
echo "  2. Type: prod-help"
echo "  3. Try: queue-status"
echo ""
echo "💡 Example commands:"
echo "  art migrate              - Run migrations"
echo "  queue-monitor            - Monitor queue"
echo "  mysql                    - Open MySQL CLI"
echo "  tinker                   - Open Laravel Tinker"
echo "  db-backup                - Backup database"
echo "  ./prod-status            - Show quick status"
echo ""
echo "For full list of commands, type: prod-help"
