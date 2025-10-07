# Deployment Guide - VibeTravels

## Przegląd strategii deployment

Zgodnie z tech-stack.md, projekt używa:
- **GitHub Actions** do CI/CD
- **DigitalOcean** do hostowania za pomocą Docker

## GitHub Actions CI/CD

### Setup

Plik `.github/workflows/ci.yml` zawiera konfigurację CI która:
1. Uruchamia testy na każdy push/PR do `main` i `develop`
2. Sprawdza quality code (PHPStan, CS Fixer, PHPCS)
3. Buduje frontend assets

### Secrets w GitHub

Dodaj następujące secrets w GitHub (Settings → Secrets → Actions):

```
DIGITALOCEAN_ACCESS_TOKEN
DOCKER_USERNAME
DOCKER_PASSWORD
```

## DigitalOcean Deployment

### Przygotowanie Droplet

1. **Utwórz Droplet**
   - Ubuntu 22.04 LTS
   - Minimum: 2GB RAM / 1 vCPU (Basic)
   - Zalecane dla produkcji: 4GB RAM / 2 vCPU

2. **Zainstaluj Docker**
```bash
# SSH do dropletu
ssh root@your-droplet-ip

# Instalacja Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# Instalacja Docker Compose
apt-get install docker-compose-plugin

# Sprawdź instalację
docker --version
docker compose version
```

3. **Dodaj swap (dla małych dropletów)**
```bash
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile none swap sw 0 0' | tee -a /etc/fstab
```

### Deployment z Docker Compose

#### 1. Utwórz strukturę projektu

```bash
mkdir -p /var/www/vibetravels
cd /var/www/vibetravels
```

#### 2. Skopiuj pliki projektu

Opcja A: Clone z Git
```bash
git clone https://github.com/your-username/vibetravels.git .
```

Opcja B: Użyj rsync z lokalnej maszyny
```bash
rsync -avz --exclude 'vendor' --exclude 'node_modules' \
  ./ root@your-droplet-ip:/var/www/vibetravels/
```

#### 3. Konfiguracja produkcyjna

Utwórz `.env` dla produkcji:
```bash
cp .env.example .env
nano .env
```

Ważne zmienne dla produkcji:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://vibetravels.com

DB_PASSWORD=<strong-password>

REDIS_PASSWORD=<redis-password>

MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.vibetravels.com
MAILGUN_SECRET=<mailgun-api-key>
MAILGUN_ENDPOINT=api.eu.mailgun.net

OPENAI_API_KEY=<your-key>
AI_USE_REAL_API=true
OPENAI_MODEL=gpt-4o-mini

GOOGLE_CLIENT_ID=<production-client-id>
GOOGLE_CLIENT_SECRET=<production-secret>
GOOGLE_REDIRECT_URI=https://vibetravels.com/auth/google/callback
```

#### 4. Utwórz docker-compose.production.yml

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    image: vibetravels-app:latest
    restart: always
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./docker/php/php.ini:/usr/local/etc/php/conf.d/custom.ini
    networks:
      - vibetravels
    environment:
      - XDEBUG_MODE=off
    depends_on:
      - mysql
      - redis

  nginx:
    image: nginx:alpine
    restart: always
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./docker/nginx/production.conf:/etc/nginx/conf.d/default.conf
      - ./certbot/conf:/etc/letsencrypt
      - ./certbot/www:/var/www/certbot
    networks:
      - vibetravels
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    restart: always
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_USER: ${DB_USERNAME}
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - vibetravels
    command: --default-authentication-plugin=mysql_native_password

  redis:
    image: redis:7-alpine
    restart: always
    volumes:
      - redis_data:/data
    networks:
      - vibetravels
    command: redis-server --requirepass ${REDIS_PASSWORD} --appendonly yes

  queue-worker:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    restart: always
    working_dir: /var/www
    volumes:
      - ./:/var/www
    networks:
      - vibetravels
    depends_on:
      - mysql
      - redis
    command: php artisan queue:work --tries=3 --timeout=90

  scheduler:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    restart: always
    working_dir: /var/www
    volumes:
      - ./:/var/www
    networks:
      - vibetravels
    depends_on:
      - mysql
      - redis
    command: bash -c "while true; do php artisan schedule:run --verbose --no-interaction & sleep 60; done"

networks:
  vibetravels:
    driver: bridge

volumes:
  mysql_data:
  redis_data:
```

#### 5. Konfiguracja Nginx dla produkcji

Utwórz `docker/nginx/production.conf`:
```nginx
server {
    listen 80;
    server_name vibetravels.com www.vibetravels.com;

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }

    location / {
        return 301 https://$host$request_uri;
    }
}

server {
    listen 443 ssl http2;
    server_name vibetravels.com www.vibetravels.com;
    root /var/www/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/vibetravels.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/vibetravels.com/privkey.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 256 16k;
        fastcgi_busy_buffers_size 256k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 20M;
}
```

