# VibeTravels Database Migrations

This directory contains all database migrations for the VibeTravels MVP application.

## Overview

The database schema supports:
- Multi-provider authentication (email + password, Google OAuth)
- User preferences and onboarding tracking
- AI-powered travel plan generation with cost tracking
- Day-by-day itinerary structure with sightseeing points
- User feedback and PDF export tracking
- Email verification and notification logging
- User behavior analytics

## Prerequisites

- **MySQL 8.0.16+** (required for CHECK constraints and JSON support)
- **PHP 8.2+** with PDO MySQL extension
- **Laravel 11**

## Database Configuration

### 1. Environment Setup

Copy `.env.example` to `.env` and configure your database:

```bash
cp .env.example .env
```

Update database credentials in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vibetravels
DB_USERNAME=root
DB_PASSWORD=your_password_here
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

### 2. Create Database

Create the database in MySQL:

```sql
CREATE DATABASE vibetravels CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Or via command line:

```bash
mysql -u root -p -e "CREATE DATABASE vibetravels CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 3. Run Migrations

Execute all migrations in order:

```bash
php artisan migrate
```

This will create 14 tables in the following order:

1. `users` - Core user accounts with multi-provider auth
2. `password_resets` - Laravel password reset tokens
3. `user_preferences` - Travel preferences for AI generation
4. `travel_plans` - User travel plans with metadata
5. `plan_days` - Individual days within travel plans
6. `plan_points` - Sightseeing points within each day
7. `ai_generations` - AI generation tracking with cost metrics
8. `feedback` - User feedback on generated plans
9. `pdf_exports` - PDF export tracking
10. `email_verifications` - Email verification tokens
11. `email_logs` - Outbound email tracking
12. `user_events` - User behavior analytics
13. `jobs` - Laravel queue jobs (async processing)
14. `failed_jobs` - Failed queue jobs for debugging

### 4. Verify Migrations

Check migration status:

```bash
php artisan migrate:status
```

Expected output:
```
Migration name ................................................ Batch / Status
2025_10_08_000001_create_users_table ............................. [1] Ran
2025_10_08_000002_create_password_resets_table ................... [1] Ran
... (all 14 migrations should show "Ran")
```

## Migration Details

### Core Tables

#### users
- **Purpose**: Multi-provider authentication (email+password, Google OAuth)
- **Key Features**:
  - Onboarding progress tracking (0-4 steps)
  - AI generation limit counter (10/month with monthly reset)
  - Email verification status
  - Timezone support for user localization
- **Security**: Passwords bcrypt hashed, CASCADE delete for GDPR compliance

#### user_preferences
- **Purpose**: Store user travel preferences for AI personalization
- **Relationship**: 1:1 with users
- **Data**:
  - JSON array of interests (historia_kultura, przyroda_outdoor, etc.)
  - ENUM fields for practical parameters (pace, budget, transport, restrictions)
- **Usage**: Read on every AI generation (cached in Redis)

#### travel_plans
- **Purpose**: Core travel plan storage
- **Features**:
  - Soft delete support (deleted_at)
  - Status workflow: draft → planned → completed
  - Budget tracking with multi-currency support (PLN, USD, EUR)
  - Optional coordinates for map integration
- **Constraints**:
  - number_of_days: 1-30 (CHECK constraint)
  - number_of_people: 1-10 (CHECK constraint)

### Itinerary Structure

#### plan_days
- **Purpose**: Individual days within a travel plan
- **Relationship**: N:1 with travel_plans
- **Constraints**: UNIQUE(travel_plan_id, day_number)

#### plan_points
- **Purpose**: Sightseeing points/activities within each day
- **Relationship**: N:1 with plan_days
- **Features**:
  - Time-of-day organization (rano, poludnie, popoludnie, wieczor)
  - Google Maps URL integration
  - Optional coordinates for future map features
  - AI-generated descriptions and justifications
- **Constraints**: UNIQUE(plan_day_id, order_number)

### AI & Analytics

#### ai_generations
- **Purpose**: Track all AI generation attempts with metrics
- **Features**:
  - Cost tracking (tokens + USD cost with 4 decimal precision)
  - Status workflow: pending → processing → completed/failed
  - Error logging for debugging
- **Usage**: Only 'completed' status counts toward monthly limit

#### feedback
- **Purpose**: Collect user satisfaction feedback
- **Relationship**: 1:1 with travel_plans
- **Data**: Binary satisfaction + JSON array of issue categories

#### user_events
- **Purpose**: Behavioral analytics tracking
- **Events**: login, logout, onboarding_completed, plan_created, ai_generated, pdf_exported, feedback_submitted
- **Privacy**: No IP address or user agent tracking in MVP

### Email System

#### email_verifications
- **Purpose**: Email verification tokens with 24h expiry
- **Security**: Tokens are hashed (SHA256/bcrypt) before storage

#### email_logs
- **Purpose**: Audit trail for all outbound emails
- **Types**: verification, welcome, limit_warning, limit_reached, trip_reminder
- **Rate Limiting**: Indexed for "1 email per 5 minutes" queries

### Queue System

#### jobs
- **Purpose**: Laravel queue for async processing (AI generation, emails)
- **Driver**: Database (development) or Redis (production recommended)

#### failed_jobs
- **Purpose**: Store permanently failed jobs for debugging
- **Retry**: `php artisan queue:retry {uuid}`

## Foreign Key Relationships

All foreign keys use `ON DELETE CASCADE` for automatic cleanup:

