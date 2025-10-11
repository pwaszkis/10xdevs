# 🧪 Przewodnik testowania - CreatePlanForm

Instrukcje testowania funkcjonalności tworzenia planów podróży bez pełnego systemu logowania.

## 📋 Spis treści

1. [Przygotowanie środowiska](#przygotowanie-środowiska)
2. [Opcja 1: Trasy DEV (najszybsze)](#opcja-1-trasy-dev-najszybsze)
3. [Opcja 2: Tinker (backend testing)](#opcja-2-tinker-backend-testing)
4. [Opcja 3: Testy jednostkowe](#opcja-3-testy-jednostkowe)
5. [Testowanie Queue Workers](#testowanie-queue-workers)

---

## 🚀 Przygotowanie środowiska

### 1. Sprawdź konfigurację

```bash
# Sprawdź plik .env
cat .env | grep -E "(APP_ENV|OPENAI|QUEUE)"
```

Upewnij się, że masz:
```env
APP_ENV=local
QUEUE_CONNECTION=database  # lub redis
OPENAI_API_KEY=your-key    # lub zostaw puste dla mock mode
OPENAI_USE_REAL_API=false  # false = mock, true = real API
```

### 2. Uruchom migracje (jeśli jeszcze nie)

```bash
php artisan migrate
```

### 3. Utwórz użytkownika testowego

```bash
php artisan dev:create-test-user
```

Lub z customowymi danymi:
```bash
php artisan dev:create-test-user \
  --email=admin@test.com \
  --name="Admin User" \
  --password=secret123
```

**Wynik:**
```
✅ Test user created successfully!

┌───────────┬──────────────────────────────┐
│ Field     │ Value                        │
├───────────┼──────────────────────────────┤
│ ID        │ 1                            │
│ Name      │ Test User                    │
│ Email     │ test@example.com             │
│ Password  │ password                     │
│ Preferences│ Created with default values │
└───────────┴──────────────────────────────┘

🔗 You can now test at:
   - Create Plan: http://localhost/dev/plans/create
   - Login: Email=test@example.com, Password=password
```

---

## ✨ Opcja 1: Trasy DEV (najszybsze)

### Automatyczne logowanie

Trasy DEV automatycznie logują pierwszego użytkownika z bazy:

```bash
# Uruchom serwer deweloperski
php artisan serve
```

### Dostępne trasy testowe:

#### 1. **Tworzenie nowego planu**
```
http://localhost:8000/dev/plans/create
```

**Co testować:**
- ✅ Wypełnianie formularza
- ✅ Walidację real-time (wpisz błędne dane)
- ✅ Kalkulację daty powrotu (zmień liczbę dni)
- ✅ Kalkulację budżetu (zmień budżet/osoby)
- ✅ Wyświetlanie limitów generowań
- ✅ Zapis jako szkic (💾 przycisk)
- ✅ Generowanie AI (🤖 przycisk)

#### 2. **Edycja istniejącego planu**
```bash
# Najpierw stwórz plan (przez formularz lub tinker)
# Następnie:
http://localhost:8000/dev/plans/{ID}/edit
```

**Przykład:**
```
http://localhost:8000/dev/plans/1/edit
```

### Scenariusze testowe:

#### **Scenariusz 1: Zapisanie szkicu**
1. Otwórz `/dev/plans/create`
2. Wypełnij tylko wymagane pola:
   - Tytuł: "Wakacje w Rzymie"
   - Destynacja: "Rzym, Włochy"
   - Data wyjazdu: jutro
   - Liczba dni: 5
   - Liczba osób: 2
3. Kliknij "💾 Zapisz jako szkic"
4. **Oczekiwany rezultat**: Przekierowanie + sukces message

#### **Scenariusz 2: Generowanie planu AI (Mock)**
1. Ustaw w `.env`: `OPENAI_USE_REAL_API=false`
2. Otwórz `/dev/plans/create`
3. Wypełnij formularz kompletnie
4. Kliknij "🤖 Generuj plan AI"
5. **Oczekiwany rezultat**:
   - Przekierowanie do widoku planu
   - Status "generating" → "planned"
   - Job wykonany asynchronicznie

#### **Scenariusz 3: Limit generowań**
1. Zmień w `LimitService.php`: `MONTHLY_LIMIT = 2`
2. Wygeneruj 2 plany
3. Przy 3. próbie **oczekiwany rezultat**:
   - Komunikat o limicie
   - Disabled przycisk "Generuj"
   - Możliwość zapisu szkicu

---

## 🔧 Opcja 2: Tinker (backend testing)

### Uruchom Tinker

```bash
php artisan tinker
```

### Testowanie serwisów

#### **TravelService**

```php
// Pobierz pierwszego użytkownika
$user = \App\Models\User::first();

// Utwórz serwis
$travelService = app(\App\Services\TravelService::class);

// Stwórz plan
$plan = $travelService->create([
    'user_id' => $user->id,
    'title' => 'Test Plan',
    'destination' => 'Paris',
    'departure_date' => now()->addDays(7)->format('Y-m-d'),
    'number_of_days' => 5,
    'number_of_people' => 2,
    'budget_per_person' => 1000,
    'budget_currency' => 'EUR',
    'user_notes' => 'Test notes',
    'status' => 'draft',
]);

// Sprawdź ID
echo "Created plan ID: {$plan->id}\n";

// Pobierz plany użytkownika
$plans = $travelService->getUserTravels($user->id);
echo "Total plans: {$plans->count()}\n";
```

#### **LimitService**

```php
$user = \App\Models\User::first();
$limitService = app(\App\Services\LimitService::class);

// Sprawdź limity
$used = $limitService->getGenerationCount($user->id);
$canGenerate = $limitService->canGenerate($user->id);
$info = $limitService->getLimitInfo($user->id);

echo "Used: {$used}/10\n";
echo "Can generate: " . ($canGenerate ? 'YES' : 'NO') . "\n";
print_r($info);
```

#### **PreferenceService**

```php
$user = \App\Models\User::first();
$prefService = app(\App\Services\PreferenceService::class);

// Pobierz preferencje
$prefs = $prefService->getUserPreferences($user->id);
print_r($prefs);

// Zaktualizuj preferencje
$updated = $prefService->updatePreferences($user->id, [
    'travel_pace' => 'active',
    'budget_level' => 'premium',
]);
```

#### **AIGenerationService**

```php
$user = \App\Models\User::first();
$plan = \App\Models\TravelPlan::first();

$aiService = app(\App\Services\AIGenerationService::class);
$prefService = app(\App\Services\PreferenceService::class);

$prefs = $prefService->getUserPreferences($user->id);

// Test generowania (Mock mode)
$result = $aiService->generatePlan($plan, $prefs);

echo "Tokens used: {$result['tokens']}\n";
echo "Cost: \${$result['cost']}\n";
echo "Duration: {$result['duration']}s\n";
print_r($result['plan']);
```

---

## 🧪 Opcja 3: Testy jednostkowe

### Utwórz test

```bash
php artisan make:test TravelPlanCreationTest
```

### Przykładowy test:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TravelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelPlanCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_draft_plan(): void
    {
        $user = User::factory()->create();
        $travelService = app(TravelService::class);

        $plan = $travelService->create([
            'user_id' => $user->id,
            'title' => 'Test Plan',
            'destination' => 'Paris',
            'departure_date' => now()->addDays(7)->format('Y-m-d'),
            'number_of_days' => 5,
            'number_of_people' => 2,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('travel_plans', [
            'id' => $plan->id,
            'status' => 'draft',
        ]);
    }

    public function test_limit_service_tracks_generations(): void
    {
        $user = User::factory()->create();
        $limitService = app(\App\Services\LimitService::class);

        $this->assertEquals(0, $limitService->getGenerationCount($user->id));
        $this->assertTrue($limitService->canGenerate($user->id));
    }
}
```

### Uruchom testy:

```bash
php artisan test
# lub konkretny test:
php artisan test --filter TravelPlanCreationTest
```

---

## 🔄 Testowanie Queue Workers

### 1. Konfiguracja Queue

```bash
# W .env
QUEUE_CONNECTION=database
```

```bash
# Utwórz tabelę jobs (jeśli nie istnieje)
php artisan queue:table
php artisan migrate
```

### 2. Uruchom Worker

```bash
# W osobnym terminalu
php artisan queue:work --queue=ai-generation
```

### 3. Testowanie generowania

#### Terminal 1: Worker
```bash
php artisan queue:work --queue=ai-generation --verbose
```

#### Terminal 2: Dispatch job
```bash
php artisan tinker
```

```php
$user = \App\Models\User::first();
$plan = \App\Models\TravelPlan::factory()->create(['user_id' => $user->id]);
$prefService = app(\App\Services\PreferenceService::class);
$limitService = app(\App\Services\LimitService::class);

$prefs = $prefService->getUserPreferences($user->id);
$aiGen = $limitService->incrementGenerationCount($user->id, $plan->id);

\App\Jobs\GenerateTravelPlanJob::dispatch(
    $plan->id,
    $user->id,
    $aiGen->id,
    $prefs
)->onQueue('ai-generation');

echo "Job dispatched! Check worker terminal...\n";
```

#### Sprawdź rezultat:
```php
$plan->refresh();
echo "Status: {$plan->status}\n";  // Should be 'planned'
echo "Days count: {$plan->days->count()}\n";
```

---

## 🐛 Debugging

### Logi

```bash
# Laravel log
tail -f storage/logs/laravel.log

# OpenAI log (jeśli skonfigurowany)
tail -f storage/logs/openai.log
```

### Database debugging

```bash
php artisan tinker
```

```php
// Ostatnie plany
\App\Models\TravelPlan::latest()->limit(5)->get();

// Ostatnie generowania
\App\Models\AIGeneration::latest()->limit(5)->get();

// Failed jobs
DB::table('failed_jobs')->get();
```

### Clear cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

## ⚠️ WAŻNE: Przed produkcją

### Usuń trasy DEV z `routes/web.php`:

```php
// Usuń całą sekcję:
if (app()->environment(['local', 'development'])) {
    // ... trasy /dev/*
}
```

### Usuń komendę testową:

```bash
rm app/Console/Commands/CreateTestUser.php
```

Lub dodaj guard:
```php
public function handle(): int
{
    if (!app()->environment(['local', 'development'])) {
        $this->error('This command is only available in development!');
        return Command::FAILURE;
    }
    // ...
}
```

---

## 📚 Dalsze kroki

Po przetestowaniu podstawowej funkcjonalności:

1. ✅ Zaimplementuj pełny system logowania/rejestracji
2. ✅ Dodaj middleware `onboarding.completed`
3. ✅ Usuń/zabezpiecz trasy DEV
4. ✅ Skonfiguruj prawdziwe OpenAI API
5. ✅ Uruchom Queue Worker na produkcji (Supervisor)
6. ✅ Dodaj testy E2E (Laravel Dusk)

---

**Happy Testing! 🚀**
