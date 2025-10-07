# Szczegółowa instrukcja bootstrapowania projektu VibeTravels

## Wstęp

Ten dokument zawiera krok po kroku instrukcje jak uruchomić projekt VibeTravels od zera, używając Docker i przygotowanej konfiguracji.

## Architektura

Projekt wykorzystuje 5 kontenerów Docker:
1. **app** - PHP 8.3-FPM z Laravel 11
2. **nginx** - Serwer webowy
3. **mysql** - Baza danych MySQL 8
4. **redis** - Cache i kolejki
5. **mailhog** - Testowanie emaili (dev only)

## Krok po kroku

### 1. Przygotowanie środowiska

#### Zainstaluj Docker
```bash
# Linux (Ubuntu/Debian)
sudo apt-get update
sudo apt-get install docker.io docker-compose-plugin

# Dodaj użytkownika do grupy docker
sudo usermod -aG docker $USER
newgrp docker

# Sprawdź instalację
docker --version
docker compose version
```

#### Zainstaluj Make (opcjonalnie)
```bash
sudo apt-get install make
```

### 2. Sklonuj lub utwórz projekt

```bash
# Jeśli klonujesz z repo
git clone <repository-url> vibetravels
cd vibetravels

# Jeśli tworzysz nowy projekt
mkdir vibetravels
cd vibetravels
# Skopiuj wszystkie pliki konfiguracyjne z tego setupu
```

### 3. Konfiguracja środowiska

#### Utwórz plik .env
```bash
cp .env.example .env
```

#### Dostosuj .env do swoich potrzeb

**Ważne zmienne:**
```env
# Identyfikatory użytkownika (dla Linuxa)
USER_ID=1000        # Twoje UID (sprawdź: id -u)
GROUP_ID=1000       # Twoje GID (sprawdź: id -g)

# Baza danych
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=vibetravels
DB_USERNAME=vibetravels
DB_PASSWORD=root

# Redis
REDIS_HOST=redis
REDIS_PORT=6379

# Mail (MailHog)
MAIL_HOST=mailhog
MAIL_PORT=1025

# Xdebug (development)
XDEBUG_MODE=off     # Zmień na 'debug' gdy potrzebujesz debugowania
```

### 4. Opcja A: Automatyczny setup (z Make)

Jeśli masz zainstalowane `make`:

```bash
make setup
```

To wykona wszystkie kroki automatycznie. Przeskocz do kroku 7.

### 5. Opcja B: Manualny setup (bez Make)

#### Krok 1: Build kontenerów
```bash
docker compose build
```

To może zająć 5-10 minut przy pierwszym uruchomieniu.

#### Krok 2: Uruchom kontenery
```bash
docker compose up -d
```

#### Krok 3: Zainstaluj zależności Composer
```bash
docker compose exec app composer install
```

#### Krok 4: Wygeneruj klucz aplikacji
```bash
docker compose exec app php artisan key:generate
```

#### Krok 5: Uruchom migracje
```bash
docker compose exec app php artisan migrate
```

### 6. Instalacja Laravel (pierwsza instalacja)

Jeśli to zupełnie nowy projekt i nie masz jeszcze Laravel:

```bash
# Zainstaluj Laravel przez Composer w kontenerze
docker compose run --rm app composer create-project laravel/laravel .

# Zainstaluj dodatkowe paczki
docker compose run --rm app composer require livewire/livewire
docker compose run --rm app composer require laravel/breeze --dev
docker compose run --rm app composer require wireui/wireui
docker compose run --rm app composer require openai-php/laravel
docker compose run --rm app composer require spatie/laravel-pdf

# Zainstaluj Breeze
docker compose exec app php artisan breeze:install blade
```

### 7. Frontend setup

#### Zainstaluj zależności npm
```bash
# Z Makefile
make npm

# Lub bezpośrednio
docker compose run --rm node npm install
```

#### Uruchom dev server (opcjonalnie)
```bash
# W osobnym terminalu
docker compose run --rm --service-ports node npm run dev
```

Lub użyj profilu dev z docker compose:
```bash
docker compose --profile dev up -d
```

### 8. Sprawdź czy wszystko działa

#### Sprawdź status kontenerów
```bash
docker compose ps
```

