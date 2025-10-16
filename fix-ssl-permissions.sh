#!/bin/bash

# Fix SSL certificate directory permissions
# Run this if you get "Permission denied" errors during SSL setup

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}Fixing SSL certificate directory permissions...${NC}"
echo ""

# Check if certbot directory exists
if [ ! -d "certbot" ]; then
    echo -e "${YELLOW}certbot directory doesn't exist yet. Nothing to fix.${NC}"
    exit 0
fi

# Check current owner
CERTBOT_OWNER=$(stat -c '%U' certbot 2>/dev/null || stat -f '%Su' certbot 2>/dev/null || echo "unknown")
CURRENT_USER=$(whoami)

echo "Current directory owner: $CERTBOT_OWNER"
echo "Current user: $CURRENT_USER"
echo ""

if [ "$CERTBOT_OWNER" = "root" ]; then
    echo -e "${YELLOW}⚠ certbot directory is owned by root${NC}"
    echo "This will fix the permissions..."
    echo ""

    # Try with sudo
    sudo chown -R $CURRENT_USER:$CURRENT_USER certbot
    sudo chmod -R 755 certbot

    echo -e "${GREEN}✓ Permissions fixed!${NC}"
    echo ""
    echo "Directory owner: $(stat -c '%U' certbot 2>/dev/null || stat -f '%Su' certbot)"
    echo "You can now run: ./init-letsencrypt.sh"

elif [ "$CERTBOT_OWNER" = "$CURRENT_USER" ]; then
    echo -e "${GREEN}✓ Permissions are correct${NC}"
    echo "Directory is owned by $CURRENT_USER"

    # Still fix permissions just in case
    chmod -R 755 certbot
    echo "Ensured directories are readable/writable"

else
    echo -e "${YELLOW}⚠ Directory is owned by: $CERTBOT_OWNER${NC}"
    echo "Fixing permissions..."

    # Try to fix
    if sudo chown -R $CURRENT_USER:$CURRENT_USER certbot 2>/dev/null; then
        sudo chmod -R 755 certbot
        echo -e "${GREEN}✓ Permissions fixed!${NC}"
    else
        echo -e "${RED}Failed to fix permissions${NC}"
        echo "You may need to run: sudo chown -R \$USER:\$USER certbot"
        exit 1
    fi
fi

echo ""
echo -e "${GREEN}All done! ✓${NC}"
