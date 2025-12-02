#!/bin/bash

##############################################################################
# Cloudflare 502 Error Fix Script
#
# This script diagnoses and fixes 502 Bad Gateway errors between Cloudflare
# and the origin server by switching from Let's Encrypt to Cloudflare Origin
# Certificate.
#
# Prerequisites:
# 1. Cloudflare Origin Certificate generated in Cloudflare Dashboard
# 2. Certificate files saved at /var/www/vibetravels/ssl/cloudflare/cert.pem
# 3. Private key saved at /var/www/vibetravels/ssl/cloudflare/key.pem
#
# Usage:
#   Run on production server: ./scripts/fix-cloudflare-502.sh
##############################################################################

set -e

PROJECT_DIR="/var/www/vibetravels"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# Colors for output
RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Cloudflare 502 Error Diagnostic & Fix${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

cd "$PROJECT_DIR" || exit 1

# Step 1: Check if Cloudflare Origin Certificate files exist
echo -e "${YELLOW}[Step 1] Checking for Cloudflare Origin Certificate files...${NC}"
if [ ! -f "ssl/cloudflare/cert.pem" ] || [ ! -f "ssl/cloudflare/key.pem" ]; then
    echo -e "${RED}ERROR: Cloudflare Origin Certificate files not found!${NC}"
    echo ""
    echo "Expected files:"
    echo "  - ssl/cloudflare/cert.pem"
    echo "  - ssl/cloudflare/key.pem"
    echo ""
    echo "Please generate a Cloudflare Origin Certificate:"
    echo "1. Go to: https://dash.cloudflare.com/ → Your domain → SSL/TLS → Origin Server"
    echo "2. Click 'Create Certificate'"
    echo "3. Use default settings (15 year validity, RSA 2048)"
    echo "4. Copy the certificate to ssl/cloudflare/cert.pem"
    echo "5. Copy the private key to ssl/cloudflare/key.pem"
    echo ""
    exit 1
fi

echo -e "${GREEN}✓ Certificate files found${NC}"
echo "  - $(ls -lh ssl/cloudflare/cert.pem)"
echo "  - $(ls -lh ssl/cloudflare/key.pem)"
echo ""

# Step 2: Check current certificate in use
echo -e "${YELLOW}[Step 2] Checking current SSL certificate...${NC}"
CURRENT_CERT=$(docker compose -f docker-compose.production.yml exec -T nginx nginx -T 2>/dev/null | grep "ssl_certificate " | grep -v "ssl_certificate_key" | head -1 | awk '{print $2}' | tr -d ';')
echo "Current certificate: $CURRENT_CERT"

if [[ "$CURRENT_CERT" == *"letsencrypt"* ]]; then
    echo -e "${YELLOW}Currently using Let's Encrypt certificate${NC}"

    # Check if Let's Encrypt cert is expired
    echo "Checking certificate expiry..."
    CERT_EXPIRY=$(docker compose -f docker-compose.production.yml exec -T nginx openssl x509 -in "$CURRENT_CERT" -noout -enddate 2>/dev/null || echo "Unable to check")
    echo "  $CERT_EXPIRY"
elif [[ "$CURRENT_CERT" == *"cloudflare"* ]]; then
    echo -e "${GREEN}Already using Cloudflare Origin Certificate${NC}"
else
    echo -e "${YELLOW}Unknown certificate: $CURRENT_CERT${NC}"
fi
echo ""

# Step 3: Check Nginx error logs for SSL issues
echo -e "${YELLOW}[Step 3] Checking Nginx logs for SSL/certificate errors...${NC}"
SSL_ERRORS=$(docker compose -f docker-compose.production.yml logs nginx --tail=200 2>/dev/null | grep -i "ssl\|certificate\|handshake" | tail -10)
if [ -n "$SSL_ERRORS" ]; then
    echo -e "${RED}SSL-related errors found:${NC}"
    echo "$SSL_ERRORS"
else
    echo -e "${GREEN}✓ No SSL errors in recent logs${NC}"
fi
echo ""

# Step 4: Test current SSL configuration
echo -e "${YELLOW}[Step 4] Testing SSL handshake to origin server...${NC}"
echo "Testing connection to 161.35.66.105:443..."
SSL_TEST=$(echo | openssl s_client -connect 161.35.66.105:443 -servername przem-podroze.pl 2>/dev/null | openssl x509 -noout -subject -issuer -dates 2>/dev/null || echo "SSL test failed")
echo "$SSL_TEST"
echo ""

# Step 5: Ask user if they want to proceed with switching to Cloudflare cert
echo -e "${YELLOW}[Step 5] Ready to switch to Cloudflare Origin Certificate${NC}"
echo ""
echo "This will:"
echo "  1. Update Nginx configuration to use Cloudflare Origin Certificate"
echo "  2. Disable OCSP stapling (not supported for Origin Certificates)"
echo "  3. Restart Nginx container to apply changes"
echo ""
read -p "Proceed with the switch? (y/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}Aborted by user${NC}"
    exit 0
fi

# Step 6: Verify Nginx config syntax before restart
echo -e "${YELLOW}[Step 6] Testing Nginx configuration...${NC}"
docker compose -f docker-compose.production.yml exec -T nginx nginx -t 2>&1
if [ $? -ne 0 ]; then
    echo -e "${RED}ERROR: Nginx configuration test failed!${NC}"
    echo "Please check docker/nginx/production.conf for syntax errors"
    exit 1
fi
echo -e "${GREEN}✓ Nginx configuration is valid${NC}"
echo ""

# Step 7: Restart Nginx to apply new certificate
echo -e "${YELLOW}[Step 7] Restarting Nginx container...${NC}"
docker compose -f docker-compose.production.yml restart nginx

echo "Waiting for Nginx to start..."
sleep 5

# Step 8: Verify Nginx is healthy
echo -e "${YELLOW}[Step 8] Checking Nginx health status...${NC}"
NGINX_STATUS=$(docker compose -f docker-compose.production.yml ps nginx --format json | jq -r '.[0].Health' 2>/dev/null || echo "unknown")
echo "Nginx status: $NGINX_STATUS"

if [ "$NGINX_STATUS" != "healthy" ]; then
    echo -e "${RED}WARNING: Nginx is not healthy${NC}"
    echo "Check logs: docker compose -f docker-compose.production.yml logs nginx"
else
    echo -e "${GREEN}✓ Nginx is healthy${NC}"
fi
echo ""

# Step 9: Verify new certificate is in use
echo -e "${YELLOW}[Step 9] Verifying Cloudflare certificate is active...${NC}"
NEW_CERT=$(docker compose -f docker-compose.production.yml exec -T nginx nginx -T 2>/dev/null | grep "ssl_certificate " | grep -v "ssl_certificate_key" | head -1 | awk '{print $2}' | tr -d ';')
echo "Active certificate: $NEW_CERT"

if [[ "$NEW_CERT" == *"cloudflare"* ]]; then
    echo -e "${GREEN}✓ Successfully switched to Cloudflare Origin Certificate${NC}"
else
    echo -e "${RED}WARNING: Certificate path doesn't match expected Cloudflare path${NC}"
fi
echo ""

# Step 10: Test website accessibility
echo -e "${YELLOW}[Step 10] Testing website accessibility...${NC}"

# Test direct IP (should work)
echo "Testing direct IP access (http://161.35.66.105/)..."
DIRECT_TEST=$(curl -s -o /dev/null -w "%{http_code}" -I http://161.35.66.105/ 2>/dev/null)
echo "  HTTP status: $DIRECT_TEST"

# Test through Cloudflare
echo "Testing through Cloudflare (https://przem-podroze.pl/)..."
SUCCESS_COUNT=0
FAIL_COUNT=0

for i in {1..10}; do
    STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://przem-podroze.pl/ 2>/dev/null)
    if [ "$STATUS" = "200" ] || [ "$STATUS" = "301" ] || [ "$STATUS" = "302" ]; then
        ((SUCCESS_COUNT++))
    else
        ((FAIL_COUNT++))
    fi
    sleep 1
done

echo "  Success: $SUCCESS_COUNT/10"
echo "  Failed:  $FAIL_COUNT/10"

if [ $FAIL_COUNT -gt 0 ]; then
    echo -e "${YELLOW}WARNING: Still experiencing some failures${NC}"
    echo ""
    echo "Additional troubleshooting steps:"
    echo "1. Purge Cloudflare cache: https://dash.cloudflare.com/"
    echo "2. Verify SSL mode is set to 'Full' (not 'Full (strict)')"
    echo "3. Check Cloudflare firewall rules aren't blocking requests"
    echo "4. Wait 5-10 minutes for Cloudflare edge cache to update"
else
    echo -e "${GREEN}✓ All requests successful!${NC}"
fi
echo ""

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Diagnostic Complete${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo "Next steps:"
echo "1. Monitor website: https://przem-podroze.pl/"
echo "2. Check Nginx logs: docker compose -f docker-compose.production.yml logs -f nginx"
echo "3. If 502 errors persist, check Cloudflare dashboard for errors"
echo ""
echo "Cloudflare SSL/TLS settings should be:"
echo "  - SSL/TLS encryption mode: Full"
echo "  - Minimum TLS Version: 1.2"
echo "  - Opportunistic Encryption: On"
echo "  - TLS 1.3: On"
echo "  - Automatic HTTPS Rewrites: On"
echo ""