```
users
  ├─ user_preferences (1:1)
  ├─ travel_plans (1:N)
  │   ├─ plan_days (1:N)
  │   │   └─ plan_points (1:N)
  │   ├─ ai_generations (1:N)
  │   ├─ feedback (1:1)
  │   └─ pdf_exports (1:N)
  ├─ email_verifications (1:N)
  ├─ email_logs (1:N)
  └─ user_events (1:N)
```

**GDPR Compliance**: Deleting a user cascades to all related data (hard delete).

## Indexes

Strategic indexes for query performance:

- **users**: email (unique), provider+provider_id (composite)
- **travel_plans**: user_id, status, created_at
- **ai_generations**: (user_id, created_at) for limit counting
- **plan_days**: (travel_plan_id, day_number) unique
- **plan_points**: (plan_day_id, order_number) unique
- **email_logs**: (user_id, email_type, sent_at) for rate limiting

## Rollback

### Roll Back Last Migration Batch

```bash
php artisan migrate:rollback
```

### Roll Back All Migrations

**WARNING**: This will delete all data!

```bash
php artisan migrate:reset
```

### Fresh Migration (Reset + Migrate)

**WARNING**: This will delete all data and re-run migrations!

```bash
php artisan migrate:fresh
```

### Fresh Migration with Seeders

```bash
php artisan migrate:fresh --seed
```

## Seeding Development Data

Run seeders to populate database with fake data for testing:

```bash
php artisan db:seed
```

This will create:
- 10-20 fake users with completed onboarding
- 50-100 travel plans in various statuses
- AI generation history with mock costs
- Feedback examples
- PDF export records

## Troubleshooting

### Error: "Syntax error or access violation: 1064"

**Cause**: MySQL version < 8.0.16 (CHECK constraints not supported)

**Solution**: Upgrade to MySQL 8.0.16+ or comment out CHECK constraints in migrations.

### Error: "SQLSTATE[42000]: Syntax error: 1071 Specified key was too long"

**Cause**: Incorrect charset/collation (should be utf8mb4)

**Solution**: Verify `DB_CHARSET=utf8mb4` and `DB_COLLATION=utf8mb4_unicode_ci` in `.env`

### Error: "Base table or view not found: 1146 Table 'vibetravels.users' doesn't exist"

**Cause**: Migrations not run

**Solution**: Run `php artisan migrate`

### Error: "SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row"

**Cause**: Foreign key constraint violation (parent record doesn't exist)

**Solution**: Ensure migrations run in order. Use `php artisan migrate:fresh` to reset.

## Production Deployment

### Pre-Deployment Checklist

1. ✅ Database backup before migration
2. ✅ Run migrations on staging environment first
3. ✅ Verify `.env` configuration (especially DB_* variables)
4. ✅ Test rollback procedure
5. ✅ Enable query logging for first 24h

### Production Migration Command

```bash
# With confirmation prompt
php artisan migrate

# Force without prompt (CI/CD pipelines)
php artisan migrate --force
```

### Post-Migration Verification

```bash
# Check migration status
php artisan migrate:status

# Verify table structure
php artisan db:show
php artisan db:table users

# Test application
php artisan tinker
>>> User::count()
>>> TravelPlan::count()
```

## Performance Optimization

### Recommended for Production

1. **Use Redis for cache and queue**:
   ```env
   CACHE_STORE=redis
   QUEUE_CONNECTION=redis
   ```

2. **Enable query caching** for frequently accessed data:
   - User preferences (1 hour TTL)
   - AI generation limits (15 minutes TTL)

3. **Monitor slow queries**:
   ```bash
   # Enable MySQL slow query log
   SET GLOBAL slow_query_log = 'ON';
   SET GLOBAL long_query_time = 1;
   ```

4. **Add indexes** if new query patterns emerge:
   ```php
   Schema::table('table_name', function (Blueprint $table) {
       $table->index('column_name');
   });
   ```

## Maintenance

### Monthly Tasks

- **Reset AI generation counters** (automated via cron):
  ```sql
  UPDATE users SET ai_generations_count_current_month = 0, ai_generations_reset_at = NULL;
  ```

- **Clean up expired email verification tokens**:
  ```sql
  DELETE FROM email_verifications WHERE expires_at < NOW() AND verified_at IS NULL;
  ```

- **Archive old user events** (if table grows too large):
  ```sql
  DELETE FROM user_events WHERE created_at < DATE_SUB(NOW(), INTERVAL 12 MONTH);
  ```

### Monitoring Queries

```sql
-- Active users count
SELECT COUNT(*) FROM users WHERE email_verified_at IS NOT NULL;

-- Plans by status
SELECT status, COUNT(*) FROM travel_plans GROUP BY status;

-- AI generation success rate
SELECT
    status,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM ai_generations), 2) as percentage
FROM ai_generations
GROUP BY status;

-- Average AI cost per plan
SELECT
    AVG(cost_usd) as avg_cost,
    SUM(cost_usd) as total_cost,
    COUNT(*) as total_generations
FROM ai_generations
WHERE status = 'completed';
```

## Additional Resources

- **Laravel Migrations Documentation**: https://laravel.com/docs/11.x/migrations
- **MySQL 8.0 Reference**: https://dev.mysql.com/doc/refman/8.0/en/
- **Database Planning Document**: `../.ai/db-plan.md`
- **Product Requirements**: `../.ai/prd.md`
- **Tech Stack**: `../.ai/tech-stack.md`

## Support

For questions or issues with database schema:
1. Check this README first
2. Review `../.ai/db-plan.md` for design decisions
3. Check migration files for inline documentation
4. Open issue in project repository

---

**Last Updated**: 2025-10-08
**Schema Version**: 1.0.0 (MVP)
**MySQL Version Required**: 8.0.16+
