# SSL Certificate Setup - Quick Reference

> Quick guide for setting up Let's Encrypt SSL certificates for VibeTravels production server

## 📋 Prerequisites Checklist

Before starting, ensure:

- ✅ DNS is pointing to your server (verify with `dig vibetravels.com +short`)
- ✅ Ports 80 and 443 are open (`sudo ufw allow 80/tcp && sudo ufw allow 443/tcp`)
- ✅ Docker containers are running (`docker compose -f docker-compose.production.yml ps`)
- ✅ Application is accessible via HTTP (`curl http://vibetravels.com`)

---

## 🚀 Quick Setup (3 Steps)

### Step 1: Check Certificate Status

```bash
cd /var/www/vibetravels
./check-ssl-status.sh
```

**If certificates exist and are valid** → You're done! ✅

**If certificates don't exist** → Continue to Step 2

---

### Step 2: Configure Email

Edit `init-letsencrypt.sh` and update the email address:

```bash
nano init-letsencrypt.sh

# Change:
EMAIL="your-email@example.com"
# To:
EMAIL="hello@vibetravels.com"

# Save: Ctrl+X, Y, Enter
```

---

### Step 3: Initialize Certificates

```bash
# Run initialization script
./init-letsencrypt.sh

# Wait 2-3 minutes for completion
# Script will:
# - Create dummy certificate
# - Request real certificate from Let's Encrypt
# - Configure auto-renewal
```

---

## ✅ Verification

After setup, verify everything works:

```bash
# 1. Check certificate status
./check-ssl-status.sh

# 2. Test HTTPS in browser
# Visit: https://vibetravels.com
# Should show green padlock 🔒

# 3. Verify auto-renewal is running
docker compose -f docker-compose.production.yml ps | grep certbot
# Should show: vibetravels-certbot   running
```

---

## 🔄 Automatic Renewal

Certificates are automatically renewed:

- **Certbot container** checks every 12 hours
- **Renews** 30 days before expiration
- **Nginx reloads** every 6 hours to use new certificates

No manual action needed! ✅

---

## 🛠️ Common Commands

### Check Status
```bash
./check-ssl-status.sh
```

### View Certbot Logs
```bash
docker compose -f docker-compose.production.yml logs certbot
```

### Manual Renewal (if needed)
```bash
docker compose -f docker-compose.production.yml run --rm certbot renew
docker compose -f docker-compose.production.yml exec nginx nginx -s reload
```

### Test Renewal (dry run)
```bash
docker compose -f docker-compose.production.yml run --rm certbot renew --dry-run
```

### Restart Certbot Container
```bash
docker compose -f docker-compose.production.yml restart certbot
```

---

## 🐛 Troubleshooting

### Problem: "Permission denied" when creating certificates

**Error message:**
```
✓ Downloading recommended TLS parameters...
./init-letsencrypt.sh: line 66: certbot/conf/options-ssl-nginx.conf: Permission denied
```

**Solution:**
```bash
# Fix permissions
./fix-ssl-permissions.sh

# Or manually:
sudo chown -R $USER:$USER certbot
chmod -R 755 certbot

# Then run setup again
./init-letsencrypt.sh
```

**Why this happens:**
- Docker containers may have created certbot directories as root
- Previous failed attempts may have left incorrect ownership

---

### Problem: "DNS not pointing to server"

**Solution:**
```bash
# Check DNS
dig vibetravels.com +short

# Should return your server IP
# If not, wait 1-2 hours for DNS propagation
```

---

### Problem: "Port 80 not accessible"

**Solution:**
```bash
# Check firewall
sudo ufw status
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Check if Nginx is listening
curl -I http://vibetravels.com
```

---

### Problem: "Certificate request failed"

**Solution:**
```bash
# View detailed logs
docker compose -f docker-compose.production.yml logs certbot

# Common causes:
# - DNS not propagated yet → wait 1-2 hours
# - Port 80 blocked → check firewall
# - Rate limit hit → use STAGING=1 in script

# Test ACME challenge endpoint
curl http://vibetravels.com/.well-known/acme-challenge/
# Should return 404 (not connection error)
```

---

### Problem: "Certbot container not running"

**Solution:**
```bash
# Start certbot with production profile
docker compose -f docker-compose.production.yml --profile production up -d certbot

# Check status
docker compose -f docker-compose.production.yml ps certbot
```

---

### Problem: "Certificate expired"

**Solution:**
```bash
# Force renewal
docker compose -f docker-compose.production.yml run --rm certbot renew --force-renewal

# Reload Nginx
docker compose -f docker-compose.production.yml exec nginx nginx -s reload

# Verify
./check-ssl-status.sh
```

---

## 🧪 Testing with Staging Certificates

To test SSL setup without hitting rate limits:

```bash
# Edit script
nano init-letsencrypt.sh

# Change STAGING=0 to STAGING=1
STAGING=1

# Run script
./init-letsencrypt.sh

# This creates test certificates (not trusted by browsers)
# Good for testing, then run again with STAGING=0
```

**Let's Encrypt Rate Limits:**
- 50 certificates per domain per week
- 5 duplicate certificates per week
- Use staging for testing (no rate limits)

---

## 📂 Certificate Files

Certificates are stored in: `certbot/conf/live/vibetravels.com/`

```
certbot/conf/live/vibetravels.com/
├── fullchain.pem     # Full certificate chain (Nginx uses this)
├── privkey.pem       # Private key (Nginx uses this)
├── cert.pem          # Certificate only
└── chain.pem         # Intermediate certificates (OCSP stapling)
```

These are automatically mounted in Nginx at `/etc/letsencrypt/`.

---

## 🔐 Security Configuration

Nginx is configured with:

- ✅ TLS 1.2 and 1.3 only
- ✅ Strong cipher suites (forward secrecy)
- ✅ HSTS (HTTP Strict Transport Security)
- ✅ OCSP stapling
- ✅ Security headers (XSS, clickjacking protection)

**Test your SSL:**
- https://www.ssllabs.com/ssltest/ → Should get **A or A+**
- https://securityheaders.com/ → Should get **A**

---

## 📝 Cloudflare Configuration

After SSL setup, configure Cloudflare:

1. Go to Cloudflare Dashboard → SSL/TLS → Overview
2. Set encryption mode: **Full (strict)** ✅
3. Enable **Always Use HTTPS**
4. Enable **HSTS** (after testing)

---

## 📞 Support

For detailed documentation, see:
- **Full deployment guide**: `DEPLOYMENT.md` (section: SSL Certificate Setup)
- **Docker compose config**: `docker-compose.production.yml`
- **Nginx config**: `docker/nginx/production.conf`

For issues:
- Check logs: `docker compose -f docker-compose.production.yml logs certbot`
- Run status check: `./check-ssl-status.sh`
- Review: https://letsencrypt.org/docs/

---

## 🎯 Quick Commands Summary

```bash
# Check if certificates exist
./check-ssl-status.sh

# Setup certificates (first time)
./init-letsencrypt.sh

# Manual renewal
docker compose -f docker-compose.production.yml run --rm certbot renew

# View logs
docker compose -f docker-compose.production.yml logs certbot

# Restart Nginx
docker compose -f docker-compose.production.yml restart nginx

# Test renewal
docker compose -f docker-compose.production.yml run --rm certbot renew --dry-run
```

---

**That's it! Your SSL should be working now. 🎉**

For questions, refer to `DEPLOYMENT.md` or open an issue in the repository.
