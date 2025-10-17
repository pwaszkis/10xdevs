# Konfiguracja Email - SendGrid + DigitalOcean

## Problem
SendGrid wymaga zweryfikowanego adresu nadawcy. Błąd:
```
Expected response code "250" but got code "550", with message "550 The from address does not match a verified Sender Identity."
```

**Przyczyna**: Hosting aplikacji na DigitalOcean ≠ hosting poczty email. Domena `przem-podroze.pl` nie ma skonfigurowanego odbierania emaili.

---

## Opcja 1: Użyj innego adresu do wysyłki (ZALECANE - najszybsze ⚡)

Zamiast `hello@przem-podroze.pl`, użyj adresu email, do którego masz dostęp.

### Kroki:
1. **Zweryfikuj swój prawdziwy adres email w SendGrid**:
   - Zaloguj się: https://app.sendgrid.com/
   - Settings → Sender Authentication → Verify a Single Sender
   - Dodaj swój email (np. `twoj.email@gmail.com`)
   - Kliknij link weryfikacyjny w emailu od SendGrid

2. **Zaktualizuj production `.env`**:
   ```bash
   MAIL_FROM_ADDRESS="twoj.email@gmail.com"  # Twój zweryfikowany email
   MAIL_FROM_NAME="VibeTravels - Przem Podróże"
   ```

3. **Wyczyść cache na serwerze produkcyjnym**:
   ```bash
   docker compose exec app php artisan config:clear
   docker compose exec app php artisan config:cache
   ```

### Zalety:
- ✅ Działa natychmiast (5 minut)
- ✅ Nie wymaga konfiguracji DNS
- ✅ Darmowe

### Wady:
- ❌ Emaile wysyłane z prywatnego adresu (nie z domeny firmowej)

---

## Opcja 2: Email Forwarding z ImprovMX (DARMOWY) 🎯

Odbieraj emaile na `hello@przem-podroze.pl` i przekierowuj je na swój prawdziwy email.

### Krok 1: Zarejestruj się w ImprovMX
1. Idź na: https://improvmx.com/
2. Utwórz konto (darmowe)
3. Dodaj domenę: `przem-podroze.pl`
4. Skonfiguruj alias:
   - Z: `hello@przem-podroze.pl`
   - Do: `twoj.email@gmail.com`

### Krok 2: Dodaj MX Records w DigitalOcean
1. Zaloguj się do DigitalOcean
2. Networking → Domains → `przem-podroze.pl`
3. Dodaj następujące DNS records:

```
Type: MX
Hostname: @
Mail server: mx1.improvmx.com
Priority: 10
TTL: 3600

Type: MX
Hostname: @
Mail server: mx2.improvmx.com
Priority: 20
TTL: 3600
```

4. **Usuń stare MX records** jeśli istnieją

### Krok 3: Zweryfikuj domenę w SendGrid
1. SendGrid → Settings → Sender Authentication
2. Kliknij: **Authenticate Your Domain**
3. Wybierz DNS host: Other
4. Wprowadź domenę: `przem-podroze.pl`
5. SendGrid pokaże DNS records do dodania (SPF, DKIM, CNAME)
6. Skopiuj je do DigitalOcean DNS:

**Przykład (wartości będą inne dla Twojego konta)**:
```
Type: TXT
Hostname: em1234
Value: [długi string od SendGrid]

Type: CNAME
Hostname: s1._domainkey
Value: s1.domainkey.u12345.wl123.sendgrid.net

Type: CNAME
Hostname: s2._domainkey
Value: s2.domainkey.u12345.wl123.sendgrid.net
```

### Krok 4: Poczekaj na propagację DNS (15 min - 48h)
```bash
# Sprawdź MX records
dig przem-podroze.pl MX

# Sprawdź TXT records (SPF)
dig przem-podroze.pl TXT
```

### Krok 5: Zaktualizuj production `.env`
```bash
MAIL_FROM_ADDRESS="hello@przem-podroze.pl"
MAIL_FROM_NAME="VibeTravels"
```

### Krok 6: Wyczyść cache
```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan config:cache
```

### Zalety:
- ✅ Profesjonalny wygląd (`hello@przem-podroze.pl`)
- ✅ Darmowe
- ✅ Odbierasz emaile na swój prawdziwy adres

