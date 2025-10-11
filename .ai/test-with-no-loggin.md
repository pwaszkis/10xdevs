# 🚀 Quick Start - Testowanie bez logowania

## ⚡ Szybki start (5 minut)

### 1. Załaduj helpers

```bash
composer dump-autoload
```

### 2. Utwórz użytkownika testowego

```bash
php artisan dev:create-test-user
```

**Wynik:**
```
✅ Test user created successfully!
🔗 You can now test at: http://localhost:8000/dev/plans/create
```

### 3. Uruchom serwer

```bash
php artisan serve
```

### 4. Otwórz przeglądarkę

```
http://localhost:8000/dev/plans/create
```

**Gotowe!** 🎉 Formularz zadziała automatycznie z testowym użytkownikiem.

---

## 🔥 Szybkie komendy (Tinker)

### Otwórz Tinker

```bash
php artisan tinker
```

### 1. Utwórz testowy plan

```php
$plan = create_test_plan();
echo "Created plan ID: {$plan->id}\n";
```

### 2. Sprawdź limity

```php
$limits = check_limits();
print_r($limits);
```

### 3. Przetestuj AI (Mock mode)

```php
$plan = create_test_plan();
$result = test_ai_generation($plan->id);

echo "Tokens: {$result['tokens']}\n";
echo "Cost: \${$result['cost']}\n";
```

### 4. Wysłij do Queue

```bash
# Terminal 1: Worker
php artisan queue:work --queue=ai-generation --verbose

# Terminal 2: Tinker
php artisan tinker
```

```php
$plan = create_test_plan();
dispatch_generation($plan->id);
// Sprawdź terminal 1 - job się wykona!
```

### 5. Sprawdź rezultat

```php
$plan->refresh();
echo "Status: {$plan->status}\n";
echo "Days: {$plan->days->count()}\n";

// Pokaż dni
$plan->days->each(function($day) {
    echo "Day {$day->day_number}: {$day->title}\n";
    echo "  Points: {$day->points->count()}\n";
});
```

---

## 🌐 Dostępne trasy testowe

### Bez middleware (auto-login)

| Metoda | URL | Opis |
|--------|-----|------|
| GET | `/dev/plans/create` | Tworzenie nowego planu |
| GET | `/dev/plans/{id}/edit` | Edycja planu |

**Przykłady:**
```bash
# Nowy plan
http://localhost:8000/dev/plans/create

# Edycja planu ID=1
http://localhost:8000/dev/plans/1/edit
```

---

## 🧪 Scenariusze testowe

### Scenariusz 1: Podstawowy flow

```bash
# 1. Utwórz użytkownika
php artisan dev:create-test-user

# 2. Otwórz formularz
open http://localhost:8000/dev/plans/create

# 3. Wypełnij i zapisz jako szkic
# 4. Sprawdź w bazie
php artisan tinker
```

```php
\App\Models\TravelPlan::latest()->first();
```

### Scenariusz 2: AI Generation (Mock)

```bash
# 1. Ustaw mock mode w .env
OPENAI_USE_REAL_API=false

# 2. Otwórz Tinker
php artisan tinker
```

```php
// Utwórz plan i wygeneruj
$plan = create_test_plan();
$result = test_ai_generation($plan->id);

// Wyświetl rezultat
print_r($result['plan']);
```

### Scenariusz 3: Queue Testing

```bash
# Terminal 1: Worker
php artisan queue:work --queue=ai-generation --verbose

# Terminal 2: Dispatch
php artisan tinker
```

```php
$plan = create_test_plan();
dispatch_generation($plan->id);
// Obserwuj Terminal 1!
```

```bash
# Terminal 3: Sprawdź rezultat
php artisan tinker
```

```php
$plan = \App\Models\TravelPlan::latest()->first();
$plan->refresh();
echo "Status: {$plan->status}\n";
```

### Scenariusz 4: Testowanie limitów

```bash
php artisan tinker
```

```php
$user = \App\Models\User::first();

// Sprawdź początkowy stan
$limits = check_limits($user->id);
echo "{$limits['used']}/{$limits['limit']}\n";

// Symuluj 10 generowań
for($i = 1; $i <= 10; $i++) {
    $plan = create_test_plan($user->id);
    dispatch_generation($plan->id);
    echo "Dispatched #{$i}\n";
}

// Sprawdź po wszystkich
$limits = check_limits($user->id);
echo "Final: {$limits['used']}/{$limits['limit']}\n";
echo "Can generate: " . ($limits['can_generate'] ? 'YES' : 'NO') . "\n";
```

---

## 📦 Pomocnicze funkcje (helpers.php)

| Funkcja | Opis | Przykład |
|---------|------|----------|
| `create_test_plan($userId)` | Tworzy losowy plan testowy | `$plan = create_test_plan()` |
| `check_limits($userId)` | Sprawdza limity generowań | `$info = check_limits()` |
| `test_ai_generation($planId)` | Testuje AI generation | `$result = test_ai_generation(1)` |
| `dispatch_generation($planId)` | Wysyła job do kolejki | `dispatch_generation(1)` |
| `dev_login()` | Loguje pierwszego usera | `$user = dev_login()` |

---

## 🐛 Debugowanie

### Logi

```bash
# Laravel
tail -f storage/logs/laravel.log

# OpenAI (jeśli skonfigurowany)
tail -f storage/logs/openai.log

# Queue
php artisan queue:work --queue=ai-generation --verbose
```

### Baza danych

```php
// Ostatnie plany
\App\Models\TravelPlan::latest()->limit(5)->get();

// Ostatnie generowania
\App\Models\AIGeneration::latest()->with('travelPlan')->limit(5)->get();

// Failed jobs
DB::table('failed_jobs')->get();

// Sprawdź konkretny plan
$plan = \App\Models\TravelPlan::with(['days.points', 'aiGenerations'])->find(1);
```

### Clear wszystko

```bash
php artisan optimize:clear
# lub osobno:
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan queue:clear
```

---

## ⚙️ Konfiguracja

### Mock vs Real API

```env
# .env

# Mock mode (darmowe, szybkie testowanie)
OPENAI_USE_REAL_API=false

# Real API (wymaga klucza, kosztuje)
OPENAI_USE_REAL_API=true
OPENAI_API_KEY=sk-proj-xxxxx
```

### Queue Connection

```env
# Database (najprostsze)
QUEUE_CONNECTION=database

# Redis (szybsze, produkcja)
QUEUE_CONNECTION=redis
```

---

## ⚠️ WAŻNE: Bezpieczeństwo

### Przed produkcją usuń:

1. **Trasy DEV** z `routes/web.php`:
```php
// Usuń całą sekcję if (app()->environment(...))
```

2. **Komendę testową**:
```bash
rm app/Console/Commands/CreateTestUser.php
```

3. **Helpers** (opcjonalnie):
```bash
rm app/helpers.php
# i usuń z composer.json -> autoload -> files
```

4. **Dokumenty testowe**:
```bash
rm TESTING.md QUICKSTART.md
```

---

## 📚 Więcej informacji

- 📖 Pełny przewodnik: `TESTING.md`
- 🏗️ Plan implementacji: `.ai/generate-plan-plan.md`
- 📝 Standards: `.ai/coding-standards.md`

---

**Happy Coding! 🚀**