#### 6. Setup SSL z Let's Encrypt

```bash
# Zainstaluj certbot
apt-get update
apt-get install certbot

# Uzyskaj certyfikat
certbot certonly --webroot -w /var/www/vibetravels/certbot/www \
  -d vibetravels.com -d www.vibetravels.com \
  --email admin@vibetravels.com --agree-tos --no-eff-email

# Auto-renewal
echo "0 12 * * * /usr/bin/certbot renew --quiet" | crontab -
```

#### 7. Build i uruchomienie

```bash
# Build obrazów
docker compose -f docker-compose.production.yml build

# Uruchom kontenery
docker compose -f docker-compose.production.yml up -d

# Zainstaluj dependencies
docker compose -f docker-compose.production.yml exec app composer install --optimize-autoloader --no-dev

# Zoptymalizuj Laravel
docker compose -f docker-compose.production.yml exec app php artisan config:cache
docker compose -f docker-compose.production.yml exec app php artisan route:cache
docker compose -f docker-compose.production.yml exec app php artisan view:cache

# Uruchom migracje
docker compose -f docker-compose.production.yml exec app php artisan migrate --force

# Storage link
docker compose -f docker-compose.production.yml exec app php artisan storage:link
```

### Automatyczny Deployment z GitHub Actions

Utwórz `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Deploy to DigitalOcean
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.DROPLET_IP }}
          username: ${{ secrets.DROPLET_USERNAME }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /var/www/vibetravels
            git pull origin main
            docker compose -f docker-compose.production.yml down
            docker compose -f docker-compose.production.yml build
            docker compose -f docker-compose.production.yml up -d
            docker compose -f docker-compose.production.yml exec -T app composer install --optimize-autoloader --no-dev
            docker compose -f docker-compose.production.yml exec -T app php artisan migrate --force
            docker compose -f docker-compose.production.yml exec -T app php artisan config:cache
            docker compose -f docker-compose.production.yml exec -T app php artisan route:cache
            docker compose -f docker-compose.production.yml exec -T app php artisan view:cache
```

## Monitoring i Maintenance

### Logi

```bash
# Wszystkie logi
docker compose -f docker-compose.production.yml logs -f

# Laravel logs
docker compose -f docker-compose.production.yml exec app tail -f storage/logs/laravel.log

# Nginx logs
docker compose -f docker-compose.production.yml logs nginx
```

### Backup bazy danych

```bash
# Backup
docker compose -f docker-compose.production.yml exec mysql mysqldump \
  -u root -p${DB_PASSWORD} ${DB_DATABASE} > backup-$(date +%Y%m%d).sql

# Restore
docker compose -f docker-compose.production.yml exec -T mysql mysql \
  -u root -p${DB_PASSWORD} ${DB_DATABASE} < backup.sql
```

### Aktualizacje

```bash
# Pull najnowszych zmian
cd /var/www/vibetravels
git pull origin main

# Rebuild i restart
docker compose -f docker-compose.production.yml down
docker compose -f docker-compose.production.yml up -d --build

# Aktualizuj dependencies
docker compose -f docker-compose.production.yml exec app composer install --no-dev --optimize-autoloader

# Uruchom migracje
docker compose -f docker-compose.production.yml exec app php artisan migrate --force

# Cache
docker compose -f docker-compose.production.yml exec app php artisan optimize
```

## Koszty (DigitalOcean)

### MVP Phase (100-500 użytkowników)

- **Droplet**: $12-24/miesiąc (2-4GB RAM)
- **Spaces/Storage**: ~$5/miesiąc (opcjonalnie)
- **Bandwidth**: Included (1TB+)
- **Total**: ~$12-30/miesiąc

### OpenAI API

- GPT-4o-mini: ~$0.02-0.05/plan
- 5-20 generacji/dzień = $3-30/miesiąc

### Email (Mailgun)

- 5,000 emaili gratis
- Po tym: ~$5-10/miesiąc

**Total MVP costs: ~$20-70/miesiąc**

## Troubleshooting Production

### High memory usage
```bash
# Restart queue worker
docker compose -f docker-compose.production.yml restart queue-worker

# Clear cache
docker compose -f docker-compose.production.yml exec app php artisan cache:clear
```

### Slow response times
```bash
# Check opcache
docker compose -f docker-compose.production.yml exec app php -i | grep opcache

# Enable more aggressive caching
# W docker/php/php.ini zwiększ:
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
```

### Database issues
```bash
# Check connections
docker compose -f docker-compose.production.yml exec mysql mysql -u root -p -e "SHOW PROCESSLIST;"

# Optimize tables
docker compose -f docker-compose.production.yml exec app php artisan db:optimize
```

---

**Deployment made easy! 🚀**
