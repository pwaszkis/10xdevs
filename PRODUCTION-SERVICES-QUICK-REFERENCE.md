# Production Services Quick Reference

Quick reference for managing Laravel queue workers and scheduler in production.

## Queue Workers (Supervisor)

### Status & Management

```bash
# Check worker status
sudo supervisorctl status vibetravels-worker:*

# Restart workers (do this after every deployment)
sudo supervisorctl restart vibetravels-worker:*

# Stop workers
sudo supervisorctl stop vibetravels-worker:*

# Start workers
sudo supervisorctl start vibetravels-worker:*

# View worker logs
sudo tail -f /var/www/html/storage/logs/worker.log

# Or via Supervisor
sudo supervisorctl tail -f vibetravels-worker:0
```

### Queue Monitoring

```bash
# Check pending jobs
docker compose -f docker-compose.production.yml exec app php artisan queue:monitor

# View failed jobs
docker compose -f docker-compose.production.yml exec app php artisan queue:failed

# Retry all failed jobs
docker compose -f docker-compose.production.yml exec app php artisan queue:retry all

# Clear failed jobs
docker compose -f docker-compose.production.yml exec app php artisan queue:flush
```

### Configuration

- **Config file**: `config/vibetravels-worker.conf`
- **System location**: `/etc/supervisor/conf.d/vibetravels-worker.conf`
- **Number of workers**: 2 (adjust `numprocs` in config)
- **Queue driver**: `database` (jobs stored in `jobs` table)
- **Log file**: `/var/www/html/storage/logs/worker.log`

## Laravel Scheduler (Cron)

### Verify & Test

```bash
# Check if cron job exists
sudo crontab -u www-data -l

# Should show:
# * * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1

# List all scheduled tasks
docker compose -f docker-compose.production.yml exec app php artisan schedule:list

# Run scheduler manually (for testing)
docker compose -f docker-compose.production.yml exec app php artisan schedule:run

# Run specific command
docker compose -f docker-compose.production.yml exec app php artisan plans:auto-complete
docker compose -f docker-compose.production.yml exec app php artisan limits:reset-monthly
```

### Monitor Execution

```bash
# Check cron service status
sudo systemctl status cron

# View cron logs
grep CRON /var/log/syslog | tail -20

# View Laravel logs for scheduled tasks
docker compose -f docker-compose.production.yml exec app tail -f storage/logs/laravel.log | grep -i "schedule"
```

### Scheduled Commands

| Command | Schedule | Purpose |
|---------|----------|---------|
| `plans:auto-complete` | Daily at 00:00 | Mark past trips as completed |
| `limits:reset-monthly` | Monthly 1st at 00:01 | Reset AI limits (placeholder) |

## Setup (First Time)

Run the automated setup script:

```bash
cd /var/www/vibetravels
sudo ./scripts/setup-production-services.sh
```

This will:
- ✅ Install Supervisor
- ✅ Configure 2 queue workers
- ✅ Set up Laravel scheduler (cron)
- ✅ Configure logging
- ✅ Start services automatically

## Deployment Checklist

After every code deployment, remember to:

```bash
# 1. Restart queue workers (to pick up code changes)
sudo supervisorctl restart vibetravels-worker:*

# 2. Verify workers are running
sudo supervisorctl status vibetravels-worker:*

# 3. Check for any failed jobs
docker compose -f docker-compose.production.yml exec app php artisan queue:failed

# 4. Verify scheduler is configured
sudo crontab -u www-data -l | grep schedule:run
```

**Note**: The CI/CD pipeline (`.github/workflows/pipeline.yml`) automatically restarts workers on deployment if Supervisor is installed. If you haven't run the setup script yet, workers won't restart automatically.

## Troubleshooting

### Workers not processing jobs

```bash
# 1. Check worker status
sudo supervisorctl status vibetravels-worker:*

# 2. Check worker logs
sudo tail -50 /var/www/html/storage/logs/worker.log

# 3. Check Laravel logs
docker compose -f docker-compose.production.yml exec app tail -f storage/logs/laravel.log

# 4. Restart workers
sudo supervisorctl restart vibetravels-worker:*
```

### Scheduler not running

```bash
# 1. Check cron service
sudo systemctl status cron

# 2. Verify cron job exists
sudo crontab -u www-data -l

# 3. Test scheduler manually
docker compose -f docker-compose.production.yml exec app php artisan schedule:run

# 4. Check for errors
grep CRON /var/log/syslog | tail -50
```

### High memory usage

```bash
# Check worker memory
ps aux | grep "queue:work"

# Restart workers to clear memory
sudo supervisorctl restart vibetravels-worker:*
```

## Important Files

```
/etc/supervisor/conf.d/vibetravels-worker.conf  # Supervisor config
/var/www/html/storage/logs/worker.log           # Worker logs
/var/www/html/storage/logs/laravel.log          # Laravel logs
/var/log/syslog                                  # System/cron logs
/home/global/projekty/10xdevs/config/vibetravels-worker.conf  # Config source
/home/global/projekty/10xdevs/scripts/setup-production-services.sh  # Setup script
```

## Common Commands

```bash
# Full service restart
sudo supervisorctl restart all
sudo systemctl restart cron

# View all logs at once
sudo tail -f /var/www/html/storage/logs/worker.log /var/www/html/storage/logs/laravel.log

# Check system resources
htop
df -h
free -h

# Check Docker containers
docker compose -f docker-compose.production.yml ps
docker compose -f docker-compose.production.yml logs -f app
```

## Need Help?

See full documentation in `DEPLOYMENT.md`:
- [Laravel Queue Workers](DEPLOYMENT.md#laravel-queue-workers)
- [Laravel Scheduler](DEPLOYMENT.md#laravel-scheduler-cron)
