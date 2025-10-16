#!/bin/bash

# Let's Encrypt SSL Certificate Setup Script for VibeTravels
# This script initializes SSL certificates using Certbot and Let's Encrypt
#
# Usage:
#   1. Run this script ONCE on the production server after initial deployment
#   2. Make sure DNS is pointing to the server before running
#   3. Run as: ./init-letsencrypt.sh

set -e

# Configuration
DOMAINS=(vibetravels.com www.vibetravels.com)
EMAIL="your-email@example.com"  # TODO: Replace with actual email
STAGING=0  # Set to 1 for testing (staging certificates), 0 for production

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}╔═══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   Let's Encrypt SSL Certificate Setup for VibeTravels    ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════════════════════╝${NC}"
echo ""

# Check if running on the server
if [ ! -f "docker-compose.production.yml" ]; then
    echo -e "${RED}Error: docker-compose.production.yml not found!${NC}"
    echo "This script must be run from the project root on the production server."
    exit 1
fi

# Validate email
if [ "$EMAIL" = "your-email@example.com" ]; then
    echo -e "${RED}Error: Please update the EMAIL variable in this script!${NC}"
    echo "Edit the script and replace 'your-email@example.com' with your actual email."
    exit 1
fi

# Check if certificates already exist
if [ -d "certbot/conf/live/${DOMAINS[0]}" ]; then
    echo -e "${YELLOW}⚠ Certificates already exist for ${DOMAINS[0]}${NC}"
    read -p "Do you want to recreate them? This will delete existing certificates. (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Aborted."
        exit 0
    fi
    echo -e "${YELLOW}Removing existing certificates...${NC}"
    rm -rf certbot/conf/live/${DOMAINS[0]}
    rm -rf certbot/conf/archive/${DOMAINS[0]}
    rm -f certbot/conf/renewal/${DOMAINS[0]}.conf
fi

# Create directories
echo -e "${GREEN}✓${NC} Creating directories..."
mkdir -p certbot/conf
mkdir -p certbot/www

# Download recommended TLS parameters if they don't exist
if [ ! -f "certbot/conf/options-ssl-nginx.conf" ] || [ ! -f "certbot/conf/ssl-dhparams.pem" ]; then
    echo -e "${GREEN}✓${NC} Downloading recommended TLS parameters..."
    curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot-nginx/certbot_nginx/_internal/tls_configs/options-ssl-nginx.conf > certbot/conf/options-ssl-nginx.conf
    curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot/certbot/ssl-dhparams.pem > certbot/conf/ssl-dhparams.pem
fi

# Create dummy certificate for initial Nginx start
echo -e "${GREEN}✓${NC} Creating dummy certificate for ${DOMAINS[0]}..."
CERT_PATH="certbot/conf/live/${DOMAINS[0]}"
mkdir -p "$CERT_PATH"

openssl req -x509 -nodes -newkey rsa:4096 -days 1 \
    -keyout "$CERT_PATH/privkey.pem" \
    -out "$CERT_PATH/fullchain.pem" \
    -subj "/CN=localhost" > /dev/null 2>&1

# Create dummy chain.pem for OCSP stapling
cp "$CERT_PATH/fullchain.pem" "$CERT_PATH/chain.pem"

echo -e "${GREEN}✓${NC} Dummy certificate created"

# Start Nginx with dummy certificate
echo -e "${GREEN}✓${NC} Starting Nginx with dummy certificate..."
docker compose -f docker-compose.production.yml up -d nginx

echo -e "${GREEN}✓${NC} Waiting for Nginx to be ready..."
sleep 5

# Check if Nginx is running
if ! docker compose -f docker-compose.production.yml ps | grep -q "vibetravels-nginx.*Up"; then
    echo -e "${RED}Error: Nginx failed to start!${NC}"
    echo "Check logs with: docker compose -f docker-compose.production.yml logs nginx"
    exit 1
fi

# Test HTTP endpoint
echo -e "${GREEN}✓${NC} Testing HTTP endpoint..."
if ! curl -f http://localhost/.well-known/acme-challenge/ > /dev/null 2>&1; then
    echo -e "${YELLOW}Warning: ACME challenge endpoint might not be accessible${NC}"
fi

# Delete dummy certificate
echo -e "${GREEN}✓${NC} Deleting dummy certificate..."
docker compose -f docker-compose.production.yml run --rm --entrypoint "\
    rm -rf /etc/letsencrypt/live/${DOMAINS[0]} && \
    rm -rf /etc/letsencrypt/archive/${DOMAINS[0]} && \
    rm -rf /etc/letsencrypt/renewal/${DOMAINS[0]}.conf" certbot

# Request real certificate
echo -e "${GREEN}✓${NC} Requesting Let's Encrypt certificate..."

# Build domain arguments
DOMAIN_ARGS=""
for domain in "${DOMAINS[@]}"; do
    DOMAIN_ARGS="$DOMAIN_ARGS -d $domain"
done

# Staging or production
if [ $STAGING != "0" ]; then
    echo -e "${YELLOW}⚠ Using Let's Encrypt STAGING server (test certificates)${NC}"
    STAGING_ARG="--staging"
else
    echo -e "${GREEN}Using Let's Encrypt PRODUCTION server (real certificates)${NC}"
    STAGING_ARG=""
fi

# Run certbot
docker compose -f docker-compose.production.yml run --rm certbot certonly \
    --webroot \
    --webroot-path=/var/www/certbot \
    $STAGING_ARG \
    --email $EMAIL \
    --agree-tos \
    --no-eff-email \
    --force-renewal \
    $DOMAIN_ARGS

# Check if certificate was created
if [ ! -f "certbot/conf/live/${DOMAINS[0]}/fullchain.pem" ]; then
    echo -e "${RED}Error: Certificate creation failed!${NC}"
    echo "Check logs above for details."
    exit 1
fi

# Reload Nginx to use real certificate
echo -e "${GREEN}✓${NC} Reloading Nginx with real certificate..."
docker compose -f docker-compose.production.yml exec nginx nginx -s reload

# Start certbot for auto-renewal
echo -e "${GREEN}✓${NC} Starting Certbot auto-renewal service..."
docker compose -f docker-compose.production.yml --profile production up -d certbot

echo ""
echo -e "${GREEN}╔═══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║              SSL Certificate Setup Complete! ✓            ║${NC}"
echo -e "${GREEN}╚═══════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${GREEN}Certificate details:${NC}"
docker compose -f docker-compose.production.yml run --rm certbot certificates

echo ""
echo -e "${GREEN}Next steps:${NC}"
echo "1. Test your site: https://${DOMAINS[0]}"
echo "2. Check SSL rating: https://www.ssllabs.com/ssltest/analyze.html?d=${DOMAINS[0]}"
echo "3. Certificates will auto-renew every 12 hours via the certbot container"
echo ""
echo -e "${YELLOW}Note: If you used staging certificates (STAGING=1), they won't be trusted by browsers.${NC}"
echo -e "${YELLOW}Run this script again with STAGING=0 to get real certificates.${NC}"
echo ""