Wszystkie kontenery powinny być w stanie "Up".

#### Otwórz aplikację
```
http://localhost
```

Powinieneś zobaczyć domyślną stronę Laravel.

#### Sprawdź logi
```bash
docker compose logs -f app
```

### 9. Dodatkowa konfiguracja

#### Permissions (Linux)
Jeśli masz problemy z uprawnieniami:
```bash
# W kontenerze app
docker compose exec app chown -R www:www storage bootstrap/cache

# Na hoście (jeśli potrzeba)
sudo chown -R $USER:$USER .
```

#### Cache storage directories
```bash
docker compose exec app php artisan storage:link
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

#### Seedowanie bazy (opcjonalnie)
```bash
docker compose exec app php artisan db:seed
```

### 10. Konfiguracja IDE (PHPStorm)

#### Xdebug setup

1. W pliku `.env` ustaw:
```env
XDEBUG_MODE=debug
```

2. Restart kontenera:
```bash
docker compose restart app
```

3. PHPStorm: `Settings → PHP → Debug`
   - Port: `9003`
   - Zaznacz "Can accept external connections"

4. PHPStorm: `Settings → PHP → Servers`
   - Name: `Docker`
   - Host: `localhost`
   - Port: `80`
   - Debugger: `Xdebug`
   - Path mappings:
     - Project root → `/var/www`

5. Ustaw breakpoint i włącz "Start Listening for PHP Debug Connections"

#### Composer path
`Settings → PHP → Composer`:
- Composer executable: `docker compose exec app composer`

### 11. Testowanie instalacji

#### Uruchom testy
```bash
# Z Makefile
make test

# Lub bezpośrednio
docker compose exec app php artisan test
```

#### Sprawdź PHPStan
```bash
make phpstan
```

#### Sprawdź code style
```bash
make cs-check
```

## Najczęstsze problemy

### Problem: Port 80 już zajęty
```bash
# Sprawdź co używa portu 80
sudo lsof -i :80

# Zmień port w docker-compose.yml
ports:
  - "8080:80"  # Zamiast 80:80
```

### Problem: Permission denied na storage/
```bash
docker compose exec app chmod -R 777 storage bootstrap/cache
```

### Problem: MySQL connection refused
```bash
# Sprawdź czy kontener mysql działa
docker compose ps mysql

# Sprawdź logi
docker compose logs mysql

# Restart
docker compose restart mysql
```

### Problem: Composer memory limit
W `docker/php/php.ini`:
```ini
memory_limit = 512M  # Zwiększ z 256M
```

Potem rebuild:
```bash
docker compose build app
docker compose restart app
```

### Problem: Xdebug nie działa
```bash
# Sprawdź czy xdebug jest włączony
docker compose exec app php -v
# Powinno pokazać "with Xdebug"

# Sprawdź konfigurację
docker compose exec app php --ini | grep xdebug

# Sprawdź logi
docker compose exec app cat /tmp/xdebug.log
```

## Komendy do codziennej pracy

```bash
# Start pracy
make up

# Dostęp do kontenera
make shell

# Zobacz logi
make logs

# Restart
make restart

# Stop pracy
make down

# Fresh database
make fresh

# Testy + QA
make quality
```

## Następne kroki

Po udanym bootstrapie:

1. **Skonfiguruj Google OAuth** (opcjonalnie)
   - Utwórz projekt w Google Cloud Console
   - Dodaj credentials do `.env`

2. **Skonfiguruj OpenAI API** (dla AI features)
   - Uzyskaj API key z OpenAI
   - Dodaj do `.env`

3. **Zacznij development**
   - `make shell` - dostęp do kontenera
   - `php artisan make:livewire ComponentName`
   - `php artisan make:model ModelName -m`

4. **Przeczytaj główny README.md**
   - Zawiera więcej informacji o strukturze projektu
   - Przydatne linki do dokumentacji

## Wsparcie

Jeśli masz problemy:
1. Sprawdź logi: `make logs`
2. Sprawdź status: `docker compose ps`
3. Restart: `make restart`
4. Fresh start: `make down && make up`
5. Full rebuild: `docker compose down -v && make setup`

---

**Powodzenia w developmencie! 🚀**
