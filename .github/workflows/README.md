# GitHub Actions Workflows

## Available Workflows

### 1. CI (Continuous Integration)
**File:** `ci.yml`
**Trigger:** Push or PR to `main` or `develop` branches

**Steps:**
- Run PHPUnit tests
- Run PHPStan static analysis
- Run Laravel Pint code style check
- Run frontend linting and build

**Purpose:** Ensure code quality before merging

---

### 2. Deploy to Production
**File:** `deploy.yml`
**Triggers:**
- Automatic: Push to `main` branch
- Manual: Workflow dispatch from GitHub UI

**Steps:**
1. **Tests Job:**
   - Run all tests (PHPUnit, PHPStan, Pint)
   - Build frontend assets
   - Skippable via workflow dispatch

2. **Deploy Job:**
   - SSH to production server
   - Pull latest code
   - Build Docker images
   - Start/restart services
   - Run migrations
   - Cache configuration
   - Copy frontend assets
   - Verify deployment

3. **Verify Job:**
   - Check all containers are running
   - Check disk space
   - Test health endpoint

**Required Secrets:**
- `SSH_PRIVATE_KEY` - Private SSH key for deployment
- `SERVER_HOST` - Production server IP
- `SERVER_USER` - SSH user (usually `deploy`)
- `APP_KEY` - Laravel application key
- `DB_PASSWORD` - Database password
- `GOOGLE_CLIENT_ID` - Google OAuth client ID
- `GOOGLE_CLIENT_SECRET` - Google OAuth secret
- `OPENAI_API_KEY` - OpenAI API key
- `SENDGRID_API_KEY` - SendGrid API key (optional)

---

## Manual Deployment

To manually trigger deployment:

1. Go to **Actions** tab on GitHub
2. Select **Deploy to Production** workflow
3. Click **Run workflow**
4. Choose options:
   - `skip_tests: false` - Run tests before deployment (default)
   - `skip_tests: true` - Skip tests and deploy immediately

---

## Monitoring Deployments

### View Logs
1. Go to **Actions** tab
2. Click on the workflow run
3. View real-time logs for each step

### Check Deployment Status
After deployment completes, verify:

```bash
# Check health endpoint
curl https://przem-podroze.pl/health

# Expected response:
{
  "status": "ok",
  "timestamp": "2025-10-16T...",
  "services": {
    "database": "connected",
    "redis": "connected",
    "queue": {
      "status": "healthy",
      "size": 0
    }
  },
  "app": {
    "env": "production",
    "debug": false
  }
}
```

### SSH to Server
```bash
ssh deploy@YOUR_SERVER_IP
cd /var/www/vibetravels
docker compose -f docker-compose.production.yml ps
```

---

## Troubleshooting

### Deployment Failed

**Check logs:**
```bash
# GitHub Actions logs
# Go to Actions → Failed run → View logs

# Server logs
ssh deploy@YOUR_SERVER_IP
cd /var/www/vibetravels
docker compose -f docker-compose.production.yml logs -f
```

**Common issues:**

1. **SSH connection failed**
   - Check if `SSH_PRIVATE_KEY` secret is correct
   - Verify public key is in `~/.ssh/authorized_keys` on server
   - Test: `ssh -i key deploy@SERVER_IP`

2. **Docker build failed**
   - Check if Docker is installed on server
   - Verify disk space: `df -h`
   - Check Docker logs: `docker compose logs`

3. **Migration failed**
   - Check database connection in `.env`
   - Verify MySQL container is running
   - Check migration files for errors

4. **Health check failed**
   - Database not connected
   - Redis not running
   - Check service status: `docker compose ps`

### Rollback

If deployment fails, rollback to previous version:

```bash
ssh deploy@YOUR_SERVER_IP
cd /var/www/vibetravels

# View recent commits
git log --oneline -10

# Rollback to specific commit
git checkout COMMIT_HASH

# Rebuild and restart
docker compose -f docker-compose.production.yml up -d --build
docker compose -f docker-compose.production.yml exec -T app php artisan migrate:rollback
docker compose -f docker-compose.production.yml exec -T app php artisan config:cache
```

---

## Testing Workflow Locally

You can test parts of the workflow locally before pushing:

```bash
# Run tests
make test

# Run static analysis
make phpstan

# Run code style check
make cs-check

# Build frontend assets
npm run build

# All quality checks
make quality
```

---

## Workflow Badges

Add to README.md:

```markdown
[![CI](https://github.com/pwaszkis/10xdevs/actions/workflows/ci.yml/badge.svg)](https://github.com/pwaszkis/10xdevs/actions/workflows/ci.yml)
[![Deploy](https://github.com/pwaszkis/10xdevs/actions/workflows/deploy.yml/badge.svg)](https://github.com/pwaszkis/10xdevs/actions/workflows/deploy.yml)
```
