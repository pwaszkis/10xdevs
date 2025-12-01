# 🔧 Instrukcja wdrożenia poprawek stabilności

Data: 2025-12-01
Problem: Wysokie zużycie pamięci (100%), zawieszanie się strony, timeout 524

## 📋 Podsumowanie zmian

### Zmiany w kodzie (do zdeployowania):
1. **Nginx DNS fix** - naprawiono błąd "host not found in upstream app"
2. **MySQL optymalizacja** - zmniejszono zużycie pamięci z 376MB do ~180MB
3. **Skrypty monitorujące** - automatyczny restart przy wysokim zużyciu RAM

### Zdiagnozowane problemy:
- ❌ Droplet ma tylko **1GB RAM** (nie 2GB) - MySQL zjadał 39% pamięci
- ❌ Nginx crashował podczas startu bo nie mógł rozwiązać DNS "app"
- ❌ `APP_DEBUG=true` w produkcji (security risk)
- ✅ Worker i Redis działają poprawnie
- ✅ Endpoint `/health` działa

---

## 🚀 CZĘŚĆ 1: Natychmiastowe działania (na serwerze)

### 1.1 Wyłącz APP_DEBUG w .env

```bash
ssh deploy@przem-podroze.pl
cd /var/www/vibetravels

# Edytuj .env i zmień APP_DEBUG=true na false
nano .env
# Znajdź linię: APP_DEBUG=true
# Zmień na: APP_DEBUG=false
# Zapisz: Ctrl+X, Y, Enter

# Wyczyść cache
docker compose -f docker-compose.production.yml exec app php artisan config:clear
docker compose -f docker-compose.production.yml exec app php artisan config:cache
```

### 1.2 Sprawdź aktualną ilość RAM

```bash
# Sprawdź dostępną pamięć
free -h

# Sprawdź plan droplet
curl -X GET -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_DIGITALOCEAN_TOKEN" \
  "https://api.digitalocean.com/v2/droplets/524514828" | jq '.droplet.size.slug'
```

**⚠️ WAŻNE:** Jeśli wynik to `s-1vcpu-1gb`, masz droplet za $6/mo (1GB RAM).
**Zalecany upgrade:** `s-1vcpu-2gb` ($12/mo) lub `s-2vcpu-2gb` ($18/mo)

---

## 🚀 CZĘŚĆ 2: Deployment zmian z repozytorium

### 2.1 Commituj i wypchnij zmiany lokalnie

```bash
# Lokalnie (na swoim komputerze)
cd /home/global/projekty/10xdevs

git add docker/nginx/production.conf
git add docker/mysql/my.cnf
git add scripts/monitor-memory.sh
git add scripts/check-workers.sh
git add DEPLOYMENT-FIX.md

git commit -m "fix: resolve Nginx DNS issues and optimize MySQL memory usage

- Fix Nginx 'host not found' error with dynamic upstream resolution
- Add Docker DNS resolver (127.0.0.11) for container communication
- Reduce MySQL innodb_buffer_pool_size from 256M to 128M
- Optimize MySQL connections and memory settings for 1GB RAM
- Add memory monitoring script with auto-restart at 85% threshold
- Add worker health check script to detect Redis connection issues
- Update deployment documentation with stability fixes"

git push origin main
```

### 2.2 Wdróż zmiany na serwerze

```bash
ssh deploy@przem-podroze.pl
cd /var/www/vibetravels

# Pobierz najnowsze zmiany
git pull origin main

# Restart kontenerów (zastosuje nową konfigurację Nginx i MySQL)
docker compose -f docker-compose.production.yml down
docker compose -f docker-compose.production.yml up -d

# Poczekaj 30 sekund na start
sleep 30

# Sprawdź status
docker compose -f docker-compose.production.yml ps
```

**Oczekiwany wynik:**
```
vibetravels-app         healthy
vibetravels-mysql       healthy
vibetravels-nginx       healthy  ← POWINIEN BYĆ HEALTHY!
vibetravels-redis       healthy
vibetravels-scheduler   healthy
vibetravels-worker      healthy
```

### 2.3 Sprawdź czy Nginx jest healthy

```bash
# Sprawdź logi Nginx (powinno być bez błędów "host not found")
docker compose -f docker-compose.production.yml logs nginx | grep -i "emerg\|error" | tail -20

# Sprawdź endpoint /health
curl -I http://localhost/health
# Powinno zwrócić: HTTP/1.1 200 OK

# Sprawdź z zewnątrz
curl -I https://przem-podroze.pl/health
# Powinno zwrócić: HTTP/2 200
```

### 2.4 Sprawdź zużycie pamięci MySQL (powinno spaść)

```bash
# Przed: MySQL zjadał ~376 MB
# Po: Powinno być ~180-200 MB

docker stats --no-stream | grep mysql
```

---

## 🚀 CZĘŚĆ 3: Konfiguracja monitoringu (opcjonalnie, ale zalecane)

### 3.1 Ustaw skrypty jako wykonywalne

```bash
ssh deploy@przem-podroze.pl
cd /var/www/vibetravels

chmod +x scripts/monitor-memory.sh
chmod +x scripts/check-workers.sh
```

### 3.2 Dodaj cron job dla monitoringu pamięci

```bash
# Dodaj do crontab (jako użytkownik deploy)
crontab -e

# Dodaj na końcu pliku:
*/5 * * * * /var/www/vibetravels/scripts/monitor-memory.sh >> /var/log/memory-monitor.log 2>&1
*/10 * * * * /var/www/vibetravels/scripts/check-workers.sh >> /var/log/worker-monitor.log 2>&1

# Zapisz: Ctrl+X, Y, Enter
```

