# VibeTravels Production Deployment Checklist

## 🚨 Common Issues & Solutions

This document summarizes issues encountered during production deployment and their solutions to ensure future deployments work correctly.

---

## Issue #1: Missing Composer Dependencies

**Symptom:**
```
PHP Fatal error: Failed opening required '/var/www/vendor/autoload.php'
```

**Root Cause:**
- Composer dependencies not installed during deployment
- `vendor/` directory was cleaned or missing

**Solution:**
✅ **Already fixed in workflow** (line 285 in `.github/workflows/pipeline.yml`):
```bash
docker compose -f docker-compose.production.yml exec -T app composer install --optimize-autoloader --no-dev --no-interaction
```

**Prevention:**
- Deployment workflow automatically runs `composer install`
- Never commit `vendor/` directory to git (already in `.gitignore`)

---

## Issue #2: Livewire Assets 404 Error

**Symptom:**
```
GET /livewire/livewire.min.js 404 (Not Found)
```

**Root Cause:**
- Route cache was stale/corrupted
- `optimize:clear` wasn't run before rebuilding cache

**Solution:**
✅ **Already fixed in workflow** (lines 293-297):
```bash
docker compose exec app php artisan optimize:clear  # Clear ALL caches first
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

**Prevention:**
- Always run `optimize:clear` before caching
- Livewire v3 serves assets dynamically via routes (not static files)

---

## Issue #3: Worker Can't Connect to MySQL

**Symptom:**
```
SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from `cache`...)
```

**Root Cause:**
- `.env` had `CACHE_DRIVER=database` instead of `CACHE_STORE=redis`
- Laravel 11 uses `CACHE_STORE` (not `CACHE_DRIVER`)
- Worker tried to read cache from MySQL instead of Redis

**Solution:**
✅ **Fixed in `.env.production.example` and deployment validation**:

**Correct production .env:**
```env
CACHE_STORE=redis     # NOT CACHE_DRIVER, NOT database
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
DB_HOST=mysql         # Docker container name
REDIS_HOST=redis      # Docker container name
```

**Prevention:**
- Use `.env.production.example` as template
- Deployment workflow now validates .env configuration (lines 264-280)

---

## Issue #4: Queue Jobs Not Processing

**Symptom:**
- AI plan generation stuck at 0%
- Jobs added to queue but worker not processing them

**Root Cause:**
- Worker was listening only to `default` queue
- AI jobs are dispatched to `ai-generation` queue
- Worker command didn't include `--queue=default,ai-generation`

**Solution:**
✅ **Fixed in `docker-compose.production.yml`** (line 105):
```yaml
command: php artisan queue:work redis --queue=default,ai-generation --sleep=3 --tries=3 --max-time=3600 --memory=128
```

**Prevention:**
- Worker now listens to both queues
- Can verify with: `docker compose exec app php artisan queue:monitor redis`

---

## Pre-Deployment Checklist

Before deploying to production, ensure:

### 1. Server Configuration

- [ ] `.env` file exists on server at `/var/www/vibetravels/.env`
- [ ] `.env` has all required values (see `.env.production.example`)
- [ ] Database password is set and strong
- [ ] OpenAI API key is set (if `AI_USE_REAL_API=true`)
- [ ] SendGrid/Mailgun credentials configured
- [ ] Google OAuth credentials configured

**Validate with:**
```bash
cd /var/www/vibetravels
./check-production-health.sh
```

### 2. Critical .env Settings

Verify these exact values in production `.env`:

```env
# Docker container names (NOT localhost!)
DB_HOST=mysql
REDIS_HOST=redis

# Use Redis for performance (NOT database!)
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Security
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true
```

### 3. Docker Compose Configuration

Verify `docker-compose.production.yml`:

- [ ] Worker command includes both queues: `--queue=default,ai-generation`
- [ ] All services have `restart: unless-stopped`
- [ ] MySQL and Redis use named volumes for persistence

### 4. SSL Certificates

- [ ] Let's Encrypt certificates exist in `certbot/conf/`
- [ ] Certbot container is running for auto-renewal
- [ ] Check with: `./check-ssl-status.sh`

---

## Post-Deployment Verification

After deployment completes, verify:

```bash
cd /var/www/vibetravels

# 1. All containers running
docker compose -f docker-compose.production.yml ps

# Expected output:
# vibetravels-app       Up (healthy)
# vibetravels-mysql     Up (healthy)
# vibetravels-nginx     Up (healthy)
# vibetravels-redis     Up (healthy)
# vibetravels-worker    Up
# vibetravels-scheduler Up

# 2. Application health
curl https://przem-podroze.pl/health
# Should return: {"status":"ok",...}

# 3. Database connection
docker compose -f docker-compose.production.yml exec app php artisan db:show
# Should show tables and connection info

# 4. Queue worker processing
docker compose -f docker-compose.production.yml logs worker --tail=20
# Should NOT show connection errors

