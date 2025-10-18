# Testing Guide - VibeTravels

Kompletny przewodnik po testowaniu aplikacji VibeTravels.

## Spis treści

- [Przegląd](#przegląd)
- [Typy testów](#typy-testów)
- [Uruchamianie testów](#uruchamianie-testów)
- [Testy przeglądarkowe (Dusk)](#testy-przeglądarkowe-dusk)
- [Testowanie z podglądem VNC](#testowanie-z-podglądem-vnc)
- [Pisanie nowych testów](#pisanie-nowych-testów)
- [Najlepsze praktyki](#najlepsze-praktyki)
- [Rozwiązywanie problemów](#rozwiązywanie-problemów)

## Przegląd

VibeTravels posiada kompleksowy zestaw testów obejmujący trzy poziomy:

| Typ testu | Liczba testów | Czas wykonania | Uruchamiane w CI/CD |
|-----------|---------------|----------------|---------------------|
| **Unit** | ~15 | <1s | ✅ Tak |
| **Feature** | ~50 | 3-5s | ✅ Tak |
| **Browser (Dusk)** | 28 | 60-80s | ❌ Nie - ręcznie |

**Łącznie:** 93+ testów, 150+ asercji

## Typy testów

### 1. Unit Tests (`tests/Unit/`)

Testują pojedyncze klasy i metody w izolacji, bez interakcji z bazą danych.

**Przykłady:**
- `tests/Unit/Models/UserTest.php` - Testowanie modelu User
- `tests/Unit/Services/LimitServiceTest.php` - Testowanie serwisu limitów AI

**Uruchamianie:**
```bash
# Wszystkie testy jednostkowe
docker compose exec app php artisan test --testsuite=Unit

# Konkretny plik
docker compose exec app php artisan test tests/Unit/Models/UserTest.php
```

### 2. Feature Tests (`tests/Feature/`)

Testują kompletne funkcjonalności z interakcją z bazą danych.

**Pokrycie:**
- ✅ Autentykacja (login, rejestracja, OAuth)
- ✅ Proces onboardingu
- ✅ Zarządzanie planami podróży (CRUD)
- ✅ Generowanie AI (z zamockowanym OpenAI)
- ✅ Eksport PDF
- ✅ System feedbacku
- ✅ Powiadomienia email

**Uruchamianie:**
```bash
# Wszystkie testy feature
docker compose exec app php artisan test --testsuite=Feature

# Konkretna kategoria
docker compose exec app php artisan test tests/Feature/Auth/
docker compose exec app php artisan test tests/Feature/Plans/
```

### 3. Browser Tests - Laravel Dusk (`tests/Browser/`)

Testy end-to-end symulujące rzeczywiste interakcje użytkownika w przeglądarce Chrome.

**Pokrycie (28 testów, 92 asercje):**
- ✅ **Autentykacja** (10 testów) - login, rejestracja, walidacja
- ✅ **Onboarding** (3 testy) - kreator onboardingu, walidacja
- ✅ **Dashboard** (6 testów) - pusty stan, lista planów, filtry
- ✅ **Tworzenie planów** (5 testów) - szkice, budżety, walidacja
- ✅ **Pełne ścieżki użytkownika** (2 testy) - kompleksowe przepływy
- ✅ **Strony przykładowe** (2 testy) - strona główna, login

**⚠️ WAŻNE:** Testy Dusk NIE są uruchamiane w CI/CD!

**Powody:**
1. ⏱️ **Czas wykonania** - 60-80 sekund vs 3-5 sekund dla unit/feature
2. 🌐 **Zależności zewnętrzne** - Mogą wywoływać rzeczywiste API
3. 💰 **Zasoby** - Wymagają kontenera Chrome
4. 🎯 **Cel** - Walidacja przed wydaniem, nie przy każdym commicie

## Uruchamianie testów

### Szybkie polecenia

```bash
# Testy jednostkowe + feature (SZYBKIE ~3-5s)
make test
docker compose exec app php artisan test

# Testy przeglądarkowe (WOLNE ~60-80s)
make dusk
docker compose exec app php artisan dusk

# Wszystkie testy jakości kodu (bez Dusk)
make quality

# Wszystko (quality + dusk)
make quality && make dusk
```

### Testy z pokryciem kodu

```bash
# Generuj raport pokrycia
make test-coverage

# Raport zostanie wygenerowany w coverage/
# Otwórz: coverage/index.html
```

### Filtrowanie testów

```bash
# Konkretny plik testowy
docker compose exec app php artisan test tests/Feature/Plans/PlanCreationTest.php

# Konkretna metoda testowa
docker compose exec app php artisan test --filter=test_user_can_create_plan

# Grupa testów
docker compose exec app php artisan test --group=auth
```

## Testy przeglądarkowe (Dusk)

### Uruchamianie testów Dusk

```bash
# Wszystkie testy Dusk
make dusk
docker compose exec app php artisan dusk

# Konkretny katalog
docker compose exec app php artisan dusk tests/Browser/Auth/

# Konkretny plik
docker compose exec app php artisan dusk tests/Browser/Plans/PlanCreationTest.php

# Konkretna metoda
docker compose exec app php artisan dusk --filter=test_user_can_login
```

### Struktura testów Dusk

```
tests/Browser/
├── Auth/
│   ├── LoginTest.php                 # 5 testów logowania
│   └── RegistrationTest.php          # 5 testów rejestracji
├── Onboarding/
│   └── OnboardingFlowTest.php        # 3 testy kreatora onboardingu
├── Dashboard/
│   └── DashboardTest.php             # 6 testów dashboardu
├── Plans/
│   └── PlanCreationTest.php          # 5 testów tworzenia planów
├── CompleteUserJourneyTest.php       # 2 testy pełnych ścieżek
├── ExampleTest.php                   # 2 testy przykładowe
└── screenshots/                      # Zrzuty ekranu z błędów (gitignored)
```

### Zrzuty ekranu z błędów

Gdy test Dusk nie powiedzie się, automatycznie generowany jest zrzut ekranu:

```
tests/Browser/screenshots/
├── failure-Tests_Browser_Auth_LoginTest_test_user_can_login-0.png
└── failure-Tests_Browser_Dashboard_DashboardTest_test_shows_plans-0.png
```

**Uwaga:** Katalog `screenshots/` jest w `.gitignore` - zrzuty nie są commitowane.

## Testowanie z podglądem VNC

### Co to jest tryb `--browse`?

Tryb `--browse` pozwala oglądać testy wykonywane na żywo w przeglądarce Chrome przez połączenie VNC.

### Jak uruchomić?

```bash
# 1. Uruchom testy z trybem browse
docker compose exec app php artisan dusk --browse

# 2. Otwórz przeglądarkę i wejdź na:
# URL: http://localhost:7900/
# Hasło: secret

# 3. Obserwuj wykonywanie testów na żywo!
```

### Konkretne testy z podglądem

```bash
# Jeden konkretny test (szybsze debugowanie)
docker compose exec app php artisan dusk --browse \
  tests/Browser/CompleteUserJourneyTest.php \
  --filter=test_complete_user_journey_from_login_to_plan_management

# Cały plik testowy
docker compose exec app php artisan dusk --browse tests/Browser/Auth/LoginTest.php

# Wszystkie testy autentykacji
docker compose exec app php artisan dusk --browse tests/Browser/Auth/
```

### Przypadki użycia trybu VNC

- 🐛 **Debugowanie** - Zobacz dokładnie, gdzie test się nie powodzi
- 👀 **Demonstracje** - Pokaż funkcjonalności interesariuszom
- 🎓 **Nauka** - Zrozum wizualnie zachowanie testów
- 🔍 **Inspekcja** - Sprawdź UI podczas wykonywania testu

### Wskazówki

- Testy są **wolniejsze** z `--browse` - to celowe, żebyś mógł obserwować
- Nie zamykaj okna VNC przed zakończeniem testów
- `Ctrl+C` w terminalu przerywa testy
- Jeśli VNC się rozłącza - uruchom testy ponownie

## Pisanie nowych testów

### Generowanie testów

```bash
# Test jednostkowy
docker compose exec app php artisan make:test --unit Services/NewServiceTest

# Test feature
docker compose exec app php artisan make:test Plans/UpdatePlanTest

# Test Dusk
docker compose exec app php artisan dusk:make Profile/EditProfileTest
```

### Szablon testu Dusk

```php
<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ExampleTest extends DuskTestCase
{
    use DatabaseTruncation;

    public function test_user_can_perform_action(): void
    {
        $this->browse(function (Browser $browser) {
            // Przygotuj dane testowe
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'onboarding_completed' => true,
                'onboarding_completed_at' => now(),
            ]);

            $user->preferences()->create([
                'interests_categories' => ['historia_kultura'],
                'travel_pace' => 'umiarkowane',
                'budget_level' => 'standardowy',
            ]);

            // Wykonaj test
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertSee('Witaj w VibeTravels!')
                ->click('a[href="/plans/create"]')
                ->assertPathIs('/plans/create');
        });
    }
}
```

### Praca z formularzami Livewire

Dla pól reaktywnych Livewire używaj JavaScript `$set()`:

```php
// ❌ NIE DZIAŁA z wire:model.live
$browser->type('input[wire\\:model\\.live="departure_date"]', '2025-12-01');

// ✅ DZIAŁA - użyj $set()
$browser->script("
    const component = window.Livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id'));
    component.\$set('departure_date', '2025-12-01');
    component.\$set('number_of_days', 5);
");

$browser->pause(1000); // Poczekaj na aktualizację Livewire
```

### Asercje w testach Dusk

```php
// Sprawdź ścieżkę URL
$browser->assertPathIs('/dashboard');
$browser->assertPathBeginsWith('/plans/');

// Sprawdź tekst
$browser->assertSee('Witaj w VibeTravels!');
$browser->assertDontSee('Error');

// Sprawdź obecność elementu
$browser->assertPresent('.alert-success');
$browser->assertMissing('.error-message');

// Sprawdź wartość pola
$browser->assertInputValue('email', 'test@example.com');
$browser->assertChecked('remember');

// Czekaj na elementy
$browser->waitFor('.modal', 10); // Czekaj max 10 sekund
$browser->waitForText('Plan zapisany', 5);
$browser->waitUntilMissing('.loading');
```

## Najlepsze praktyki

### Dla deweloperów

```bash
# Przed każdym commitem
make quality

# Przed utworzeniem PR
make quality && make dusk

# Podczas debugowania funkcjonalności
docker compose exec app php artisan dusk --browse tests/Browser/YourTest.php
```

### Dla CI/CD

- ✅ Unit + Feature testy uruchamiane automatycznie przy każdym pushu
- ✅ PHPStan + Laravel Pint w pipeline
- ❌ Testy Dusk uruchamiane RĘCZNIE przed wydaniem
- ✅ Wszystkie testy muszą przejść przed mergem do `main`

### Konwencje nazewnictwa

```php
// ✅ Dobre nazwy testów
test_user_can_login_with_valid_credentials()
test_plan_creation_validates_required_fields()
test_dashboard_shows_user_plans()

// ❌ Złe nazwy
test_login()
test_it_works()
test_feature()
```

### Używaj trait DatabaseTruncation

```php
class MyDuskTest extends DuskTestCase
{
    use DatabaseTruncation; // Automatycznie czyści bazę między testami

    public function test_something(): void
    {
        // Test...
    }
}
```

## Rozwiązywanie problemów

### Testy Dusk nie działają

```bash
# 1. Sprawdź, czy kontenery działają
docker compose ps

# 2. Sprawdź logi Selenium
docker compose logs selenium

# 3. Zresetuj bazę danych
docker compose exec app php artisan migrate:fresh --seed

# 4. Wyczyść cache
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
```

### Testy losowo nie przechodzą

```bash
# Sprawdź timing issues - dodaj pauzy
$browser->pause(1000); // 1 sekunda

# Użyj waitFor zamiast pause
$browser->waitFor('.element', 10);
$browser->waitForText('Expected text', 5);
```

### VNC nie łączy się

```bash
# Sprawdź, czy port 7900 jest wolny
sudo lsof -i :7900

# Zrestartuj kontener Selenium
docker compose restart selenium

# Sprawdź URL: http://localhost:7900/ (NIE https!)
```

### Problemy z uprawnieniami do zrzutów ekranu

```bash
# Napraw uprawnienia
docker compose exec app chmod -R 777 tests/Browser/screenshots
```

### Testy są bardzo wolne

```bash
# Wyłącz tryb --browse jeśli nie debugujesz
docker compose exec app php artisan dusk

# Uruchamiaj tylko niezbędne testy
docker compose exec app php artisan dusk --filter=test_specific_test
```

### Błąd "Database not cleaned"

Użytkownicy z poprzednich testów nadal istnieją:

```bash
# Wymuś reset bazy
docker compose exec app php artisan migrate:fresh --seed

# Upewnij się, że używasz DatabaseTruncation trait
use Illuminate\Foundation\Testing\DatabaseTruncation;
```

## Cele pokrycia testami

- **Testy jednostkowe**: >80% pokrycia dla Models i Services
- **Testy feature**: 100% pokrycia krytycznych ścieżek użytkownika
- **Testy Dusk**: Wszystkie główne przepływy użytkownika pokryte
- **Ogólnie**: >75% pokrycia kodu

## Harmonogram testowania

### Codziennie (automatycznie w CI/CD)
- ✅ Unit tests
- ✅ Feature tests
- ✅ PHPStan
- ✅ Laravel Pint

### Przed wydaniem (ręcznie)
- ✅ Wszystkie testy Dusk
- ✅ Manualne testy eksploracyjne
- ✅ Test na środowisku staging

### Co tydzień (opcjonalnie)
- ✅ Testy wydajności
- ✅ Testy bezpieczeństwa
- ✅ Przegląd pokrycia testami

## Dodatkowe zasoby

- [Laravel Testing Docs](https://laravel.com/docs/11.x/testing)
- [Laravel Dusk Docs](https://laravel.com/docs/11.x/dusk)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Livewire Testing Guide](https://livewire.laravel.com/docs/testing)

---

**Pytania?** Zobacz [README.md](README.md) lub [CLAUDE.md](CLAUDE.md) dla więcej informacji.
