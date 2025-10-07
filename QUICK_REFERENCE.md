# VibeTravels - Quick Reference Card

## 🚦 Szybki Start

```bash
# Pierwsze uruchomienie
make setup

# Codzienne uruchamianie
make up

# Zatrzymanie
make down
```

## 🐳 Docker

```bash
# Status kontenerów
docker compose ps

# Logi wszystkich serwisów
docker compose logs -f

# Logi konkretnego serwisu
docker compose logs -f app

# Restart wszystkich
make restart

# Restart jednego serwisu
docker compose restart app

# Przebuduj kontenery
docker compose build
docker compose up -d

# Shell w kontenerze
make shell
# lub
docker compose exec app bash

# Wykonaj komendę w kontenerze
docker compose exec app <komenda>
```

## 🎨 Artisan

```bash
# Skróty
docker compose exec app php artisan <komenda>
# Lub z kontenera (make shell):
php artisan <komenda>

# Najpopularniejsze komendy
php artisan make:model Post -mcr        # Model + Migration + Controller + Resource
php artisan make:livewire PostForm      # Komponent Livewire
php artisan make:migration create_posts_table
php artisan make:seeder PostSeeder
php artisan make:factory PostFactory
php artisan make:request PostRequest
php artisan make:policy PostPolicy

# Migracje
php artisan migrate                     # Uruchom migracje
php artisan migrate:fresh              # Drop all + migrate
php artisan migrate:fresh --seed       # + seeders
php artisan migrate:rollback           # Cofnij ostatnią migracją
php artisan migrate:status             # Status migracji

# Cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear             # Wszystkie cache

# Cache dla produkcji
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## 📦 Composer

```bash
# W kontenerze
composer install
composer update
composer require vendor/package
composer require --dev vendor/package
composer dump-autoload

# Z hosta
docker compose run --rm app composer install
docker compose run --rm app composer require vendor/package
```

## 📦 NPM

```bash
# Development
docker compose run --rm node npm install
docker compose run --rm node npm run dev

# Production build
docker compose run --rm node npm run build

# Watch mode
docker compose run --rm --service-ports node npm run dev
```

## 🧪 Testing

```bash
# Wszystkie testy
make test
# lub
docker compose exec app php artisan test

# Konkretny test
docker compose exec app php artisan test --filter=UserTest

# Z pokryciem kodu
make test-coverage

# Parallel testing
docker compose exec app php artisan test --parallel
```

## 🔍 Quality Assurance

```bash
# PHPStan
make phpstan
./vendor/bin/phpstan analyse

# PHP CS Fixer
make cs-fix          # Napraw automatycznie
make cs-check        # Tylko sprawdź

# PHP CodeSniffer
make phpcs           # Sprawdź
make phpcs-fix       # Napraw

# Wszystko naraz
make quality
```

## 🗄️ Baza Danych

```bash
# Migracje
make migrate

# Fresh database z seedami
make fresh

# Tylko seedery
make seed

# MySQL CLI
docker compose exec mysql mysql -u vibetravels -proot vibetravels

# Backup
docker compose exec mysql mysqldump -u root -proot vibetravels > backup.sql

# Restore
docker compose exec -T mysql mysql -u root -proot vibetravels < backup.sql
```

## 🔧 Redis

```bash
# Redis CLI
docker compose exec redis redis-cli

# Sprawdź klucze
docker compose exec redis redis-cli KEYS '*'

# Wyczyść wszystko
docker compose exec redis redis-cli FLUSHALL

# Monitor
docker compose exec redis redis-cli MONITOR
```

## 📬 Queue

```bash
# Start workera
make queue
# lub
docker compose exec app php artisan queue:work

# Z verbose output
docker compose exec app php artisan queue:work -v

# Tylko jedno zadanie
docker compose exec app php artisan queue:work --once

# Restart workerów
docker compose exec app php artisan queue:restart

# Zobacz failed jobs
docker compose exec app php artisan queue:failed

# Retry failed job
docker compose exec app php artisan queue:retry <job-id>
```

## 🐛 Debug (Xdebug)

```bash
# Włącz Xdebug
# W .env:
XDEBUG_MODE=debug

# Restart
make restart

# Wyłącz (dla performance)
# W .env:
XDEBUG_MODE=off

# Coverage mode
XDEBUG_MODE=coverage
make test-coverage

# Sprawdź status
docker compose exec app php -v
docker compose exec app php --ini | grep xdebug
```

## 🔐 Permissions (Linux)

```bash
# Napraw permissions storage/
docker compose exec app chmod -R 777 storage bootstrap/cache

# Zmień ownership
docker compose exec app chown -R www:www storage bootstrap/cache

# Na hoście
sudo chown -R $USER:$USER .
```

## 📝 Tinker (REPL)

```bash
make tinker
# lub
docker compose exec app php artisan tinker

# Przykłady w tinkerze:
>>> User::count()
>>> User::factory()->create()
>>> Cache::get('key')
>>> Redis::ping()
```

## 🌐 URLs

```
Aplikacja:     http://localhost
MailHog UI:    http://localhost:8025
MySQL:         localhost:3306
Redis:         localhost:6379
```

## 🎯 Livewire

```bash
# Utwórz komponent
php artisan make:livewire Counter
php artisan make:livewire forms/PostForm

# Lista komponentów
php artisan livewire:list

# Publish config
php artisan livewire:publish --config

# W Blade:
<livewire:counter />
@livewire('forms.post-form')
```

## 📊 Użyteczne skróty

```bash
# Tail Laravel logs
docker compose exec app tail -f storage/logs/laravel.log

# Sprawdź rozmiar kontenerów
docker system df
docker compose ps --all --size

# Wyczyść nieużywane obrazy/kontenery
docker system prune -a

# Export/Import bazy
make shell
php artisan db:export --database=mysql
php artisan db:import backup.sql
```

## 🔄 Git Workflow

```bash
# Przed commitem
make quality                # Uruchom wszystkie testy/checkers

# Commity
git add .
git commit -m "feat: add post creation feature"

# Konwencje commitów
feat:     Nowa funkcjonalność
fix:      Bugfix
refactor: Refaktoring
test:     Dodanie testów
docs:     Dokumentacja
style:    Formatowanie
chore:    Maintenance
```

## 💡 Troubleshooting

```bash
# Port 80 zajęty
sudo lsof -i :80
sudo systemctl stop apache2

# Kontenery nie startują
docker compose down
docker compose up -d

# Rebuild wszystkiego od zera
docker compose down -v
docker system prune -a
make setup

# Cache issues
php artisan optimize:clear
composer dump-autoload

# Permission issues
chmod -R 777 storage bootstrap/cache

# MySQL nie działa
docker compose restart mysql
docker compose logs mysql
```

## 📚 Przydatne aliasy do .bashrc/.zshrc

```bash
alias dce='docker compose exec'
alias dcr='docker compose run --rm'
alias art='docker compose exec app php artisan'
alias tinker='docker compose exec app php artisan tinker'
alias composer='docker compose exec app composer'
alias npm='docker compose run --rm node npm'
alias ptest='docker compose exec app php artisan test'
```

---

**Szybszy development = więcej kawy ☕**
