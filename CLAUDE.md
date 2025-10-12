# VibeTravels - Claude Code Reference

> **IMPORTANT**: This project runs in Docker containers. All commands must be executed IN containers, not on the host.

## 🐳 Docker Environment

**Architecture**:
- `app` - PHP 8.3 + Laravel 11 (main application)
- `mysql` - MySQL 8 (database)
- `redis` - Redis (cache/queue)
- `node` - Node.js (for npm/build tasks)
- `mailhog` - Email testing UI

**URLs**:
- Application: http://localhost
- MailHog: http://localhost:8025

## 🎯 Command Execution Rules

### ✅ CORRECT: Use Make commands or Docker Compose

```bash
# Preferred: Make commands (they handle Docker internally)
make test
make phpstan
make shell

# Alternative: Docker Compose exec/run
docker compose exec app php artisan test
docker compose run --rm node npm install
```

### ❌ INCORRECT: Direct commands on host

```bash
# DON'T DO THIS - these won't work!
php artisan test          # ❌ No PHP on host
composer install          # ❌ No Composer on host
npm install               # ❌ Wrong Node version on host
```

## 📋 Essential Commands

### Application Commands

```bash
# Access app container shell
make shell

# Run Laravel Artisan commands
docker compose exec app php artisan <command>

# Examples:
docker compose exec app php artisan migrate
docker compose exec app php artisan tinker
docker compose exec app php artisan make:livewire ComponentName
```

### Testing & Quality

```bash
# Run tests (PHPUnit)
make test
# or: docker compose exec app php artisan test

# Static analysis (PHPStan)
make phpstan
# or: docker compose exec app ./vendor/bin/phpstan analyse

# Fix code style (Laravel Pint)
make cs-fix
# or: docker compose exec app ./vendor/bin/pint

# Check code style
make cs-check
# or: docker compose exec app ./vendor/bin/pint --test

# Run all quality checks
make quality
```

### Database Operations

```bash
# Run migrations
docker compose exec app php artisan migrate

# Fresh database with seeders
docker compose exec app php artisan migrate:fresh --seed

# Access MySQL CLI
docker compose exec mysql mysql -u vibetravels -proot vibetravels
```

### Dependencies

```bash
# Composer (PHP dependencies)
docker compose run --rm app composer install
docker compose run --rm app composer require vendor/package

# NPM (Node dependencies)
docker compose run --rm node npm install
docker compose run --rm node npm run build
docker compose run --rm node npm run dev
```

### Logs & Debugging

```bash
# View all logs
make logs

# View specific service logs
docker compose logs -f app

# View Laravel logs
docker compose exec app tail -f storage/logs/laravel.log
```

## 🏗️ Project Structure

```
app/
├── Http/Controllers/     # Controllers (including API)
├── Livewire/            # Livewire components
│   ├── Dashboard.php
│   ├── Plans/           # Plan-related components
│   └── Onboarding/      # Onboarding wizard
├── Models/              # Eloquent models
├── Services/            # Business logic
│   ├── OpenAI/          # AI integration
│   ├── TravelPlanService.php
│   └── LimitService.php
└── Mail/                # Email templates

database/
├── migrations/          # Database migrations
└── SCHEMA.md           # Full schema documentation

resources/
├── views/
│   ├── livewire/       # Livewire component views
│   ├── pdf/            # PDF templates
│   └── auth/           # Auth views
├── css/
└── js/

routes/
├── web.php             # Web routes
├── api.php             # API routes (Sanctum auth)
└── auth.php            # Auth routes (Breeze)
```

## 🔑 Key Project Features Status

### ✅ Implemented
- Authentication (email + Google OAuth)
- Onboarding wizard with preferences
- Dashboard with plan listing
- Create/save/delete travel plans
- AI generation (OpenAI integration)
- Plan display (day-by-day itinerary)
- Feedback system
- PDF export
- 10/month AI limit tracking

### ⚠️ Partially Implemented
- Email notifications (templates may be incomplete)
- Plan filtering/sorting UI
- Limit counter display in UI
- Regenerate plan UI

### ❌ Not Yet Implemented
- Analytics dashboard
- Rate limiting
- Automated cron jobs (monthly reset, auto-complete trips)
- User events tracking implementation
- Error handling for AI failures

## 🛠️ Common Workflows

### Adding a New Feature

```bash
# 1. Create necessary components
docker compose exec app php artisan make:livewire Feature/ComponentName
docker compose exec app php artisan make:model ModelName -m

# 2. Write code

# 3. Run tests
make test

# 4. Check code quality
make quality

# 5. Commit
git add .
git commit -m "feat: add feature description"
```