**Co to robi:**
- Co 5 minut sprawdza zużycie RAM, restartuje kontenery jeśli >85%
- Co 10 minut sprawdza status workera i restartuje przy błędach Redis

### 3.3 Sprawdź czy cron działa

```bash
# Testuj ręcznie
/var/www/vibetravels/scripts/monitor-memory.sh

# Sprawdź logi (po 5-10 minutach)
tail -f /var/log/memory-monitor.log
tail -f /var/log/worker-monitor.log
```

---

## 🎯 CZĘŚĆ 4: Długoterminowe rozwiązanie - Upgrade Droplet

### Dlaczego upgrade jest zalecany?

Nawet z optymalizacjami, **1GB RAM to za mało** dla stacku:
- MySQL (128-200 MB)
- Redis (5-10 MB)
- PHP-FPM app (40-60 MB)
- Worker (30-50 MB)
- Scheduler (2-5 MB)
- Nginx (3-5 MB)
- System (100-150 MB)

**TOTAL:** ~300-480 MB bez ruchu, **600-800 MB pod obciążeniem**

### Jak zrobić upgrade (w DigitalOcean)?

1. Zaloguj się: https://cloud.digitalocean.com/droplets/524514828/resize
2. Wybierz: **Resize** → **CPU and RAM only** (bez dodatkowego dysku)
3. Wybierz plan:
   - `s-1vcpu-2gb` ($12/mo) ← **ZALECANE MINIMUM**
   - `s-2vcpu-2gb` ($18/mo) ← Lepiej dla 100+ użytkowników
4. Kliknij **Resize Droplet**
5. Poczekaj 2-5 minut na restart
6. Sprawdź: `free -h` (powinno pokazać ~2GB total)

**⚠️ Uwaga:** Podczas upgrade serwer będzie niedostępny przez 2-5 minut.

---

## ✅ Weryfikacja po wdrożeniu

### Checklist:

```bash
# 1. Wszystkie kontenery healthy?
docker compose -f docker-compose.production.yml ps
# Wszystkie powinny być "healthy"

# 2. Nginx bez błędów DNS?
docker compose -f docker-compose.production.yml logs nginx | grep "emerg"
# Nie powinno być "host not found"

# 3. MySQL zużywa mniej pamięci?
docker stats --no-stream | grep mysql
# Powinno być ~180-200 MB (było 376 MB)

# 4. APP_DEBUG wyłączony?
docker compose -f docker-compose.production.yml exec app php artisan tinker
>>> config('app.debug');
// Powinno zwrócić: false

# 5. Strona działa?
curl -I https://przem-podroze.pl
# HTTP/2 200

# 6. Endpoint /health działa?
curl https://przem-podroze.pl/health
# {"status":"ok",...}

# 7. Dostępna pamięć?
free -h
# available powinno być >200 MB
```

---

## 📊 Oczekiwane rezultaty

### Przed poprawkami:
- ❌ RAM: 67 MB free / 961 MB total (93% used)
- ❌ Nginx: unhealthy (host not found)
- ❌ MySQL: 376 MB (39% całej pamięci)
- ❌ Strona: timeout 524 po kilku godzinach

### Po poprawkach:
- ✅ RAM: 400-500 MB free / 961 MB total (~50% used)
- ✅ Nginx: healthy
- ✅ MySQL: ~180-200 MB (~20% pamięci)
- ✅ Monitoring: auto-restart przy >85% RAM

### Po upgrade do 2GB:
- ✅ RAM: 1200-1400 MB free / 2 GB total (~30% used)
- ✅ Stabilność: brak timeoutów przez tygodnie
- ✅ Margines bezpieczeństwa: 70% wolnej pamięci

---

## 🆘 Troubleshooting

### Problem: Nginx nadal unhealthy po deployment

```bash
# Sprawdź dokładny błąd
docker compose -f docker-compose.production.yml logs nginx | tail -50

# Spróbuj rebuild
docker compose -f docker-compose.production.yml up -d --force-recreate nginx

# Sprawdź endpoint health
docker compose -f docker-compose.production.yml exec nginx wget -q -O - http://localhost/health
```

### Problem: MySQL nie startuje po zmianie konfiguracji

```bash
# Sprawdź logi
docker compose -f docker-compose.production.yml logs mysql | tail -50

# Jeśli błąd innodb_log_file_size, usuń stare logi
docker compose -f docker-compose.production.yml down
docker volume rm vibetravels_mysql_data
# UWAGA: To usunie WSZYSTKIE dane! Zrób backup najpierw:
docker compose -f docker-compose.production.yml exec mysql mysqldump -u root -p vibetravels > backup.sql

# Lub przywróć starą wartość w my.cnf
```

### Problem: Worker nadal ma błędy Redis

```bash
# Sprawdź czy Redis działa
docker compose -f docker-compose.production.yml exec redis redis-cli ping
# Powinno zwrócić: PONG

# Restart worker
docker compose -f docker-compose.production.yml restart worker

# Sprawdź logi (powinno być bez błędów "getaddrinfo")
docker compose -f docker-compose.production.yml logs --tail=30 worker
```

---

## 📞 Support

Jeśli problemy się utrzymują:

1. Sprawdź logi: `docker compose -f docker-compose.production.yml logs --tail=100`
2. Sprawdź status: `docker compose -f docker-compose.production.yml ps`
3. Sprawdź pamięć: `free -h` i `docker stats`
4. Zrestartuj wszystko: `docker compose -f docker-compose.production.yml restart`
5. Jeśli nic nie pomaga: Power Cycle droplet w DigitalOcean

---

**Powodzenia! 🚀**
