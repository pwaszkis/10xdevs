#!/bin/bash

# SSL Certificate Status Checker for VibeTravels
# Checks if SSL certificates exist and shows their status
#
# Usage: ./check-ssl-status.sh

set -e

# Configuration
DOMAIN="vibetravels.com"
CERT_PATH="certbot/conf/live/$DOMAIN"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}╔═══════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║         SSL Certificate Status Check - VibeTravels        ║${NC}"
echo -e "${BLUE}╚═══════════════════════════════════════════════════════════╝${NC}"
echo ""

# Check if on production server
if [ ! -f "docker-compose.production.yml" ]; then
    echo -e "${YELLOW}⚠ Not on production server (docker-compose.production.yml not found)${NC}"
    echo "This script should be run on the production server."
    exit 0
fi

# Check if certificate directories exist
echo -e "${BLUE}Checking certificate directories...${NC}"
if [ -d "$CERT_PATH" ]; then
    echo -e "${GREEN}✓ Certificate directory exists: $CERT_PATH${NC}"
else
    echo -e "${RED}✗ Certificate directory NOT found: $CERT_PATH${NC}"
    echo ""
    echo -e "${YELLOW}Certificates have not been initialized yet.${NC}"
    echo "Run: ./init-letsencrypt.sh to set up SSL certificates"
    exit 1
fi

# Check certificate files
echo ""
echo -e "${BLUE}Checking certificate files...${NC}"

FILES=("fullchain.pem" "privkey.pem" "chain.pem" "cert.pem")
ALL_EXIST=true

for file in "${FILES[@]}"; do
    if [ -f "$CERT_PATH/$file" ]; then
        SIZE=$(stat -f%z "$CERT_PATH/$file" 2>/dev/null || stat -c%s "$CERT_PATH/$file" 2>/dev/null || echo "0")
        echo -e "${GREEN}✓${NC} $file (${SIZE} bytes)"
    else
        echo -e "${RED}✗${NC} $file (missing)"
        ALL_EXIST=false
    fi
done

if [ "$ALL_EXIST" = false ]; then
    echo ""
    echo -e "${RED}Some certificate files are missing!${NC}"
    echo "Run: ./init-letsencrypt.sh to regenerate certificates"
    exit 1
fi

# Get certificate details using openssl
echo ""
echo -e "${BLUE}Certificate details:${NC}"
echo "─────────────────────────────────────────────────────────────"

CERT_INFO=$(openssl x509 -in "$CERT_PATH/fullchain.pem" -noout -text 2>/dev/null)

# Extract and display key information
SUBJECT=$(openssl x509 -in "$CERT_PATH/fullchain.pem" -noout -subject 2>/dev/null | sed 's/subject=//')
ISSUER=$(openssl x509 -in "$CERT_PATH/fullchain.pem" -noout -issuer 2>/dev/null | sed 's/issuer=//')
NOT_BEFORE=$(openssl x509 -in "$CERT_PATH/fullchain.pem" -noout -startdate 2>/dev/null | sed 's/notBefore=//')
NOT_AFTER=$(openssl x509 -in "$CERT_PATH/fullchain.pem" -noout -enddate 2>/dev/null | sed 's/notAfter=//')

echo "Subject:    $SUBJECT"
echo "Issuer:     $ISSUER"
echo "Valid from: $NOT_BEFORE"
echo "Valid to:   $NOT_AFTER"

# Check expiration
EXPIRY_EPOCH=$(date -d "$NOT_AFTER" +%s 2>/dev/null || date -j -f "%b %d %T %Y %Z" "$NOT_AFTER" +%s 2>/dev/null || echo "0")
NOW_EPOCH=$(date +%s)
DAYS_LEFT=$(( ($EXPIRY_EPOCH - $NOW_EPOCH) / 86400 ))

echo ""
if [ $DAYS_LEFT -lt 0 ]; then
    echo -e "${RED}✗ Certificate EXPIRED ${DAYS_LEFT#-} days ago!${NC}"
elif [ $DAYS_LEFT -lt 7 ]; then
    echo -e "${RED}⚠ Certificate expires in $DAYS_LEFT days!${NC}"
elif [ $DAYS_LEFT -lt 30 ]; then
    echo -e "${YELLOW}⚠ Certificate expires in $DAYS_LEFT days${NC}"