# 5. Redis queues
docker compose -f docker-compose.production.yml exec app php artisan queue:monitor redis
# Should show queue lengths

# 6. No failed jobs
docker compose -f docker-compose.production.yml exec app php artisan queue:failed
# Should show "No failed jobs"

# 7. Livewire assets loading
curl -I https://przem-podroze.pl/livewire/livewire.min.js
# Should return: HTTP/2 200
```

**Run complete health check:**
```bash
./check-production-health.sh
```

---

## Quick Fixes

### If worker stops processing jobs:

```bash
docker compose -f docker-compose.production.yml restart worker
docker compose -f docker-compose.production.yml logs worker --tail=50
```

### If routes return 404:

```bash
docker compose -f docker-compose.production.yml exec app php artisan optimize:clear
docker compose -f docker-compose.production.yml exec app php artisan route:cache
docker compose -f docker-compose.production.yml restart app nginx
```

### If cache/session issues:

```bash
docker compose -f docker-compose.production.yml exec app php artisan cache:clear
docker compose -f docker-compose.production.yml exec redis redis-cli FLUSHDB
docker compose -f docker-compose.production.yml restart app worker
```

### If permissions errors:

```bash
# Fix permissions on host (not inside container)
# Use deploy:deploy because volumes are bind-mounted from host
sudo chown -R deploy:deploy storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Fix public/vendor if needed (Livewire published assets)
sudo chown -R deploy:deploy public/vendor
```

---

## Architecture Diagram

```
┌─────────────────────────────────────────┐
│  Cloudflare (DNS + CDN + SSL)          │
└────────────────┬────────────────────────┘
                 │ HTTPS
┌────────────────▼────────────────────────┐
│  Nginx (SSL termination, static files)  │
└────────────────┬────────────────────────┘
                 │
┌────────────────▼────────────────────────┐
│  Laravel App (PHP-FPM)                  │
│  - Livewire components                  │
│  - API endpoints                        │
│  - Dispatches jobs to Redis queue      │
└─────┬──────────────────────────┬────────┘
      │                          │
┌─────▼─────┐              ┌─────▼─────────┐
│  MySQL    │              │  Redis        │
│  (data)   │              │  (cache,      │
└───────────┘              │   queue,      │
                           │   sessions)   │
                           └───────┬───────┘
                                   │
                          ┌────────▼────────┐
                          │  Queue Worker   │
                          │  - Processes:   │
                          │    * default    │
                          │    * ai-gen     │
                          └─────────────────┘
```

---

## Environment Variables Reference

| Variable | Development | Production | Notes |
|----------|-------------|------------|-------|
| `APP_ENV` | `local` | `production` | Environment name |
| `APP_DEBUG` | `true` | `false` | Never true in production! |
| `DB_HOST` | `127.0.0.1` | `mysql` | Docker container name |
| `REDIS_HOST` | `127.0.0.1` | `redis` | Docker container name |
| `CACHE_STORE` | `database` | `redis` | Laravel 11 uses CACHE_STORE |
| `QUEUE_CONNECTION` | `database` | `redis` | Redis is faster |
| `SESSION_DRIVER` | `database` | `redis` | Redis for performance |
| `AI_USE_REAL_API` | `false` | `true` | Mock vs real OpenAI |

---

## Monitoring

### Key Metrics to Watch

1. **Queue Length** - Should be < 10 normally
   ```bash
   docker compose exec redis redis-cli LLEN "vibetravels_database_queues:ai-generation"
   ```

2. **Failed Jobs** - Should be 0
   ```bash
   docker compose exec app php artisan queue:failed
   ```

3. **Container Health** - All should be healthy
   ```bash
   docker compose -f docker-compose.production.yml ps
   ```

4. **Disk Usage** - Should be < 80%
   ```bash
   df -h
   ```

5. **Memory Usage** - Monitor with:
   ```bash
   docker stats
   ```

---

## Rollback Procedure

If deployment fails or introduces issues:

```bash
# 1. SSH to server
ssh deploy@przem-podroze.pl

cd /var/www/vibetravels

# 2. Check recent commits
git log --oneline -10

# 3. Rollback to previous commit
git checkout <previous-commit-hash>

# 4. Restart services
docker compose -f docker-compose.production.yml down
docker compose -f docker-compose.production.yml up -d

# 5. Run migrations rollback if needed
docker compose -f docker-compose.production.yml exec app php artisan migrate:rollback

# 6. Clear caches
docker compose -f docker-compose.production.yml exec app php artisan optimize:clear
docker compose -f docker-compose.production.yml exec app php artisan config:cache
```

---

## Support

If issues persist:

1. Run health check: `./check-production-health.sh`
2. Check logs: `docker compose -f docker-compose.production.yml logs -f`
3. Review this checklist
4. Check `DEPLOYMENT.md` for detailed setup instructions

**Last Updated:** 2025-10-16
**Version:** 1.0
