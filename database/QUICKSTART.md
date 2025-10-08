# Database Quick Start Guide

Quick reference for setting up and running VibeTravels database migrations.

## Prerequisites

- MySQL 8.0.16+ installed and running
- PHP 8.2+ with PDO MySQL extension
- Composer installed

## 5-Minute Setup

### 1. Copy Environment File
```bash
cp .env.example .env
```

### 2. Configure Database
Edit `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vibetravels
DB_USERNAME=root
DB_PASSWORD=your_password_here
```

### 3. Create Database
```bash
mysql -u root -p -e "CREATE DATABASE vibetravels CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 4. Install Dependencies
```bash
composer install
```

### 5. Generate Application Key
```bash
php artisan key:generate
```

### 6. Run Migrations
```bash
php artisan migrate
```

Expected output:
```
Migration table created successfully.
Migrating: 2025_10_08_000001_create_users_table
Migrated:  2025_10_08_000001_create_users_table (X.XXms)
...
Migrated:  2025_10_08_000014_create_failed_jobs_table (X.XXms)
```

### 7. Verify Setup
```bash
php artisan migrate:status
```

All 14 migrations should show `[Ran]`.

## What Was Created?

✅ **14 database tables**:
- User authentication (users, password_resets, email_verifications)
- User preferences and onboarding tracking
- Travel plans with day-by-day itinerary structure
- AI generation tracking with cost metrics
- Feedback and analytics
- Email logging
- Queue system (jobs, failed_jobs)

✅ **24 indexes** for query performance

✅ **13 foreign keys** with CASCADE delete (GDPR compliance)

✅ **3 CHECK constraints** for data validation

## Common Commands

### Run Migrations
```bash
php artisan migrate
```

### Check Migration Status
```bash
php artisan migrate:status
```

### Rollback Last Batch
```bash
php artisan migrate:rollback
```

### Fresh Start (⚠️ Deletes all data!)
```bash
php artisan migrate:fresh
```

### Fresh Start with Seed Data
```bash
php artisan migrate:fresh --seed
```

### View Database Structure
```bash
php artisan db:show
php artisan db:table users
```

## Seeding Test Data

Run seeders to populate database with fake data:

```bash
php artisan db:seed
```

This creates:
- 20 fake users
- 100 travel plans
- AI generations with mock costs
- Feedback examples
- PDF export records

## Troubleshooting

### "Access denied for user"
❌ Problem: Database credentials incorrect

✅ Solution: Check `DB_USERNAME` and `DB_PASSWORD` in `.env`

### "Unknown database 'vibetravels'"
❌ Problem: Database not created

✅ Solution: Run `CREATE DATABASE vibetravels;` in MySQL

### "Syntax error: 1071 Specified key was too long"
❌ Problem: Charset/collation incorrect

✅ Solution: Ensure database created with `CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`

### "Check constraint 'chk_...' is violated"
❌ Problem: MySQL version < 8.0.16

✅ Solution: Upgrade MySQL or comment out CHECK constraints in migrations

## Production Setup

### 1. Backup Database
```bash
mysqldump -u root -p vibetravels > backup_$(date +%Y%m%d).sql
```

### 2. Run Migrations (with confirmation)
```bash
php artisan migrate
```

### 3. Enable Redis Cache (recommended)
Edit `.env`:
```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis
```

### 4. Configure Cron Jobs

Add to crontab:
```bash
# Laravel scheduler (handles AI limit resets, cleanup tasks)
* * * * * cd /path/to/vibetravels && php artisan schedule:run >> /dev/null 2>&1

# Queue worker (processes AI generation jobs)
# Use supervisor instead of cron for queue workers
```

Supervisor config example:
```ini
[program:vibetravels-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/vibetravels/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/vibetravels/storage/logs/worker.log
```

## Next Steps

1. ✅ Migrations complete
2. ⬜ Create Eloquent models (`app/Models/`)
3. ⬜ Implement authentication (Laravel Breeze)
4. ⬜ Create seeders (`database/seeders/`)
5. ⬜ Build API endpoints / Livewire components
6. ⬜ Integrate OpenAI API
7. ⬜ Configure Mailgun for emails

## Documentation

- **Detailed README**: `database/migrations/README.md`
- **Schema Documentation**: `database/migrations/SCHEMA.md`
- **Database Plan**: `.ai/db-plan.md`
- **Product Requirements**: `.ai/prd.md`

## Support

Questions? Check:
1. `database/migrations/README.md` - Comprehensive guide
2. `database/migrations/SCHEMA.md` - Schema reference with ERD
3. Migration files - Inline documentation for each table
4. Laravel docs - https://laravel.com/docs/11.x/migrations

---

**Database Version**: 1.0.0 (MVP)
**Last Updated**: 2025-10-08