else
    echo -e "${GREEN}✓ Certificate valid for $DAYS_LEFT more days${NC}"
fi

# Check if it's a staging certificate
if echo "$ISSUER" | grep -iq "staging"; then
    echo -e "${YELLOW}⚠ This is a STAGING certificate (not trusted by browsers)${NC}"
    echo "Run ./init-letsencrypt.sh with STAGING=0 for production certificates"
fi

# Check SAN (Subject Alternative Names)
echo ""
echo -e "${BLUE}Certificate covers these domains:${NC}"
SAN=$(openssl x509 -in "$CERT_PATH/fullchain.pem" -noout -text 2>/dev/null | grep -A1 "Subject Alternative Name" | tail -n1 | sed 's/DNS://g' | sed 's/,/\n/g' | sed 's/^ *//')
echo "$SAN" | while read -r domain; do
    if [ -n "$domain" ]; then
        echo -e "${GREEN}  ✓${NC} $domain"
    fi
done

# Check certbot container status
echo ""
echo -e "${BLUE}Certbot auto-renewal status:${NC}"
if docker compose -f docker-compose.production.yml ps 2>/dev/null | grep -q "vibetravels-certbot.*Up"; then
    echo -e "${GREEN}✓ Certbot container is running (auto-renewal active)${NC}"

    # Show certbot renewal status
    echo ""
    echo -e "${BLUE}Certbot renewal information:${NC}"
    docker compose -f docker-compose.production.yml run --rm certbot certificates 2>/dev/null || echo "Unable to fetch renewal info"
else
    echo -e "${YELLOW}⚠ Certbot container is NOT running${NC}"
    echo "Auto-renewal is disabled. Start it with:"
    echo "  docker compose -f docker-compose.production.yml --profile production up -d certbot"
fi

# Check Nginx status
echo ""
echo -e "${BLUE}Nginx HTTPS configuration:${NC}"
if docker compose -f docker-compose.production.yml ps 2>/dev/null | grep -q "vibetravels-nginx.*Up"; then
    echo -e "${GREEN}✓ Nginx container is running${NC}"

    # Test HTTPS locally
    if curl -sI https://localhost -k | grep -q "HTTP/"; then
        echo -e "${GREEN}✓ HTTPS is responding locally${NC}"
    else
        echo -e "${YELLOW}⚠ HTTPS might not be configured correctly${NC}"
    fi
else
    echo -e "${RED}✗ Nginx container is NOT running${NC}"
    echo "Start services with: docker compose -f docker-compose.production.yml up -d"
fi

# Test external HTTPS (if domain resolves to this server)
echo ""
echo -e "${BLUE}External HTTPS test:${NC}"
EXTERNAL_TEST=$(curl -sI https://$DOMAIN -m 5 2>&1 || true)
if echo "$EXTERNAL_TEST" | grep -q "HTTP/"; then
    echo -e "${GREEN}✓ HTTPS is accessible externally at https://$DOMAIN${NC}"

    # Check HTTP to HTTPS redirect
    HTTP_TEST=$(curl -sI http://$DOMAIN -m 5 2>&1 || true)
    if echo "$HTTP_TEST" | grep -q "301\|302"; then
        echo -e "${GREEN}✓ HTTP to HTTPS redirect is working${NC}"
    fi
else
    echo -e "${YELLOW}⚠ Unable to test external HTTPS${NC}"
    echo "  This could mean:"
    echo "  - DNS is not pointing to this server yet"
    echo "  - Firewall is blocking port 443"
    echo "  - Site is not publicly accessible"
fi

echo ""
echo -e "${BLUE}╔═══════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                    Check Complete                         ║${NC}"
echo -e "${BLUE}╚═══════════════════════════════════════════════════════════╝${NC}"
echo ""

# Summary and recommendations
if [ $DAYS_LEFT -lt 7 ] && [ $DAYS_LEFT -gt 0 ]; then
    echo -e "${YELLOW}RECOMMENDATION: Certificate expires soon. Ensure certbot auto-renewal is working.${NC}"
elif [ $DAYS_LEFT -lt 0 ]; then
    echo -e "${RED}ACTION REQUIRED: Certificate has expired! Run ./init-letsencrypt.sh${NC}"
else
    echo -e "${GREEN}All checks passed! ✓${NC}"
fi

echo ""