### Database Changes

```bash
# 1. Create migration
docker compose exec app php artisan make:migration create_table_name

# 2. Edit migration file in database/migrations/

# 3. Run migration
docker compose exec app php artisan migrate

# 4. If needed, create seeder
docker compose exec app php artisan make:seeder TableSeeder
```

### Testing AI Integration

```bash
# Use mock API (default in development)
# In .env: AI_USE_REAL_API=false

# Use real OpenAI API
# In .env: AI_USE_REAL_API=true
# In .env: OPENAI_API_KEY=sk-...

# Test in Tinker
docker compose exec app php artisan tinker
>>> $service = app(\App\Services\OpenAI\OpenAIService::class);
>>> // Test AI calls
```

### Debugging

```bash
# Check application logs
docker compose exec app tail -f storage/logs/laravel.log

# Check container health
docker compose ps

# Restart specific service
docker compose restart app

# Clear all caches
docker compose exec app php artisan optimize:clear

# Access Tinker REPL
docker compose exec app php artisan tinker
>>> User::count()
>>> TravelPlan::with('days.points')->find(1)
```

## 📊 Database Schema Quick Reference

**Main Tables**:
- `users` - User accounts (with OAuth support)
- `user_preferences` - Travel preferences (interests, pace, budget)
- `travel_plans` - Trip plans (title, destination, dates)
- `plan_days` - Individual days in itinerary
- `plan_points` - Points of interest within each day
- `ai_generations` - AI generation tracking (tokens, costs)
- `travel_plan_feedback` - User feedback on generated plans
- `pdf_exports` - PDF export tracking

**Key Relationships**:
- User → UserPreference (1:1)
- User → TravelPlan (1:many)
- TravelPlan → PlanDay (1:many)
- PlanDay → PlanPoint (1:many)
- TravelPlan → Feedback (1:1 per user)

**Status Flow**:
```
draft → planned → completed
```

## 🔄 Git Conventions

```bash
# Commit types
feat:     New feature
fix:      Bug fix
refactor: Code refactoring
test:     Adding/updating tests
docs:     Documentation
style:    Code formatting
chore:    Maintenance
```

## ⚡ Performance Tips

- Use `make` commands when available (they're optimized)
- Run multiple commands in one `docker compose exec` when possible:
  ```bash
  docker compose exec app bash -c "php artisan migrate && php artisan db:seed"
  ```
- Use `docker compose run --rm` for one-off commands (auto-cleanup)
- Cache clearing is needed after config changes:
  ```bash
  docker compose exec app php artisan config:clear
  ```

## 🚨 Troubleshooting

**Problem**: Permission denied in storage/
```bash
docker compose exec app chmod -R 777 storage bootstrap/cache
```

**Problem**: Port 80 already in use
```bash
# Check what's using the port
sudo lsof -i :80
# Stop conflicting service or change port in docker-compose.yml
```

**Problem**: Database connection refused
```bash
docker compose restart mysql
docker compose logs mysql
# Verify DB_HOST=mysql in .env (not localhost!)
```

**Problem**: Assets not loading
```bash
docker compose run --rm node npm run build
```

**Problem**: Need fresh start
```bash
docker compose down -v
docker compose build --no-cache
make setup
```

## 📝 Important Notes

1. **Always use Docker commands** - The host machine doesn't have PHP/Composer/correct Node version
2. **Database host is `mysql`** - Not `localhost` or `127.0.0.1` in .env
3. **Redis host is `redis`** - Not `localhost` in .env
4. **Use `make shell`** - When you need multiple commands in the container
5. **Check logs first** - `make logs` or `docker compose logs app` for debugging
6. **AI is mocked by default** - Set `AI_USE_REAL_API=true` to use real OpenAI API
7. **Email goes to MailHog** - Check http://localhost:8025 for sent emails

## 🎓 Laravel-Specific Notes

- This is **Laravel 11** with **Livewire 3**
- Authentication: **Laravel Breeze** + **Socialite** (Google OAuth)
- Queue driver: **Redis** (configured in .env)
- Cache driver: **Redis**
- Email: **Mailgun** in production, **MailHog** in development
- PDF: **Spatie Laravel PDF** (Chromium-based)
- AI: **OpenAI PHP Laravel** package

## 📚 Additional Resources

- Full setup: `SETUP.md`
- Command reference: `QUICK_REFERENCE.md`
- Deployment: `DEPLOYMENT.md`
- Database schema: `database/SCHEMA.md`
- PRD (requirements): `.ai/prd.md`
