#!/bin/bash

##############################################################################
# VibeTravels Production Services Setup
#
# This script sets up:
# 1. Supervisor for Laravel queue workers
# 2. Cron jobs for Laravel scheduler
#
# Usage: sudo ./scripts/setup-production-services.sh
##############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== VibeTravels Production Services Setup ===${NC}\n"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Error: This script must be run as root (use sudo)${NC}"
    exit 1
fi

# Get the actual user who invoked sudo
REAL_USER=${SUDO_USER:-$USER}
PROJECT_DIR="/var/www/html"

echo -e "${YELLOW}Project directory: ${PROJECT_DIR}${NC}"
echo -e "${YELLOW}Running as user: ${REAL_USER}${NC}\n"

##############################################################################
# 1. Install Supervisor
##############################################################################

echo -e "${GREEN}[1/5] Installing Supervisor...${NC}"
if ! command -v supervisorctl &> /dev/null; then
    apt-get update -qq
    apt-get install -y supervisor
    echo -e "${GREEN}✓ Supervisor installed${NC}\n"
else
    echo -e "${GREEN}✓ Supervisor already installed${NC}\n"
fi

##############################################################################
# 2. Configure Supervisor for Laravel Workers
##############################################################################

echo -e "${GREEN}[2/5] Configuring Supervisor for Laravel workers...${NC}"

# Copy worker configuration
SUPERVISOR_CONF="/etc/supervisor/conf.d/vibetravels-worker.conf"
if [ -f "${PROJECT_DIR}/config/vibetravels-worker.conf" ]; then
    cp "${PROJECT_DIR}/config/vibetravels-worker.conf" "${SUPERVISOR_CONF}"
    echo -e "${GREEN}✓ Worker configuration copied to ${SUPERVISOR_CONF}${NC}"
else
    echo -e "${RED}✗ Worker config file not found at ${PROJECT_DIR}/config/vibetravels-worker.conf${NC}"
    exit 1
fi

# Reload supervisor
supervisorctl reread
supervisorctl update
supervisorctl start vibetravels-worker:*

echo -e "${GREEN}✓ Supervisor configured and workers started${NC}\n"

##############################################################################
# 3. Configure Cron for Laravel Scheduler
##############################################################################

echo -e "${GREEN}[3/5] Setting up cron job for Laravel scheduler...${NC}"

# Create cron entry for Laravel scheduler
CRON_ENTRY="* * * * * cd ${PROJECT_DIR} && php artisan schedule:run >> /dev/null 2>&1"

# Check if cron entry already exists for www-data user
if crontab -u www-data -l 2>/dev/null | grep -q "artisan schedule:run"; then
    echo -e "${YELLOW}⚠ Cron job already exists${NC}"
else
    # Add cron job for www-data user
    (crontab -u www-data -l 2>/dev/null; echo "${CRON_ENTRY}") | crontab -u www-data -
    echo -e "${GREEN}✓ Cron job added for www-data user${NC}"
fi

echo -e "${GREEN}✓ Laravel scheduler configured${NC}\n"

##############################################################################
# 4. Create log directory and set permissions
##############################################################################

echo -e "${GREEN}[4/5] Setting up logs and permissions...${NC}"

# Ensure storage directories exist and have correct permissions
mkdir -p "${PROJECT_DIR}/storage/logs"
chown -R www-data:www-data "${PROJECT_DIR}/storage"
chmod -R 775 "${PROJECT_DIR}/storage"

echo -e "${GREEN}✓ Permissions configured${NC}\n"

##############################################################################
# 5. Verify Setup
##############################################################################

echo -e "${GREEN}[5/5] Verifying setup...${NC}\n"

# Check Supervisor status
echo -e "${YELLOW}Supervisor worker status:${NC}"
supervisorctl status vibetravels-worker:*

# Check cron
echo -e "\n${YELLOW}Cron jobs for www-data:${NC}"
crontab -u www-data -l | grep artisan || echo "No cron jobs found!"

##############################################################################
# Summary
##############################################################################

echo -e "\n${GREEN}=====================================${NC}"
echo -e "${GREEN}Setup completed successfully!${NC}"
echo -e "${GREEN}=====================================${NC}\n"

echo -e "Services configured:"
echo -e "  ${GREEN}✓${NC} Supervisor (2 queue workers)"
echo -e "  ${GREEN}✓${NC} Cron (Laravel scheduler)"
echo -e ""
echo -e "Scheduled tasks:"
echo -e "  • ${YELLOW}plans:auto-complete${NC} - Daily (marks past trips as completed)"
echo -e "  • ${YELLOW}limits:reset-monthly${NC} - Monthly on 1st at 00:01 (Europe/Warsaw)"
echo -e ""
echo -e "Useful commands:"
echo -e "  ${YELLOW}supervisorctl status${NC}            - Check worker status"
echo -e "  ${YELLOW}supervisorctl restart vibetravels-worker:*${NC} - Restart workers"
echo -e "  ${YELLOW}supervisorctl tail vibetravels-worker:0${NC} - View worker logs"
echo -e "  ${YELLOW}tail -f ${PROJECT_DIR}/storage/logs/worker.log${NC} - View worker logs"
echo -e "  ${YELLOW}tail -f ${PROJECT_DIR}/storage/logs/laravel.log${NC} - View Laravel logs"
echo -e "  ${YELLOW}crontab -u www-data -l${NC}         - View cron jobs"
echo -e ""
echo -e "${GREEN}All done! 🚀${NC}\n"