### Wady:
- ❌ Wymaga konfiguracji DNS (30 min)
- ❌ Propagacja DNS może potrwać do 48h

---

## Opcja 3: Pełny Email Hosting (PŁATNY)

Jeśli potrzebujesz pełnego hostingu email (wysyłka + odbiór + webmail).

### Google Workspace
- Cena: ~23 PLN/mies. za jedno konto
- URL: https://workspace.google.com/
- Pełny Gmail z Twoją domeną
- Wymaga weryfikacji domeny przez DNS

### Zoho Mail
- Cena: ~7 PLN/mies. za jedno konto
- URL: https://www.zoho.com/mail/
- Tańsza alternatywa dla Google Workspace
- Darmowy plan dla 1 użytkownika (do 5 GB)

### Kroki (dla Google Workspace):
1. Zarejestruj się na workspace.google.com
2. Zweryfikuj domenę `przem-podroze.pl`
3. Dodaj MX records Google do DigitalOcean DNS
4. Utwórz konto email: `hello@przem-podroze.pl`
5. Zweryfikuj domenę w SendGrid (jak w Opcji 2, Krok 3)
6. Zaktualizuj production `.env`

### Zalety:
- ✅ Pełny profesjonalny email
- ✅ Webmail, kalendarz, Drive
- ✅ Reputacja Google dla deliverability

### Wady:
- ❌ Kosztuje ~23 PLN/mies.
- ❌ Wymaga konfiguracji DNS

---

## Rekomendacja

| Scenariusz | Najlepsza opcja |
|------------|-----------------|
| **Testowanie / MVP** | Opcja 1 (Gmail) |
| **Produkcja, małe użycie** | Opcja 2 (ImprovMX) |
| **Profesjonalna firma** | Opcja 3 (Google Workspace) |

---

## Aktualna konfiguracja projektu

### Development (.env)
```bash
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_FROM_ADDRESS="hello@example.com"
```
- Emaile trafiają do MailHog: http://localhost:8025
- Nie wysyła prawdziwych emaili

### Production (.env.production.example)
```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=  # SendGrid API key (SG.xxx)
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="hello@przem-podroze.pl"
MAIL_FROM_NAME="VibeTravels"
```

---

## Troubleshooting

### SendGrid API Key nie działa
```bash
# Sprawdź czy klucz jest poprawny (w kontenerze)
docker compose exec app php artisan tinker
>>> config('mail.mailers.smtp.password')
```

### DNS nie propaguje się
```bash
# Sprawdź MX records
dig przem-podroze.pl MX +short

# Sprawdź SPF/DKIM
dig przem-podroze.pl TXT +short
dig s1._domainkey.przem-podroze.pl CNAME +short
```

### Emaile trafiają do SPAM
- Zweryfikuj domenę w SendGrid (SPF + DKIM)
- Upewnij się że MAIL_FROM_ADDRESS jest zweryfikowany
- Dodaj link "unsubscribe" do emaili
- Unikaj słów spamowych w temacie

### Test wysyłki emaili
```bash
docker compose exec app php artisan tinker
>>> Mail::raw('Test email', function($message) {
...     $message->to('twoj.email@gmail.com')->subject('Test');
... });
```

---

## Przydatne linki

- SendGrid Dashboard: https://app.sendgrid.com/
- SendGrid Sender Identity: https://sendgrid.com/docs/for-developers/sending-email/sender-identity/
- ImprovMX: https://improvmx.com/
- DigitalOcean DNS: https://cloud.digitalocean.com/networking/domains
- Google Workspace: https://workspace.google.com/
- Zoho Mail: https://www.zoho.com/mail/

---

## Następne kroki

1. **Wybierz opcję** (1, 2 lub 3)
2. **Wykonaj kroki** dla wybranej opcji
3. **Przetestuj wysyłkę** emaili:
   ```bash
   docker compose exec app php artisan tinker
   >>> Mail::raw('Test', fn($m) => $m->to('twoj.email@gmail.com')->subject('Test'));
   ```
4. **Sprawdź logi** w przypadku błędów:
   ```bash
   docker compose exec app tail -f storage/logs/laravel.log
   ```

---

**Dokument stworzony**: 2025-10-17
**Projekt**: VibeTravels (przem-podroze.pl)
**Środowisko**: Docker + DigitalOcean + SendGrid
