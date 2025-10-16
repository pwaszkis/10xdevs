# VibeTravels - Production Deployment Guide

> Complete guide for deploying VibeTravels MVP to production using DigitalOcean, Docker, and GitHub Actions CI/CD

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Cost Estimation](#cost-estimation)
- [Prerequisites](#prerequisites)
- [Infrastructure Setup](#infrastructure-setup)
  - [1. Domain Purchase (OVH)](#1-domain-purchase-ovh)
  - [2. DigitalOcean Droplet](#2-digitalocean-droplet)
  - [3. Cloudflare DNS Setup](#3-cloudflare-dns-setup)
- [Server Configuration](#server-configuration)
  - [Initial Server Setup](#initial-server-setup)
  - [Docker Installation](#docker-installation)
  - [SSL Certificate](#ssl-certificate)
- [Application Deployment](#application-deployment)
  - [Environment Configuration](#environment-configuration)
  - [Docker Compose Production](#docker-compose-production)
  - [First Deployment](#first-deployment)
- [CI/CD Pipeline](#cicd-pipeline)
  - [GitHub Secrets](#github-secrets)
  - [GitHub Actions Workflow](#github-actions-workflow)
- [Service Configuration](#service-configuration)
  - [Laravel Queue Workers](#laravel-queue-workers)
  - [Laravel Scheduler (Cron)](#laravel-scheduler-cron)
  - [Google OAuth](#google-oauth)
  - [Mailgun Email Service](#mailgun-email-service)
- [Monitoring & Maintenance](#monitoring--maintenance)
- [Backup Strategy](#backup-strategy)
- [Troubleshooting](#troubleshooting)

---

## Architecture Overview

**Production Stack:**
```
┌─────────────────────────────────────────────────────┐
│  Cloudflare (DNS + CDN + DDoS Protection)          │
└────────────────────┬────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────┐
│  DigitalOcean Droplet (Ubuntu 24.04 LTS)           │
│  ┌──────────────────────────────────────────────┐  │
│  │  Docker Compose Environment                   │  │
│  │  ┌────────────┐  ┌─────────┐  ┌──────────┐  │  │
│  │  │ Nginx      │  │ MySQL 8 │  │ Redis 7  │  │  │
│  │  │ (SSL)      │  │         │  │          │  │  │
│  │  └─────┬──────┘  └─────────┘  └────┬─────┘  │  │
│  │        │                            │        │  │
│  │  ┌─────▼──────────────────────┬────▼─────┐  │  │
│  │  │ Laravel 11 App             │          │  │  │
│  │  │ PHP 8.3 + Supervisor       │  Queue   │  │  │
│  │  │ (workers + scheduler)      │  Worker  │  │  │
│  │  └────────────────────────────┴──────────┘  │  │
│  └──────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
         │                    │
         ▼                    ▼
   ┌──────────┐      ┌────────────────┐
   │ Mailgun  │      │ OpenAI API     │
   │ (EU)     │      │ (GPT-4o-mini)  │
   └──────────┘      └────────────────┘
```

**Key Design Decisions:**
- **Single Droplet Setup**: All services run on one DigitalOcean droplet to minimize costs (~$12/month)
- **Docker Compose**: Containerized services for easy deployment and consistency
- **Cloudflare DNS**: Free tier provides DNS, CDN, SSL, and DDoS protection
- **Managed Services**: Using third-party managed services (Mailgun, OpenAI) instead of self-hosting
- **GitHub Actions**: Automated CI/CD pipeline for testing and deployment

---

## Cost Estimation

### Monthly Costs Breakdown

| Service | Plan | Monthly Cost | Notes |
|---------|------|--------------|-------|
| **OVH Domain** | .com or .pl | ~$1 | $12/year amortized |
| **DigitalOcean Droplet** | Basic 2GB RAM | $12 | 2GB RAM, 1 vCPU, 50GB SSD, 2TB transfer |
| **DigitalOcean Backups** | Automated backups | $2.40 | 20% of droplet cost |
| **Cloudflare DNS** | Free tier | $0 | DNS + CDN + SSL + cache |
| **SendGrid** | Free tier | $0 | 100 emails/day (3,000/month) - sufficient for MVP |
| **OpenAI API** | Pay-as-you-go | $3-30 | GPT-4o-mini: ~$0.02-0.05/plan × 5-20/day |
| **GitHub** | Public repo | $0 | Actions: 2,000 min/month free |
| | | |
| **TOTAL** | | **$18-47** | Scales with AI usage |

### Cost Optimization Tips

1. **Start with Basic Droplet ($6/mo)**: If traffic is low initially, downgrade to 1GB RAM droplet
2. **Monitor AI Usage**: Use caching and mock responses in testing to avoid unnecessary API calls
3. **Cloudflare Free Tier**: Provides CDN caching which reduces bandwidth on droplet
4. **Email Provider - SendGrid (darmowe)**:
   - SendGrid: 100 emails/day free (3,000/month) ✅ ZALECANE
   - Resend: 3,000 emails/month free
   - SMTP2GO: 1,000 emails/month free
   - ⚠️ Mailgun usunął darmowy plan (najtańszy €14/msc)
5. **Reserved Instances**: After 3 months, consider DigitalOcean reserved instances (16% savings for 1-year commit)

### Scaling Costs (Future)

When traffic grows beyond MVP scale:
- **$24/mo Droplet** (4GB RAM) - supports 1,000-2,000 users
- **Managed MySQL** ($15/mo) - when database becomes bottleneck
- **Managed Redis** ($15/mo) - for high-traffic queue/cache workloads
- **Load Balancer** ($12/mo) - for multi-instance setup

---

## Prerequisites

Before starting deployment, ensure you have:

- [x] GitHub account with repository access
- [x] OVH account for domain purchase
- [x] DigitalOcean account (sign up at https://www.digitalocean.com)
- [x] Cloudflare account (sign up at https://www.cloudflare.com)
- [x] SendGrid account (sign up at https://signup.sendgrid.com) - darmowe 100 emails/day
- [x] Google Cloud Console project (for OAuth)
- [x] OpenAI API key (from https://platform.openai.com)
- [x] SSH key pair for server access
- [x] Credit card for service payments

---

## Infrastructure Setup

### 1. Domain Purchase (OVH)

**Step 1: Purchase Domain**
1. Go to https://www.ovh.com/world/domains/
2. Search for desired domain (e.g., `vibetravels.com`)
3. Complete purchase (~$12/year for .com, ~$8/year for .pl)
4. **Do not configure DNS at OVH** - we'll use Cloudflare

**Recommended domain names:**
- `vibetravels.com` (primary)
- `vibetravels.pl` (if targeting Polish market)
- `vibe-travels.com` (alternative)

**Cost Comparison:**
- `.com` - $11.99/year
- `.pl` - $7.99/year
- `.io` - $39.99/year (avoid for MVP)
- `.app` - $19.99/year (requires HTTPS)

---

### 2. DigitalOcean Droplet

**Step 1: Create Droplet**

1. Log in to DigitalOcean Dashboard
2. Click **Create → Droplets**
3. Configure droplet:
   ```
   Distribution:     Ubuntu 24.04 LTS x64
   Plan:             Basic
   CPU Options:      Regular - $12/mo (2GB RAM, 1 vCPU, 50GB SSD)
   Datacenter:       Frankfurt (closest to Poland/Europe)
   Authentication:   SSH Key (upload your public key)
   Hostname:         vibetravels-prod
   Backups:          ✅ Enable ($2.40/mo - highly recommended)
   Monitoring:       ✅ Enable (free)
   ```

4. Click **Create Droplet**
5. Note the assigned IP address (e.g., `159.89.123.45`)

**Why Frankfurt datacenter?**
- Lowest latency for European users
- GDPR compliant (data stays in EU)
- Similar pricing to other regions

**Droplet sizing guide:**
- **1GB RAM ($6/mo)**: 50-100 concurrent users, testing phase
- **2GB RAM ($12/mo)**: 100-500 concurrent users, MVP launch ✅ **RECOMMENDED**
- **4GB RAM ($24/mo)**: 500-2,000 concurrent users, growth phase

---

### 3. Cloudflare DNS Setup

**Why Cloudflare instead of OVH DNS?**
- Free CDN and DDoS protection
- Better performance with global anycast network
- Free SSL certificates
- Cache static assets (reduces bandwidth)
- Easy DNS management UI

**Step 1: Add Site to Cloudflare**

1. Log in to Cloudflare Dashboard
2. Click **Add a Site**
3. Enter your domain (e.g., `vibetravels.com`)
4. Select **Free Plan**
5. Cloudflare will scan existing DNS records

**Step 2: Configure DNS Records**

Add these **basic records** (email DNS records będą dodane później przy konfiguracji SendGrid):

| Type | Name | Content | Proxy Status | TTL |
|------|------|---------|--------------|-----|
| A | @ | `YOUR_DROPLET_IP` | ✅ Proxied | Auto |
| A | www | `YOUR_DROPLET_IP` | ✅ Proxied | Auto |

Example:
```
A     @      159.89.123.45   [Proxied]
A     www    159.89.123.45   [Proxied]
```

**Note:** SendGrid DNS records (CNAME dla domain authentication) zostaną dodane później w sekcji [Email Service Setup](#email-service-setup).

**Step 3: Update Nameservers at OVH**

1. Go to OVH Dashboard → Domain Management
2. Find your domain → DNS Management
3. Change nameservers to Cloudflare nameservers (provided by Cloudflare):
   ```
   ns1.cloudflare.com
   ns2.cloudflare.com
   ```
4. Save changes
5. Wait 24-48 hours for propagation (usually completes in 1-2 hours)

**Step 4: Configure Cloudflare Settings**

In Cloudflare Dashboard:

1. **SSL/TLS → Overview**
   - Encryption mode: **Full (strict)** ✅

2. **SSL/TLS → Edge Certificates**
   - ✅ Always Use HTTPS: **On**
   - ✅ HTTP Strict Transport Security (HSTS): **Enable** (after testing SSL)
   - ✅ Minimum TLS Version: **TLS 1.2**

3. **Speed → Optimization**
   - ✅ Auto Minify: Check CSS, JS, HTML
   - ✅ Brotli: **On**

4. **Caching → Configuration**
   - Caching Level: **Standard**
   - Browser Cache TTL: **4 hours**

---

## Server Configuration

### Initial Server Setup

**Step 1: Connect to Server**

```bash
# SSH into droplet (replace with your IP)
ssh root@YOUR_DROPLET_IP

# Update system packages
apt update && apt upgrade -y

# Set timezone
timedatectl set-timezone Europe/Warsaw

# Create non-root user for deployment
adduser deploy
usermod -aG sudo deploy

# Copy SSH key to deploy user
mkdir -p /home/deploy/.ssh
cp ~/.ssh/authorized_keys /home/deploy/.ssh/
chown -R deploy:deploy /home/deploy/.ssh
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys

# Test login as deploy user (from your local machine)
ssh deploy@YOUR_DROPLET_IP
```

**Step 2: Secure Server**

```bash
# Install firewall
sudo apt install ufw -y

# Allow SSH, HTTP, HTTPS
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall
sudo ufw --force enable
sudo ufw status

# Disable root SSH login (security best practice)
sudo nano /etc/ssh/sshd_config
# Set: PermitRootLogin no
# Set: PasswordAuthentication no
sudo systemctl restart ssh

# Install fail2ban (prevent brute force attacks)
sudo apt install fail2ban -y
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

---

### Docker Installation

**Step 1: Install Docker**

```bash
# Install prerequisites
sudo apt install apt-transport-https ca-certificates curl software-properties-common -y

# Add Docker GPG key
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Add Docker repository
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Install Docker
sudo apt update
sudo apt install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin -y

# Add deploy user to docker group
sudo usermod -aG docker deploy
newgrp docker

# Verify installation
docker --version
docker compose version

# Enable Docker to start on boot
sudo systemctl enable docker
```

**Step 2: Configure Docker**

```bash
# Create directory for application
sudo mkdir -p /var/www/vibetravels
sudo chown -R deploy:deploy /var/www/vibetravels
cd /var/www/vibetravels
```

---

### SSL Certificate Setup

We use **Let's Encrypt** for free SSL certificates with automatic renewal via Docker Certbot container.

#### Prerequisites

Before setting up SSL:
- ✅ DNS must be pointing to your server (wait 1-2 hours after DNS change)
- ✅ Ports 80 and 443 must be open in firewall
- ✅ Nginx container must be running
- ✅ Domain must be accessible via HTTP first

#### Check if Certificates Already Exist

**On the production server**, run the certificate status checker:

```bash
cd /var/www/vibetravels
./check-ssl-status.sh
```

This script will:
- ✅ Check if certificates exist
- ✅ Show certificate expiration date
- ✅ Verify Certbot auto-renewal is running
- ✅ Test HTTPS configuration
- ⚠️ Warn if certificates are expiring soon

**If certificates exist and are valid**, you're done! Skip to the next section.

**If certificates don't exist**, continue with the setup below.

---

#### Initial SSL Certificate Setup

**Step 1: Verify DNS is Working**

```bash
# From your local machine or server
dig vibetravels.com +short
# Should return your server IP

# Test HTTP access (before HTTPS)
curl -I http://vibetravels.com
# Should return 200 or 301 (redirect)
```

**Step 2: Update Email in Script**

Edit the initialization script to add your email:

```bash
cd /var/www/vibetravels
nano init-letsencrypt.sh

# Change this line:
EMAIL="your-email@example.com"
# To your actual email:
EMAIL="hello@vibetravels.com"

# Save and exit (Ctrl+X, Y, Enter)
```

**Step 3: Run Certificate Initialization**

```bash
# Make script executable (if not already)
chmod +x init-letsencrypt.sh

# Run the script
./init-letsencrypt.sh
```

**What the script does:**

1. Creates necessary directories (`certbot/conf`, `certbot/www`)
2. Downloads recommended TLS parameters
3. Creates temporary dummy certificate
4. Starts Nginx with dummy cert
5. Requests real certificate from Let's Encrypt
6. Replaces dummy cert with real cert
7. Reloads Nginx with real certificate
8. Starts Certbot auto-renewal service

**Step 4: Verify Certificate**

```bash
# Check certificate was created
./check-ssl-status.sh

# Test HTTPS in browser
# Visit: https://vibetravels.com
# Should show green padlock 🔒

# Test SSL rating (optional)
# Visit: https://www.ssllabs.com/ssltest/analyze.html?d=vibetravels.com
# Should get A or A+ rating
```

---

#### Testing with Staging Certificates (Optional)

If you want to test the SSL setup without hitting Let's Encrypt rate limits:

```bash
# Edit init-letsencrypt.sh
nano init-letsencrypt.sh

# Change:
STAGING=0
# To:
STAGING=1

# Run the script
./init-letsencrypt.sh

# This creates test certificates (not trusted by browsers)
# Good for testing the automation, then run again with STAGING=0
```

**Let's Encrypt Rate Limits:**
- 50 certificates per domain per week
- 5 duplicate certificates per week
- Staging server has no rate limits (use for testing)

---

#### Automatic Certificate Renewal

Certificates are automatically renewed by the `certbot` container.

**How it works:**
- Certbot container runs in background (`docker-compose.production.yml`)
- Checks for renewal every 12 hours
- Renews certificates 30 days before expiration
- Nginx automatically reloads every 6 hours to pick up new certs

**Verify auto-renewal is running:**

```bash
# Check certbot container status
docker compose -f docker-compose.production.yml ps | grep certbot

# Should show:
# vibetravels-certbot   running

# View certbot logs
docker compose -f docker-compose.production.yml logs certbot

# Test renewal (dry run)
docker compose -f docker-compose.production.yml run --rm certbot renew --dry-run
```

**If certbot container is not running:**

```bash
# Start certbot with production profile
docker compose -f docker-compose.production.yml --profile production up -d certbot

# Verify it started
docker compose -f docker-compose.production.yml ps certbot
```

---

#### Manual Certificate Renewal

If automatic renewal fails or you need to renew manually:

```bash
# Renew certificate
docker compose -f docker-compose.production.yml run --rm certbot renew

# Reload Nginx
docker compose -f docker-compose.production.yml exec nginx nginx -s reload

# Check status
./check-ssl-status.sh
```

---

#### Troubleshooting SSL Issues

**Problem: DNS not propagating**

```bash
# Check DNS from different locations
dig vibetravels.com @8.8.8.8 +short
dig vibetravels.com @1.1.1.1 +short

# If different IPs, wait 1-2 hours
```

**Problem: Port 80 not accessible**

```bash
# Check firewall
sudo ufw status
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Check if port is listening
sudo netstat -tulpn | grep :80
```

**Problem: Certificate request failed**

```bash
# View detailed logs
docker compose -f docker-compose.production.yml logs certbot

# Common issues:
# - DNS not pointing to server
# - Port 80 blocked
# - Rate limit hit (use STAGING=1)
# - Domain not accessible via HTTP

# Test ACME challenge endpoint
curl http://vibetravels.com/.well-known/acme-challenge/test
# Should return 404 (not error)
```

**Problem: Certificate expired**

```bash
# Force renewal
docker compose -f docker-compose.production.yml run --rm certbot renew --force-renewal

# Restart Nginx
docker compose -f docker-compose.production.yml restart nginx
```

---

#### Certificate Files Location

Certificates are stored in `certbot/conf/live/vibetravels.com/`:

```
certbot/conf/live/vibetravels.com/
├── fullchain.pem    # Full certificate chain (used by Nginx)
├── privkey.pem      # Private key (used by Nginx)
├── cert.pem         # Certificate only
└── chain.pem        # Chain only (for OCSP stapling)
```

These are mounted in the Nginx container at `/etc/letsencrypt/`.

---

#### Security Best Practices

The Nginx configuration includes:

- ✅ **TLS 1.2 and 1.3 only** (no SSL, no TLS 1.0/1.1)
- ✅ **Strong cipher suites** (forward secrecy)
- ✅ **HSTS enabled** (HTTP Strict Transport Security)
- ✅ **OCSP stapling** (faster certificate validation)
- ✅ **Security headers** (XSS, clickjacking protection)

Test your SSL configuration:
- https://www.ssllabs.com/ssltest/ (should get A or A+)
- https://securityheaders.com/ (should get A)

---

**Note**: After SSL setup, update Cloudflare SSL mode to **Full (strict)** in the dashboard.

---

## Application Deployment

### Environment Configuration

**Step 1: Prepare .env File**

On the server, create production `.env` file:

```bash
cd /var/www/vibetravels

# Create .env file (never commit this to git!)
nano .env
```

**Production .env template:**

```env
# Application
APP_NAME="VibeTravels"
APP_ENV=production
APP_KEY=base64:GENERATE_THIS_WITH_php_artisan_key:generate
APP_DEBUG=false
APP_URL=https://vibetravels.com

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=vibetravels
DB_USERNAME=vibetravels
DB_PASSWORD=GENERATE_STRONG_PASSWORD_HERE

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Mail (Mailgun EU)
MAIL_MAILER=mailgun
MAIL_FROM_ADDRESS=hello@vibetravels.com
MAIL_FROM_NAME="VibeTravels"
MAILGUN_DOMAIN=vibetravels.com
MAILGUN_SECRET=key-YOUR_MAILGUN_API_KEY
MAILGUN_ENDPOINT=api.eu.mailgun.net

# Google OAuth
GOOGLE_CLIENT_ID=YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=YOUR_GOOGLE_CLIENT_SECRET
GOOGLE_REDIRECT_URI=https://vibetravels.com/auth/google/callback

# OpenAI API
AI_USE_REAL_API=true
OPENAI_API_KEY=sk-YOUR_OPENAI_API_KEY
OPENAI_MODEL=gpt-4o-mini

# Logging
LOG_CHANNEL=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=14

# Session
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# Security
SANCTUM_STATEFUL_DOMAINS=vibetravels.com,www.vibetravels.com
```

**Step 2: Generate Secure Values**

```bash
# Generate APP_KEY (run this after first deployment)
docker compose exec app php artisan key:generate --show

# Generate strong DB password
openssl rand -base64 32

# Save these values in GitHub Secrets for CI/CD
```

---

### Docker Compose Production

The production configuration is in `docker-compose.production.yml` (created in next step).

Key differences from development:
- No Xdebug
- No MailHog (using real Mailgun)
- SSL/TLS enabled
- Optimized PHP settings
- Queue worker runs continuously
- Scheduler runs via cron
- Health checks enabled
- Restart policies set

---

### First Deployment

**Step 1: Clone Repository**

```bash
cd /var/www/vibetravels

# Clone repository (if starting fresh)
git clone https://github.com/YOUR_USERNAME/vibetravels.git .

# Or pull latest changes
git pull origin main
```

**Step 2: Build and Start Services**

```bash
# Build production images
docker compose -f docker-compose.production.yml build

# Start services
docker compose -f docker-compose.production.yml up -d

# Check status
docker compose -f docker-compose.production.yml ps

# View logs
docker compose -f docker-compose.production.yml logs -f app
```

**Step 3: Install Dependencies**

```bash
# Install PHP dependencies
docker compose -f docker-compose.production.yml exec app composer install --optimize-autoloader --no-dev

# Generate application key (if not in .env yet)
docker compose -f docker-compose.production.yml exec app php artisan key:generate

# Clear and cache config
docker compose -f docker-compose.production.yml exec app php artisan config:cache
docker compose -f docker-compose.production.yml exec app php artisan route:cache
docker compose -f docker-compose.production.yml exec app php artisan view:cache
```

**Step 4: Run Migrations**

```bash
# Run database migrations
docker compose -f docker-compose.production.yml exec app php artisan migrate --force

# (Optional) Seed initial data
docker compose -f docker-compose.production.yml exec app php artisan db:seed --force
```

**Step 5: Build Frontend Assets**

```bash
# Install Node dependencies
docker compose -f docker-compose.production.yml run --rm node npm ci

# Build production assets
docker compose -f docker-compose.production.yml run --rm node npm run build

# Assets will be in public/build/
```

**Step 6: Set Permissions**

```bash
# Fix storage permissions
docker compose -f docker-compose.production.yml exec app chmod -R 775 storage bootstrap/cache
docker compose -f docker-compose.production.yml exec app chown -R www-data:www-data storage bootstrap/cache
```

**Step 7: Verify Deployment**

```bash
# Check application health
curl -I https://vibetravels.com

# Test database connection
docker compose -f docker-compose.production.yml exec app php artisan tinker
>>> DB::connection()->getPdo();

# Test queue
docker compose -f docker-compose.production.yml exec app php artisan queue:work --once

# Check logs for errors
docker compose -f docker-compose.production.yml logs --tail=100 app
```

---

## CI/CD Pipeline

### GitHub Secrets

**Step 1: Add Deployment Secrets**

In GitHub repository, go to **Settings → Secrets and variables → Actions → New repository secret**

Add these secrets:

| Secret Name | Description | Example Value |
|-------------|-------------|---------------|
| `SSH_PRIVATE_KEY` | Private SSH key for deploy user | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `SERVER_HOST` | Droplet IP address | `159.89.123.45` |
| `SERVER_USER` | Deployment user | `deploy` |
| `APP_KEY` | Laravel application key | `base64:...` |
| `DB_PASSWORD` | MySQL root password | `your_secure_password` |
| `GOOGLE_CLIENT_ID` | Google OAuth client ID | `123456.apps.googleusercontent.com` |
| `GOOGLE_CLIENT_SECRET` | Google OAuth secret | `GOCSPX-...` |
| `OPENAI_API_KEY` | OpenAI API key | `sk-...` |
| `MAILGUN_SECRET` | Mailgun API key | `key-...` |

**Step 2: Configure GitHub Actions**

The workflow file `.github/workflows/deploy.yml` is created in the next task.

**Workflow triggers:**
- ✅ **Push to main branch** - automatic deployment
- ✅ **Manual dispatch** - deploy on demand via GitHub UI

**Workflow steps:**
1. Checkout code
2. Run tests (PHPUnit)
3. Run static analysis (PHPStan)
4. Check code style (Laravel Pint)
5. Build Docker image
6. Push to GitHub Container Registry
7. SSH to server and deploy
8. Run migrations
9. Clear/cache config
10. Restart services

---

### GitHub Actions Workflow

See `.github/workflows/deploy.yml` (will be created in next step).

**Manual deployment trigger:**

```bash
# In GitHub UI: Actions → Deploy to Production → Run workflow → Run workflow
```

**Rollback procedure:**

```bash
# SSH to server
ssh deploy@YOUR_DROPLET_IP

# Check recent commits
cd /var/www/vibetravels
git log --oneline -10

# Rollback to specific commit
git checkout COMMIT_HASH
docker compose -f docker-compose.production.yml up -d --build
docker compose -f docker-compose.production.yml exec app php artisan migrate:rollback
docker compose -f docker-compose.production.yml exec app php artisan config:cache
```

---

## Service Configuration

### Laravel Queue Workers

**Why we need queue workers:**
- AI itinerary generation is slow (10-45 seconds)
- Offload to background job for better UX
- Prevents timeout on web requests

**Configuration:**

Queue worker runs as a separate service in `docker-compose.production.yml`:

```yaml
worker:
  image: vibetravels-app
  container_name: vibetravels-worker
  restart: unless-stopped
  command: php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
  volumes:
    - ./:/var/www
  depends_on:
    - mysql
    - redis
  networks:
    - vibetravels
```

**Monitoring queue:**

```bash
# Check queue status
docker compose -f docker-compose.production.yml exec app php artisan queue:monitor

# View failed jobs
docker compose -f docker-compose.production.yml exec app php artisan queue:failed

# Retry failed job
docker compose -f docker-compose.production.yml exec app php artisan queue:retry JOB_ID

# Clear failed jobs
docker compose -f docker-compose.production.yml exec app php artisan queue:flush
```

**Supervisor (alternative to Docker command):**

If you prefer Supervisor inside the container:

```bash
# Supervisor config is at docker/php/supervisord.conf
# It's automatically started in the Dockerfile

# Check supervisor status
docker compose -f docker-compose.production.yml exec app supervisorctl status

# Restart queue worker
docker compose -f docker-compose.production.yml exec app supervisorctl restart laravel-worker:*
```

---

### Laravel Scheduler (Cron)

**Why we need scheduler:**
- Reset monthly AI generation limits (1st of each month)
- Mark trips as completed (day after end date)
- Send trip reminders (3 days before departure)
- Clean up expired email verifications
- Prune old logs and failed jobs

**Configuration:**

Scheduler runs inside the `app` container via cron:

```bash
# Cron is configured in Dockerfile
# Entry: * * * * * cd /var/www && php artisan schedule:run >> /dev/null 2>&1
```

**Scheduled commands** (in `app/Console/Kernel.php`):

```php
protected function schedule(Schedule $schedule)
{
    // Reset AI generation limits monthly
    $schedule->command('limits:reset')
        ->monthlyOn(1, '00:00')
        ->timezone('Europe/Warsaw');

    // Mark trips as completed
    $schedule->command('trips:auto-complete')
        ->dailyAt('03:00');

    // Send trip reminders
    $schedule->command('trips:send-reminders')
        ->dailyAt('09:00');

    // Clean up
    $schedule->command('auth:clear-resets')->everyFifteenMinutes();
    $schedule->command('queue:prune-failed --hours=48')->daily();
    $schedule->command('telescope:prune')->daily(); // if using Telescope
}
```

**Verify scheduler:**

```bash
# List scheduled tasks
docker compose -f docker-compose.production.yml exec app php artisan schedule:list

# Run scheduler once (for testing)
docker compose -f docker-compose.production.yml exec app php artisan schedule:run

# Check logs
docker compose -f docker-compose.production.yml exec app tail -f storage/logs/laravel.log | grep "schedule:"
```

---

### Google OAuth

**Step 1: Configure Google Cloud Console**

1. Go to https://console.cloud.google.com
2. Select your project (or create new one: "VibeTravels Production")
3. Navigate to **APIs & Services → Credentials**
4. Create OAuth 2.0 Client ID:
   ```
   Application type: Web application
   Name: VibeTravels Production

   Authorized JavaScript origins:
   - https://vibetravels.com
   - https://www.vibetravels.com

   Authorized redirect URIs:
   - https://vibetravels.com/auth/google/callback
   - https://www.vibetravels.com/auth/google/callback
   ```
5. Copy **Client ID** and **Client Secret**
6. Add to `.env` and GitHub Secrets

**Step 2: Configure OAuth Consent Screen**

1. **APIs & Services → OAuth consent screen**
2. User Type: **External**
3. App information:
   ```
   App name: VibeTravels
   User support email: your@email.com
   Developer contact: your@email.com
   ```
4. Scopes: Add `email` and `profile`
5. Test users: Add your email for testing
6. **Publishing status**:
   - Start with "Testing" mode (up to 100 users)
   - Submit for verification when ready for public launch

**Step 3: Test OAuth Flow**

```bash
# Test login flow
1. Go to https://vibetravels.com/login
2. Click "Sign in with Google"
3. Authorize app
4. Verify redirect and user creation

# Debug OAuth errors
docker compose -f docker-compose.production.yml logs app | grep -i oauth
```

---

### Email Service Setup

**⚠️ UWAGA: Mailgun usunął darmowy plan dla nowych użytkowników (najtańszy: €14/msc).**

**ZALECANE: Użyj SendGrid (darmowe 100 emaili/dzień = 3,000/msc)**

---

## Opcja A: SendGrid (ZALECANE - DARMOWE)

**Step 1: Create SendGrid Account**

1. Sign up at https://signup.sendgrid.com
2. Fill in account details
3. Choose **Free Plan** (100 emails/day forever)
4. Verify your email address
5. Complete sender verification

**Step 2: Create API Key**

1. Go to **Settings → API Keys**
2. Click **Create API Key**
3. Name: "VibeTravels Production"
4. Permissions: **Full Access** (or **Mail Send** only)
5. Copy the API key (starts with `SG.`)

**Step 3: Configure Laravel**

Add to your `.env` file:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.YOUR_SENDGRID_API_KEY
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=hello@przem-podroze.pl
MAIL_FROM_NAME="VibeTravels"
```

**Step 4: Verify Sender Identity**

1. Go to **Settings → Sender Authentication**
2. Choose **Single Sender Verification** (easiest for MVP)
3. Add your email address (e.g., hello@przem-podroze.pl)
4. Verify the email SendGrid sends you
5. OR choose **Domain Authentication** (better, see below)

**Step 5: Domain Authentication (ZALECANE)**

For better email deliverability, authenticate your domain with SendGrid:

1. W SendGrid: **Settings → Sender Authentication → Authenticate Your Domain**
2. Select DNS host: **Other Host (Not Listed)**
3. Enter domain: `przem-podroze.pl`
4. Advanced Settings:
   - Use automated security: ✅ Yes
   - Brand links with your domain: ✅ Yes (optional)
5. Click **Next**

SendGrid wygeneruje **3 rekordy CNAME**. Przykład (Twoje będą inne):

```
Record 1: em1234.przem-podroze.pl → u1234567.wl.sendgrid.net
Record 2: s1._domainkey.przem-podroze.pl → s1.domainkey.u1234567.wl.sendgrid.net
Record 3: s2._domainkey.przem-podroze.pl → s2.domainkey.u1234567.wl.sendgrid.net
```

6. **Dodaj te rekordy w Cloudflare DNS**:

| Type | Name | Content (z SendGrid) | Proxy Status | TTL |
|------|------|---------------------|--------------|-----|
| CNAME | em1234 | u1234567.wl.sendgrid.net | ❌ DNS only | Auto |
| CNAME | s1._domainkey | s1.domainkey.u1234567.wl.sendgrid.net | ❌ DNS only | Auto |
| CNAME | s2._domainkey | s2.domainkey.u1234567.wl.sendgrid.net | ❌ DNS only | Auto |

**WAŻNE:**
- ⚠️ **Proxy Status musi być "DNS only"** (chmurka szara w Cloudflare)
- Użyj dokładnie takich wartości jak pokazuje SendGrid
- Wartości `em1234` i `u1234567` są przykładowe - Twoje będą inne!

7. Poczekaj **5-10 minut** na propagację DNS
8. W SendGrid kliknij **Verify**
9. Powinny pojawić się zielone checkmarki ✅

**Dlaczego to ważne?**
- ✅ Lepszy deliverability (mniej spamu)
- ✅ DKIM signing (autentykacja emaili)
- ✅ Profesjonalny wygląd (emaile z Twojej domeny)
- ✅ Wyższy sender reputation

**Step 6: Test Email Sending**

```bash
docker compose -f docker-compose.production.yml exec app php artisan tinker

>>> Mail::raw('Test email from VibeTravels', function($msg) {
...     $msg->to('your@email.com')->subject('Test');
... });
```

Check **Activity → Email Activity** in SendGrid dashboard.

**SendGrid Limits:**
- **Free tier**: 100 emails/day (3,000/month) **FOREVER**
- **Expected MVP usage**: 100-500 users × ~10 emails/user/month = 1,000-5,000 emails/month
- ✅ **Sufficient for MVP launch**
- If you need more: Essentials plan $15/month (up to 50k emails)

---

## Opcja B: Mailgun (PŁATNE - €14/msc)

**⚠️ Mailgun nie ma już darmowego planu. Najtańszy: Basic 10k za €14/msc**

**Step 1: Create Mailgun Account**

1. Sign up at https://www.mailgun.com
2. Choose **EU region** (GDPR compliant)
3. Verify your email
4. Select **Basic 10k plan** (€14/month)

**Step 2: Add Domain to Mailgun**

1. In Mailgun Dashboard: **Sending → Domains → Add New Domain**
2. Enter your domain: `przem-podroze.pl`
3. Region: **EU**
4. DKIM key length: **2048 bit**

**Step 3: Configure DNS Records (jeśli używasz Mailgun)**

Mailgun poda Ci rekordy DNS do dodania w **Cloudflare DNS**:

| Type | Name | Content (przykład) | Proxy Status |
|------|------|---------------------|--------------|
| TXT | @ | `v=spf1 include:mailgun.org ~all` | ❌ DNS only |
| TXT | smtp._domainkey | `k=rsa; p=MIGfMA0GCSq...` | ❌ DNS only |
| CNAME | email | `mailgun.org` | ❌ DNS only |
| MX | @ | `mxa.eu.mailgun.org` (priority 10) | ❌ DNS only |
| MX | @ | `mxb.eu.mailgun.org` (priority 10) | ❌ DNS only |

**WAŻNE:** Wszystkie rekordy email **muszą mieć Proxy Status = DNS only** (szara chmurka w Cloudflare)!

**Step 4: Get API Key & Configure .env**

1. **Settings → API Keys**
2. Copy **Private API key** (starts with `key-...`)
3. Add to `.env`:
   ```env
   MAIL_MAILER=mailgun
   MAIL_FROM_ADDRESS=hello@przem-podroze.pl
   MAIL_FROM_NAME="VibeTravels"
   MAILGUN_DOMAIN=przem-podroze.pl
   MAILGUN_SECRET=key-YOUR_MAILGUN_API_KEY
   MAILGUN_ENDPOINT=api.eu.mailgun.net
   ```

**Step 5: Verify Domain**

1. Wait 5-10 minutes for DNS propagation
2. In Mailgun: Click **Verify DNS Settings**
3. All checks should pass (✅ green)

**Step 6: Test Email Sending**

```bash
docker compose -f docker-compose.production.yml exec app php artisan tinker

>>> Mail::raw('Test email from VibeTravels', function($msg) {
...     $msg->to('your@email.com')->subject('Test');
... });

# Check: Mailgun Dashboard → Sending → Logs
```

**Mailgun Limits:**

- **Basic 10k**: €14/month (10,000 emails/month)
- **Expected MVP usage**: 100-500 users × ~10 emails/user/month = 1,000-5,000 emails/month
- ⚠️ **Nie zalecane dla MVP** - wybierz SendGrid (darmowe)

---

## Opcja C: Inne darmowe alternatywy

**1. Resend** (https://resend.com)
- Free tier: 3,000 emails/month
- Modern API, dobre dla Laravel
- EU region available

**2. SMTP2GO** (https://www.smtp2go.com)
- Free tier: 1,000 emails/month
- Proste setup przez SMTP

**3. Amazon SES** (https://aws.amazon.com/ses)
- $0.10 per 1,000 emails
- 62,000 emails/month za $6.20
- Wymaga konfiguracji AWS (bardziej skomplikowane)

---

## Monitoring & Maintenance

### Health Checks

**Application health endpoint:**

```php
// routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'redis' => Redis::connection()->ping() ? 'connected' : 'disconnected',
        'queue' => Queue::size() < 100 ? 'healthy' : 'overloaded',
    ]);
});
```

**Monitor with cron:**

```bash
# Add to server crontab (outside Docker)
crontab -e

# Check health every 5 minutes
*/5 * * * * curl -f https://vibetravels.com/health || echo "Health check failed" | mail -s "VibeTravels Down" your@email.com
```

**External monitoring (free options):**
- **UptimeRobot** (https://uptimerobot.com) - 50 monitors free
- **Better Uptime** (https://betteruptime.com) - 1 monitor free
- **Freshping** (https://www.freshworks.com/website-monitoring) - 50 monitors free

---

### Logging

**View logs:**

```bash
# Application logs
docker compose -f docker-compose.production.yml logs -f app

# Laravel logs (inside container)
docker compose -f docker-compose.production.yml exec app tail -f storage/logs/laravel.log

# Nginx access logs
docker compose -f docker-compose.production.yml logs -f nginx

# MySQL logs
docker compose -f docker-compose.production.yml logs -f mysql

# Queue worker logs
docker compose -f docker-compose.production.yml logs -f worker
```

**Log rotation** (Laravel daily logs):

```env
# .env
LOG_CHANNEL=daily
LOG_DAILY_DAYS=14
```

Logs older than 14 days are automatically deleted.

**Centralized logging (optional):**

For better log management, consider:
- **Papertrail** (https://www.papertrail.com) - 50 MB/month free
- **Logz.io** (https://logz.io) - 3 GB/day free for 7 days
- **Self-hosted ELK stack** (Elasticsearch + Logstash + Kibana)

---

### Performance Monitoring

**Laravel Telescope (development only):**

Do not enable Telescope in production (high overhead).

**Alternative APM tools:**
- **New Relic** (https://newrelic.com) - 100 GB/month free
- **Sentry** (https://sentry.io) - 5K errors/month free
- **Bugsnag** (https://www.bugsnag.com) - 7,500 events/month free

**Add Sentry (recommended):**

```bash
docker compose -f docker-compose.production.yml exec app composer require sentry/sentry-laravel
docker compose -f docker-compose.production.yml exec app php artisan sentry:publish --dsn=YOUR_DSN
```

---

### Database Maintenance

**Optimize tables:**

```bash
# Run monthly
docker compose -f docker-compose.production.yml exec mysql mysql -u vibetravels -p vibetravels -e "OPTIMIZE TABLE users, travel_plans, plan_days, plan_points;"
```

**Database backups** (see Backup Strategy section below)

---

## Backup Strategy

### Automated DigitalOcean Backups

**Droplet Backups** ($2.40/month):
- Automatic weekly backups
- Stores 4 most recent backups
- Can restore entire droplet in case of failure

**Enable backups:**
1. DigitalOcean Dashboard → Droplets → vibetravels-prod
2. Backups → Enable Backups
3. Backups run automatically every week

**Restore from backup:**
1. Destroy current droplet (or create new from backup)
2. Create droplet from backup image
3. Update DNS to new IP
4. Verify application

---

### Manual Database Backups

**Daily automated backups:**

```bash
# Create backup script on server
sudo nano /usr/local/bin/backup-database.sh
```

```bash
#!/bin/bash
# Backup script for VibeTravels database

BACKUP_DIR="/var/www/vibetravels/backups"
DATE=$(date +%Y-%m-%d_%H-%M-%S)
BACKUP_FILE="$BACKUP_DIR/vibetravels_$DATE.sql.gz"

# Create backup directory
mkdir -p $BACKUP_DIR

# Dump database
docker compose -f /var/www/vibetravels/docker-compose.production.yml exec -T mysql \
    mysqldump -u vibetravels -pYOUR_PASSWORD vibetravels | gzip > $BACKUP_FILE

# Keep only last 30 days of backups
find $BACKUP_DIR -name "vibetravels_*.sql.gz" -mtime +30 -delete

echo "Backup completed: $BACKUP_FILE"
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/backup-database.sh

# Add to crontab (daily at 2 AM)
crontab -e
0 2 * * * /usr/local/bin/backup-database.sh >> /var/log/database-backup.log 2>&1
```

**Restore from backup:**

```bash
# Copy backup to server (if needed)
scp vibetravels_2025-01-15_02-00-00.sql.gz deploy@YOUR_IP:/tmp/

# Restore
gunzip < /tmp/vibetravels_2025-01-15_02-00-00.sql.gz | \
docker compose -f docker-compose.production.yml exec -T mysql \
mysql -u vibetravels -pYOUR_PASSWORD vibetravels
```

---

### Off-site Backups

**Option 1: DigitalOcean Spaces** ($5/month for 250 GB)

```bash
# Install s3cmd
sudo apt install s3cmd

# Configure for DigitalOcean Spaces
s3cmd --configure
# Access Key: YOUR_SPACES_KEY
# Secret Key: YOUR_SPACES_SECRET
# S3 Endpoint: fra1.digitaloceanspaces.com

# Upload backup
s3cmd put /var/www/vibetravels/backups/*.sql.gz s3://vibetravels-backups/
```

**Option 2: Amazon S3** (~$1/month for 50 GB)

**Option 3: Backblaze B2** (~$0.50/month for 50 GB) ✅ **CHEAPEST**

---

## Troubleshooting

### Application Not Accessible

**Check 1: DNS propagation**
```bash
# Check if DNS is resolving correctly
dig vibetravels.com +short
nslookup vibetravels.com

# Should return your droplet IP
```

**Check 2: Docker services**
```bash
ssh deploy@YOUR_IP
cd /var/www/vibetravels
docker compose -f docker-compose.production.yml ps

# All services should be "Up"
# If not:
docker compose -f docker-compose.production.yml logs SERVICE_NAME
```

**Check 3: Nginx configuration**
```bash
docker compose -f docker-compose.production.yml exec nginx nginx -t
# Should return "syntax is ok"

# Reload Nginx
docker compose -f docker-compose.production.yml restart nginx
```

**Check 4: Firewall**
```bash
sudo ufw status

# Ensure 80 and 443 are allowed
# If not:
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
```

---

### SSL Certificate Issues

**Error: "Your connection is not private"**

```bash
# Check if certificate exists
docker compose -f docker-compose.production.yml exec nginx ls -la /etc/nginx/ssl/

# Renew certificate manually
docker compose -f docker-compose.production.yml run --rm certbot renew

# Restart Nginx
docker compose -f docker-compose.production.yml restart nginx
```

---

### Database Connection Failed

```bash
# Check MySQL container
docker compose -f docker-compose.production.yml logs mysql

# Access MySQL shell
docker compose -f docker-compose.production.yml exec mysql mysql -u vibetravels -p

# Verify credentials in .env match database
```

---

### Queue Not Processing

```bash
# Check worker status
docker compose -f docker-compose.production.yml ps worker

# View worker logs
docker compose -f docker-compose.production.yml logs -f worker

# Manually process queue
docker compose -f docker-compose.production.yml exec app php artisan queue:work --once

# Restart worker
docker compose -f docker-compose.production.yml restart worker
```

---

### Emails Not Sending

**Check 1: Mailgun domain verification**
```bash
# Go to Mailgun Dashboard → Domains
# Verify all DNS records are green ✅
```

**Check 2: Test SMTP connection**
```bash
docker compose -f docker-compose.production.yml exec app php artisan tinker

>>> Mail::raw('Test', fn($m) => $m->to('your@email.com')->subject('Test'));
```

**Check 3: View Laravel logs**
```bash
docker compose -f docker-compose.production.yml exec app tail -f storage/logs/laravel.log | grep -i mail
```

**Check 4: Mailgun logs**
- Go to Mailgun Dashboard → Sending → Logs
- Look for failed deliveries or bounces

---

### Google OAuth Failing

**Error: "redirect_uri_mismatch"**

1. Go to Google Cloud Console → Credentials
2. Edit OAuth 2.0 Client ID
3. Ensure redirect URI exactly matches: `https://vibetravels.com/auth/google/callback`
4. Check for trailing slashes and http vs https

**Error: "access_denied"**

1. OAuth consent screen might be in "Testing" mode
2. Add test users or publish app for production

---

### Out of Disk Space

```bash
# Check disk usage
df -h

# Clean up Docker
docker system prune -a --volumes
docker compose -f docker-compose.production.yml down -v
docker compose -f docker-compose.production.yml up -d

# Clean Laravel logs
docker compose -f docker-compose.production.yml exec app php artisan log:clear

# Clean old backups
find /var/www/vibetravels/backups -mtime +30 -delete
```

---

### High Memory Usage

```bash
# Check memory
free -h

# Identify memory hogs
docker stats

# Restart services
docker compose -f docker-compose.production.yml restart

# If persistent, upgrade droplet to 4GB RAM
```

---

## Emergency Contacts & Resources

### Support Channels

- **DigitalOcean Support**: https://www.digitalocean.com/support
- **Cloudflare Support**: https://support.cloudflare.com
- **Mailgun Support**: https://help.mailgun.com
- **Laravel Documentation**: https://laravel.com/docs
- **OpenAI Status**: https://status.openai.com

### Useful Commands Reference

```bash
# SSH to server
ssh deploy@YOUR_IP

# View all logs
cd /var/www/vibetravels
docker compose -f docker-compose.production.yml logs -f

# Restart application
docker compose -f docker-compose.production.yml restart app

# Full restart
docker compose -f docker-compose.production.yml down
docker compose -f docker-compose.production.yml up -d

# Run artisan commands
docker compose -f docker-compose.production.yml exec app php artisan COMMAND

# Database backup
docker compose -f docker-compose.production.yml exec mysql mysqldump -u vibetravels -p vibetravels > backup.sql

# Clear caches
docker compose -f docker-compose.production.yml exec app php artisan optimize:clear
```

---

## Post-Deployment Checklist

After deployment, verify these:

- [ ] Application accessible at https://vibetravels.com
- [ ] SSL certificate valid (green padlock)
- [ ] Registration works (email verification sent)
- [ ] Google OAuth login works
- [ ] Onboarding flow completes
- [ ] Create travel plan (save as draft)
- [ ] Generate itinerary with AI (check queue worker)
- [ ] PDF export works
- [ ] Emails delivered (check Mailgun logs)
- [ ] Scheduler running (check `schedule:list`)
- [ ] Backups enabled (DigitalOcean + database)
- [ ] Monitoring setup (UptimeRobot or similar)
- [ ] Health check endpoint responding
- [ ] 404/500 error pages styled correctly
- [ ] Mobile responsive design verified
- [ ] Performance acceptable (<3s page load)
- [ ] No console errors in browser DevTools

---

## Next Steps

1. **Week 1-2**: Complete infrastructure setup and first deployment
2. **Week 3**: Test all features thoroughly in production
3. **Week 4**: Soft launch to 10-20 beta testers
4. **Month 2**: Public launch and marketing
5. **Month 3**: Monitor metrics and iterate

### Growth Preparation

When you reach 500+ users:
- Upgrade droplet to 4GB RAM ($24/mo)
- Consider managed MySQL database
- Add caching layer (Redis cache TTL)
- Implement CDN for static assets (Cloudflare already provides this)
- Set up error tracking (Sentry)
- Monitor with APM tool (New Relic)

---

**Good luck with your deployment! 🚀**

For questions or issues, refer to the Laravel documentation or create an issue in the repository.
