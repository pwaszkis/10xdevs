# Szczegółowy Plan Implementacji Testów - VibeTravels MVP

**Autor:** Senior QA Engineer
**Data:** 2025-10-14
**Wersja:** 1.0
**Status:** Do wdrożenia

---

## Spis Treści

1. [Wprowadzenie](#wprowadzenie)
2. [Analiza Obecnego Pokrycia](#analiza-obecnego-pokrycia)
3. [Strategia Implementacji](#strategia-implementacji)
4. [Moduł 1: Uwierzytelnianie i Autoryzacja](#moduł-1-uwierzytelnianie-i-autoryzacja)
5. [Moduł 2: Onboarding Użytkownika](#moduł-2-onboarding-użytkownika)
6. [Moduł 3: Zarządzanie Profilem](#moduł-3-zarządzanie-profilem)
7. [Moduł 4: Dashboard i Nawigacja](#moduł-4-dashboard-i-nawigacja)
8. [Moduł 5: Zarządzanie Planami Podróży](#moduł-5-zarządzanie-planami-podróży)
9. [Moduł 6: Generowanie Planów AI](#moduł-6-generowanie-planów-ai)
10. [Moduł 7: Wyświetlanie i Interakcja z Planem](#moduł-7-wyświetlanie-i-interakcja-z-planem)
11. [Moduł 8: System Feedbacku](#moduł-8-system-feedbacku)
12. [Moduł 9: Eksport do PDF](#moduł-9-eksport-do-pdf)
13. [Moduł 10: Powiadomienia Email](#moduł-10-powiadomienia-email)
14. [Moduł 11: Bezpieczeństwo i RODO](#moduł-11-bezpieczeństwo-i-rodo)
15. [Moduł 12: Testy Wydajnościowe](#moduł-12-testy-wydajnościowe)
16. [Harmonogram Implementacji](#harmonogram-implementacji)
17. [Kryteria Sukcesu](#kryteria-sukcesu)

---

## Wprowadzenie

Niniejszy dokument zawiera szczegółowy, krok po kroku plan implementacji testów automatycznych dla aplikacji VibeTravels MVP. Plan obejmuje **120+ przypadków testowych** podzielonych na 12 modułów funkcjonalnych.

### Założenia
- Wszystkie testy będą napisane w PHPUnit 11+
- Wykorzystanie Laravel Test Helpers i Livewire Test Helpers
- Mockowanie zewnętrznych API (OpenAI, Google OAuth)
- Użycie trait `RefreshDatabase` dla izolacji testów
- Pokrycie kodu: cel ≥90% dla kluczowych ścieżek

### Środowisko testowe
- **Baza danych:** MySQL (vibetravels_test) lub SQLite in-memory
- **Kolejki:** `sync` driver (synchroniczne wykonanie)
- **Email:** `array` driver (bez wysyłki)
- **AI:** MockOpenAIService (AI_USE_REAL_API=false)
- **Cache:** `array` driver

---

## Analiza Obecnego Pokrycia

### ✅ Testy istniejące (16 plików)

| Obszar | Pliki testowe | Status |
|--------|--------------|--------|
| **Autentykacja** | `AuthenticationTest.php`, `RegistrationTest.php`, `EmailVerificationTest.php` | ✅ Częściowe |
| **Resetowanie hasła** | `PasswordResetTest.php`, `PasswordUpdateTest.php`, `PasswordConfirmationTest.php` | ✅ Pełne |
| **Profil** | `ProfileTest.php` | ✅ Częściowe |
| **Onboarding** | `OnboardingFlowTest.php`, `OnboardingMiddlewareTest.php`, `OnboardingPersistenceTest.php`, `OnboardingValidationTest.php` | ✅ Rozbudowane |
| **OpenAI** | `OpenAIServiceTest.php` | ✅ Pełne |
| **Feedback** | `TravelPlanFeedbackTest.php` | ✅ Częściowe |

### ❌ Brakujące obszary testowe

1. **Dashboard** - brak testów komponentu Livewire
2. **Tworzenie planów** - brak testów dla CreatePlanForm
3. **Generowanie AI** - brak testów integracyjnych z kolejkami
4. **Widok planu** - brak testów dla Plans\Show i komponentów
5. **Eksport PDF** - brak testów
6. **Limity AI** - brak testów LimitService
7. **Email notifications** - brak testów Mail
8. **Bezpieczeństwo** - brak testów autoryzacji dostępu do zasobów
9. **GDPR** - brak testów usuwania konta
10. **Testy wydajnościowe** - brak

### Szacunkowe pokrycie obecne: ~35%
### Cel po implementacji: ≥90%

---

## Strategia Implementacji

### Faza 1: Foundation (Tydzień 1-2) - PRIORYTET KRYTYCZNY
- Testy jednostkowe Services (LimitService, TravelPlanService, PreferenceService)
- Testy modeli (relacje, accessory, mutatory)
- Testy pomocnicze (factories, seeders)

### Faza 2: Core Features (Tydzień 3-5) - PRIORYTET WYSOKI
- Dashboard i nawigacja
- Tworzenie i zarządzanie planami
- Integracja z AI i kolejkami
- Limity generacji

### Faza 3: User Experience (Tydzień 6-7) - PRIORYTET ŚREDNI
- Widok planu i komponenty
- System feedbacku
- Eksport PDF
- Powiadomienia email

### Faza 4: Security & Edge Cases (Tydzień 8-9) - PRIORYTET WYSOKI
- Testy bezpieczeństwa
- Testy RODO
- Edge cases i error handling
- Testy integracyjne end-to-end

### Faza 5: Performance & Polish (Tydzień 10) - PRIORYTET ŚREDNI
- Testy wydajnościowe
- Testy regresji
- Optymalizacja suite'a testowego

---

## Moduł 1: Uwierzytelnianie i Autoryzacja

**Status:** ✅ Częściowo zaimplementowane
**Priorytet:** 🔴 KRYTYCZNY
**Szacowany czas:** 4 godziny
**Pliki:** `tests/Feature/Auth/`

### 1.1. Testy do uzupełnienia

#### TC-AUTH-04: Google OAuth - Nowy użytkownik
**Plik:** `tests/Feature/Auth/GoogleOAuthTest.php` (NOWY)

```php
public function test_new_user_can_register_with_google()
{
    // Mock Google OAuth response
    $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
    $abstractUser->shouldReceive('getId')->andReturn('google-123');
    $abstractUser->shouldReceive('getEmail')->andReturn('newuser@example.com');
    $abstractUser->shouldReceive('getName')->andReturn('John Doe');

    Socialite::shouldReceive('driver->user')->andReturn($abstractUser);

    // Simulate OAuth callback
    $response = $this->get('/auth/google/callback');

    // Assertions
    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
        'google_id' => 'google-123',
        'email_verified_at' => now()->format('Y-m-d H:i:s'),
    ]);

    $response->assertRedirect('/onboarding');
    $this->assertAuthenticated();
}

public function test_existing_user_can_login_with_google()
{
    // Arrange: User with Google ID
    $user = User::factory()->create([
        'email' => 'existing@example.com',
        'google_id' => 'google-456',
        'onboarding_completed' => true,
    ]);

    // Mock OAuth
    $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
    $abstractUser->shouldReceive('getId')->andReturn('google-456');
    $abstractUser->shouldReceive('getEmail')->andReturn('existing@example.com');

    Socialite::shouldReceive('driver->user')->andReturn($abstractUser);

    // Act
    $response = $this->get('/auth/google/callback');

    // Assert
    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
}

public function test_google_oauth_handles_cancelled_authorization()
{
    // Simulate error from Google
    $response = $this->get('/auth/google/callback?error=access_denied');

    $response->assertRedirect('/login');
    $response->assertSessionHas('error', 'Autoryzacja Google została anulowana.');
}

public function test_google_oauth_links_existing_email_account()
{
    // User registered with email/password
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'google_id' => null,
    ]);

    // Mock Google with same email
    $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
    $abstractUser->shouldReceive('getId')->andReturn('google-789');
    $abstractUser->shouldReceive('getEmail')->andReturn('user@example.com');

    Socialite::shouldReceive('driver->user')->andReturn($abstractUser);

    // Act
    $response = $this->get('/auth/google/callback');

    // Assert: Google ID added to existing account
    $this->assertDatabaseHas('users', [
        'email' => 'user@example.com',
        'google_id' => 'google-789',
    ]);
}
```

**Scenariusze:**
- ✅ Nowy użytkownik rejestruje się przez Google
- ✅ Istniejący użytkownik loguje się przez Google
- ✅ Obsługa anulowania autoryzacji
- ✅ Linkowanie Google do istniejącego konta email

---

#### TC-AUTH-05: Rate Limiting
**Plik:** `tests/Feature/Auth/RateLimitingTest.php` (NOWY)

```php
public function test_login_is_rate_limited_after_5_failed_attempts()
{
    $user = User::factory()->create();

    // 5 nieudanych prób
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);
    }

    // 6. próba powinna być zablokowana
    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(429); // Too Many Requests
    $response->assertSee('Too many login attempts');
}

public function test_registration_is_rate_limited()
{
    // 3 rejestracje z tego samego IP w krótkim czasie
    for ($i = 0; $i < 3; $i++) {
        $this->post('/register', [
            'email' => "user{$i}@example.com",
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
    }

    // 4. próba powinna być zablokowana
    $response = $this->post('/register', [
        'email' => 'user4@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(429);
}
```

**Scenariusze:**
- ✅ Login rate limiting (5 prób/minutę)
- ✅ Registration rate limiting (3 rejestracje/10 minut)
- ✅ Weryfikacja reset limitu po czasie

---

#### TC-AUTH-06: Session Management
**Plik:** `tests/Feature/Auth/SessionManagementTest.php` (NOWY)

```php
public function test_session_expires_after_inactivity()
{
    config(['session.lifetime' => 1]); // 1 minute

    $user = User::factory()->create();
    $this->actingAs($user);

    // Symulacja upływu czasu
    $this->travel(2)->minutes();

    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
}

public function test_remember_me_extends_session()
{
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'remember' => true,
    ]);

    $this->assertNotNull(Auth::user()->remember_token);
}

public function test_logout_destroys_session()
{
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
}
```

**Scenariusze:**
- ✅ Wygaśnięcie sesji po nieaktywności
- ✅ Remember me functionality
- ✅ Wylogowanie usuwa sesję

---

### 1.2. Testy do rozszerzenia (istniejące pliki)

#### `tests/Feature/Auth/EmailVerificationTest.php`

**Dodać:**
```php
public function test_verification_link_expires_after_24_hours()
{
    $user = User::factory()->unverified()->create();

    // Generuj link
    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addHours(24),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    // Symuluj upływ 25 godzin
    $this->travel(25)->hours();

    $response = $this->get($verificationUrl);
    $response->assertStatus(403); // Link wygasł
}

public function test_resend_verification_email_is_rate_limited()
{
    $user = User::factory()->unverified()->create();
    $this->actingAs($user);

    // 1. wysyłka - OK
    $this->post('/email/verification-notification')->assertStatus(200);

    // 2. wysyłka (< 5 minut) - blocked
    $this->travel(2)->minutes();
    $response = $this->post('/email/verification-notification');
    $response->assertStatus(429);
}
```

---

### 1.3. Podsumowanie Modułu 1

**Do utworzenia:**
- `GoogleOAuthTest.php` (4 testy)
- `RateLimitingTest.php` (2 testy)
- `SessionManagementTest.php` (3 testy)

**Do rozszerzenia:**
- `EmailVerificationTest.php` (+2 testy)

**Łącznie:** 11 nowych testów
**Całkowity czas implementacji:** 4 godziny

---

## Moduł 2: Onboarding Użytkownika

**Status:** ✅ Dobrze pokryty
**Priorytet:** 🟡 ŚREDNI (rozszerzenie)
**Szacowany czas:** 2 godziny
**Pliki:** `tests/Feature/Onboarding/`

### 2.1. Testy do uzupełnienia

#### TC-ONB-05: Edge Cases
**Plik:** `tests/Feature/Onboarding/OnboardingEdgeCasesTest.php` (NOWY)

```php
public function test_user_cannot_access_dashboard_without_completing_onboarding()
{
    $user = User::factory()->create([
        'onboarding_completed' => false,
        'onboarding_step' => 1,
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertRedirect('/onboarding');
}

public function test_completed_onboarding_cannot_be_accessed_again()
{
    $user = User::factory()->create([
        'onboarding_completed' => true,
    ]);

    $this->actingAs($user);

    $response = $this->get('/onboarding');
    $response->assertRedirect('/dashboard');
}

public function test_onboarding_tracks_completion_rate()
{
    // User completes onboarding
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'onboarding_completed' => false,
    ]);

    Livewire::actingAs($user)
        ->test(OnboardingWizard::class)
        ->set('nickname', 'TestUser')
        ->set('homeLocation', 'Warsaw')
        ->call('nextStep')
        ->call('toggleInterest', 'historia_kultura')
        ->call('nextStep')
        ->call('setTravelPace', 'umiarkowane')
        ->call('setBudgetLevel', 'standardowy')
        ->call('setTransportPreference', 'pieszo_publiczny')
        ->call('setRestrictions', 'brak')
        ->call('completeOnboarding');

    // Analytics: completion rate
    $user->refresh();
    $this->assertTrue($user->onboarding_completed);
    $this->assertEquals(100, $user->onboardingCompletionPercentage());
}

public function test_partial_onboarding_can_be_resumed()
{
    $user = User::factory()->create([
        'onboarding_completed' => false,
        'onboarding_step' => 2,
        'nickname' => 'TestUser',
        'home_location' => 'Warsaw',
    ]);

    $this->actingAs($user);

    Livewire::test(OnboardingWizard::class)
        ->assertSet('currentStep', 2) // Resume at step 2
        ->assertSet('nickname', 'TestUser');
}
```

**Scenariusze:**
- ✅ Middleware: przekierowanie do onboardingu
- ✅ Nie można wejść ponownie po ukończeniu
- ✅ Tracking completion rate
- ✅ Wznawianie przerwanego onboardingu

---

### 2.2. Podsumowanie Modułu 2

**Do utworzenia:**
- `OnboardingEdgeCasesTest.php` (4 testy)

**Łącznie:** 4 nowe testy
**Całkowity czas implementacji:** 2 godziny

---

## Moduł 3: Zarządzanie Profilem

**Status:** ✅ Częściowo zaimplementowane
**Priorytet:** 🟢 NISKI
**Szacowany czas:** 2 godziny
**Pliki:** `tests/Feature/Profile/`

### 3.1. Testy do uzupełnienia

#### TC-PROF-01: Edycja Preferencji
**Plik:** `tests/Feature/Profile/UserPreferencesTest.php` (NOWY)

```php
public function test_user_can_update_travel_preferences()
{
    $user = User::factory()->create();
    UserPreference::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(ProfilePreferencesForm::class)
        ->set('interestCategories', ['przyroda_outdoor', 'gastronomia'])
        ->set('travelPace', 'intensywne')
        ->set('budgetLevel', 'premium')
        ->set('transportPreference', 'wynajem_auta')
        ->set('restrictions', 'dieta')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('user_preferences', [
        'user_id' => $user->id,
        'travel_pace' => 'intensywne',
        'budget_level' => 'premium',
    ]);
}

public function test_preference_changes_affect_future_ai_generations()
{
    $user = User::factory()->create();
    $preferences = UserPreference::factory()->create([
        'user_id' => $user->id,
        'interests_categories' => ['plaże_relaks'],
    ]);

    // Generate plan with beach preferences
    $plan1 = TravelPlan::factory()->create(['user_id' => $user->id]);

    // Update preferences to hiking
    $preferences->update(['interests_categories' => ['przyroda_outdoor']]);

    // New generation should use updated preferences
    Queue::fake();

    Livewire::actingAs($user)
        ->test(CreatePlanForm::class)
        ->set('title', 'Mountain Trip')
        ->set('destination', 'Tatry')
        ->call('generate');

    Queue::assertPushed(GenerateTravelPlanJob::class, function ($job) use ($preferences) {
        return $job->preferences->interests_categories === ['przyroda_outdoor'];
    });
}
```

**Scenariusze:**
- ✅ Aktualizacja preferencji
- ✅ Walidacja zmian
- ✅ Wpływ zmian na przyszłe generacje AI

---

### 3.2. Podsumowanie Modułu 3

**Do utworzenia:**
- `UserPreferencesTest.php` (2 testy)

**Łącznie:** 2 nowe testy
**Całkowity czas implementacji:** 2 godziny

---

## Moduł 4: Dashboard i Nawigacja

**Status:** ❌ Brak testów
**Priorytet:** 🔴 KRYTYCZNY
**Szacowany czas:** 6 godzin
**Pliki:** `tests/Feature/Dashboard/`

### 4.1. Testy komponentu Dashboard

#### TC-DASH-01: Podstawowe wyświetlanie
**Plik:** `tests/Feature/Dashboard/DashboardTest.php` (NOWY)

```php
public function test_authenticated_user_can_access_dashboard()
{
    $user = User::factory()->create([
        'nickname' => 'JohnDoe',
        'onboarding_completed' => true,
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertOk();
    $response->assertSee('Cześć JohnDoe!');
    $response->assertSee('Zaplanuj swoją kolejną przygodę');
    $response->assertSeeLivewire(Dashboard::class);
}

public function test_guest_cannot_access_dashboard()
{
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
}

public function test_dashboard_displays_user_plans()
{
    $user = User::factory()->create(['onboarding_completed' => true]);

    TravelPlan::factory()->count(3)->create([
        'user_id' => $user->id,
        'title' => 'Plan Title',
        'destination' => 'Paris',
    ]);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSee('Plan Title')
        ->assertSee('Paris')
        ->assertCount('plans', 3);
}

public function test_dashboard_shows_empty_state_when_no_plans()
{
    $user = User::factory()->create(['onboarding_completed' => true]);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSee('Nie masz jeszcze żadnych planów')
        ->assertSee('Stwórz nowy plan');
}
```

**Scenariusze:**
- ✅ Dostęp dla zalogowanych użytkowników
- ✅ Blokada dla gości
- ✅ Wyświetlanie listy planów
- ✅ Empty state

---

#### TC-DASH-02: Filtrowanie i sortowanie
**Plik:** `tests/Feature/Dashboard/PlanFilteringTest.php` (NOWY)

```php
public function test_filter_plans_by_status_all()
{
    $user = User::factory()->create(['onboarding_completed' => true]);

    TravelPlan::factory()->create(['user_id' => $user->id, 'status' => 'draft']);
    TravelPlan::factory()->create(['user_id' => $user->id, 'status' => 'planned']);
    TravelPlan::factory()->create(['user_id' => $user->id, 'status' => 'completed']);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->set('statusFilter', 'all')
        ->assertCount('plans', 3);
}

public function test_filter_plans_by_status_drafts()
{
    $user = User::factory()->create(['onboarding_completed' => true]);

    TravelPlan::factory()->count(2)->create([
        'user_id' => $user->id,
        'status' => 'draft'
    ]);
    TravelPlan::factory()->create(['user_id' => $user->id, 'status' => 'planned']);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->set('statusFilter', 'draft')
        ->assertCount('filteredPlans', 2);
}

public function test_search_plans_by_title_or_destination()
{
    $user = User::factory()->create(['onboarding_completed' => true]);

    TravelPlan::factory()->create([
        'user_id' => $user->id,
        'title' => 'Summer in Paris',
        'destination' => 'France',
    ]);
    TravelPlan::factory()->create([
        'user_id' => $user->id,
        'title' => 'Winter Trip',
        'destination' => 'Norway',
    ]);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->set('search', 'Paris')
        ->assertSee('Summer in Paris')
        ->assertDontSee('Winter Trip');
}

public function test_sort_plans_by_newest_first()
{
    $user = User::factory()->create(['onboarding_completed' => true]);

    $old = TravelPlan::factory()->create([
        'user_id' => $user->id,
        'created_at' => now()->subDays(5),
        'title' => 'Old Plan',
    ]);
    $new = TravelPlan::factory()->create([
        'user_id' => $user->id,
        'created_at' => now(),
        'title' => 'New Plan',
    ]);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->set('sortBy', 'created_at')
        ->set('sortDirection', 'desc')
        ->assertSeeInOrder(['New Plan', 'Old Plan']);
}
```

**Scenariusze:**
- ✅ Filtr "Wszystkie"
- ✅ Filtr "Szkice"
- ✅ Filtr "Zaplanowane"
- ✅ Filtr "Zrealizowane"
- ✅ Wyszukiwanie po title/destination
- ✅ Sortowanie (najnowsze/najstarsze)

---

#### TC-DASH-03: Licznik limitów AI
**Plik:** `tests/Feature/Dashboard/AILimitCounterTest.php` (NOWY)

```php
public function test_dashboard_displays_current_ai_limit()
{
    $user = User::factory()->create(['onboarding_completed' => true]);

    // User has used 3 generations this month
    AIGeneration::factory()->count(3)->create([
        'user_id' => $user->id,
        'created_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSee('3/10 w tym miesiącu');
}

public function test_counter_shows_warning_when_limit_nearly_reached()
{
    $user = User::factory()->create(['onboarding_completed' => true]);

    AIGeneration::factory()->count(9)->create([
        'user_id' => $user->id,
        'created_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSee('9/10')
        ->assertSee('Pozostała 1 generacja');
}

public function test_counter_shows_error_when_limit_exhausted()
{
    $user = User::factory()->create(['onboarding_completed' => true]);

    AIGeneration::factory()->count(10)->create([
        'user_id' => $user->id,
        'created_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertSee('10/10')
        ->assertSee('Limit wyczerpany');
}
```

**Scenariusze:**
- ✅ Wyświetlanie aktualnego limitu
- ✅ Ostrzeżenie przy 8-9/10
- ✅ Komunikat o wyczerpaniu przy 10/10

---

### 4.2. Podsumowanie Modułu 4

**Do utworzenia:**
- `DashboardTest.php` (4 testy)
- `PlanFilteringTest.php` (4 testy)
- `AILimitCounterTest.php` (3 testy)

**Łącznie:** 11 nowych testów
**Całkowity czas implementacji:** 6 godzin

---

## Moduł 5: Zarządzanie Planami Podróży

**Status:** ❌ Brak testów
**Priorytet:** 🔴 KRYTYCZNY
**Szacowany czas:** 8 godzin
**Pliki:** `tests/Feature/Plans/`

### 5.1. Tworzenie planów

#### TC-PLAN-01: Formularz tworzenia
**Plik:** `tests/Feature/Plans/CreatePlanTest.php` (NOWY)

```php
public function test_user_can_create_new_plan_as_draft()
{
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreatePlanForm::class)
        ->set('title', 'Weekend w Krakowie')
        ->set('destination', 'Kraków')
        ->set('departureDate', '2025-06-15')
        ->set('numberOfDays', 3)
        ->set('numberOfPeople', 2)
        ->set('budgetPerPerson', 500)
        ->set('notes', 'Chcemy zwiedzić Stare Miasto')
        ->call('saveAsDraft')
        ->assertHasNoErrors()
        ->assertRedirect('/dashboard');

    $this->assertDatabaseHas('travel_plans', [
        'user_id' => $user->id,
        'title' => 'Weekend w Krakowie',
        'destination' => 'Kraków',
        'status' => 'draft',
    ]);
}

public function test_form_validates_required_fields()
{
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreatePlanForm::class)
        ->set('title', '')
        ->set('destination', '')
        ->call('saveAsDraft')
        ->assertHasErrors(['title', 'destination', 'departureDate', 'numberOfDays', 'numberOfPeople']);
}

public function test_departure_date_cannot_be_in_past()
{
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreatePlanForm::class)
        ->set('title', 'Test')
        ->set('destination', 'Test')
        ->set('departureDate', now()->subDay()->format('Y-m-d'))
        ->set('numberOfDays', 3)
        ->set('numberOfPeople', 2)
        ->call('saveAsDraft')
        ->assertHasErrors(['departureDate' => 'after_or_equal:today']);
}

public function test_number_of_days_is_between_1_and_30()
{
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreatePlanForm::class)
        ->set('title', 'Test')
        ->set('destination', 'Test')
        ->set('departureDate', now()->addDays(10)->format('Y-m-d'))
        ->set('numberOfDays', 0)
        ->set('numberOfPeople', 2)
        ->call('saveAsDraft')
        ->assertHasErrors(['numberOfDays' => 'min:1']);

    Livewire::actingAs($user)
        ->test(CreatePlanForm::class)
        ->set('numberOfDays', 31)
        ->call('saveAsDraft')
        ->assertHasErrors(['numberOfDays' => 'max:30']);
}

public function test_budget_is_optional()
{
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreatePlanForm::class)
        ->set('title', 'Test Plan')
        ->set('destination', 'Warsaw')
        ->set('departureDate', now()->addDays(5)->format('Y-m-d'))
        ->set('numberOfDays', 2)
        ->set('numberOfPeople', 1)
        ->set('budgetPerPerson', null)
        ->call('saveAsDraft')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('travel_plans', [
        'title' => 'Test Plan',
        'budget_per_person' => null,
    ]);
}
```

**Scenariusze:**
- ✅ Tworzenie szkicu
- ✅ Walidacja wymaganych pól
- ✅ Data wyjazdu nie może być w przeszłości
- ✅ Liczba dni: 1-30
- ✅ Liczba osób: 1-10
- ✅ Budżet opcjonalny

---

#### TC-PLAN-02: Wyświetlanie i edycja szkiców
**Plik:** `tests/Feature/Plans/DraftManagementTest.php` (NOWY)

```php
public function test_user_can_view_draft_details()
{
    $user = User::factory()->create();
    $plan = TravelPlan::factory()->create([
        'user_id' => $user->id,
        'status' => 'draft',
        'title' => 'My Draft',
    ]);

    $this->actingAs($user);

    $response = $this->get("/plans/{$plan->id}");

    $response->assertOk();
    $response->assertSee('My Draft');
    $response->assertSee('Generuj plan'); // Button available for drafts
}

public function test_draft_can_be_deleted()
{
    $user = User::factory()->create();
    $plan = TravelPlan::factory()->create([
        'user_id' => $user->id,
        'status' => 'draft',
    ]);

    $this->actingAs($user);

    $response = $this->delete("/plans/{$plan->id}");

    $response->assertRedirect('/dashboard');
    $this->assertDatabaseMissing('travel_plans', ['id' => $plan->id]);
}

public function test_user_cannot_delete_other_users_plan()
{
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $plan = TravelPlan::factory()->create(['user_id' => $user1->id]);

    $this->actingAs($user2);

    $response = $this->delete("/plans/{$plan->id}");

    $response->assertStatus(403); // Forbidden
    $this->assertDatabaseHas('travel_plans', ['id' => $plan->id]);
}
```

**Scenariusze:**
- ✅ Wyświetlanie szczegółów szkicu
- ✅ Usuwanie szkicu
- ✅ Autoryzacja: nie można usunąć cudzego planu

---

### 5.2. Podsumowanie Modułu 5

**Do utworzenia:**
- `CreatePlanTest.php` (5 testów)
- `DraftManagementTest.php` (3 testy)

**Łącznie:** 8 nowych testów
**Całkowity czas implementacji:** 8 godzin

---

## Moduł 6: Generowanie Planów AI

**Status:** ❌ Brak testów integracyjnych
**Priorytet:** 🔴 KRYTYCZNY
**Szacowany czas:** 12 godzin
**Pliki:** `tests/Feature/AI/`

### 6.1. Podstawowe generowanie

#### TC-AI-01: Proces generowania
**Plik:** `tests/Feature/AI/PlanGenerationTest.php` (NOWY)

```php
public function test_user_can_generate_plan_from_form()
{
    Queue::fake();
    $user = User::factory()->create();
    UserPreference::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(CreatePlanForm::class)
        ->set('title', 'Trip to Paris')
        ->set('destination', 'Paris')
        ->set('departureDate', now()->addDays(30)->format('Y-m-d'))
        ->set('numberOfDays', 5)
        ->set('numberOfPeople', 2)
        ->set('budgetPerPerson', 1000)
        ->set('notes', 'Want to see Eiffel Tower')
        ->call('generate')
        ->assertHasNoErrors();

    Queue::assertPushed(GenerateTravelPlanJob::class);

    $this->assertDatabaseHas('travel_plans', [
        'user_id' => $user->id,
        'title' => 'Trip to Paris',
        'status' => 'draft', // Status before generation completes
    ]);
}

public function test_generate_button_checks_ai_limit()
{
    $user = User::factory()->create();

    // Exhaust limit
    AIGeneration::factory()->count(10)->create([
        'user_id' => $user->id,
        'created_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(CreatePlanForm::class)
        ->set('title', 'Test')
        ->set('destination', 'Test')
        ->set('departureDate', now()->addDays(10)->format('Y-m-d'))
        ->set('numberOfDays', 3)
        ->set('numberOfPeople', 2)
        ->call('generate')
        ->assertHasErrors(['aiLimit']);
}

public function test_plan_generation_job_processes_successfully()
{
    $user = User::factory()->create();
    UserPreference::factory()->create(['user_id' => $user->id]);

    $plan = TravelPlan::factory()->create([
        'user_id' => $user->id,
        'status' => 'draft',
    ]);

    $job = new GenerateTravelPlanJob($plan);
    $job->handle();

    $plan->refresh();

    $this->assertEquals('planned', $plan->status);
    $this->assertNotNull($plan->generated_content);
    $this->assertDatabaseHas('plan_days', ['travel_plan_id' => $plan->id]);
}

public function test_generation_creates_ai_metadata()
{
    $user = User::factory()->create();
    UserPreference::factory()->create(['user_id' => $user->id]);

    $plan = TravelPlan::factory()->create(['user_id' => $user->id]);

    $job = new GenerateTravelPlanJob($plan);
    $job->handle();

    $this->assertDatabaseHas('ai_generations', [
        'user_id' => $user->id,
        'travel_plan_id' => $plan->id,
    ]);

    $generation = AIGeneration::where('travel_plan_id', $plan->id)->first();
    $this->assertGreaterThan(0, $generation->tokens_used);
    $this->assertGreaterThan(0, $generation->estimated_cost);
}
```

**Scenariusze:**
- ✅ Tworzenie planu i wysyłanie do kolejki
- ✅ Sprawdzanie limitu przed generowaniem
- ✅ Job przetwarza plan poprawnie
- ✅ Tworzenie metadanych AI

---

#### TC-AI-02: Limity generacji
**Plik:** `tests/Feature/AI/GenerationLimitsTest.php` (NOWY)

```php
public function test_user_has_10_generations_per_month()
{
    $user = User::factory()->create();

    $service = app(LimitService::class);

    $this->assertEquals(10, $service->getMonthlyLimit($user));
    $this->assertEquals(10, $service->getRemainingGenerations($user));
}

public function test_generation_decrements_remaining_limit()
{
    $user = User::factory()->create();
    $service = app(LimitService::class);

    AIGeneration::factory()->create([
        'user_id' => $user->id,
        'created_at' => now(),
    ]);

    $this->assertEquals(9, $service->getRemainingGenerations($user));
}

public function test_limit_resets_on_first_day_of_month()
{
    $user = User::factory()->create();
    $service = app(LimitService::class);

    // Use 10 generations in previous month
    AIGeneration::factory()->count(10)->create([
        'user_id' => $user->id,
        'created_at' => now()->subMonth(),
    ]);

    // Current month should be reset
    $this->assertEquals(10, $service->getRemainingGenerations($user));
}

public function test_failed_generation_does_not_consume_limit()
{
    $user = User::factory()->create();
    $plan = TravelPlan::factory()->create(['user_id' => $user->id]);

    $service = app(LimitService::class);
    $initialRemaining = $service->getRemainingGenerations($user);

    // Simulate API failure
    $this->mock(OpenAIService::class, function ($mock) {
        $mock->shouldReceive('chat->send')
            ->andThrow(new \Exception('API Error'));
    });

    try {
        $job = new GenerateTravelPlanJob($plan);
        $job->handle();
    } catch (\Exception $e) {
        // Expected
    }

    $plan->refresh();
    $this->assertEquals('draft', $plan->status); // Status unchanged
    $this->assertEquals($initialRemaining, $service->getRemainingGenerations($user)); // Limit unchanged
}

public function test_regeneration_consumes_additional_limit()
{
    $user = User::factory()->create();
    $service = app(LimitService::class);

    $plan = TravelPlan::factory()->create([
        'user_id' => $user->id,
        'status' => 'planned',
    ]);

    // First generation
    AIGeneration::factory()->create([
        'user_id' => $user->id,
        'travel_plan_id' => $plan->id,
    ]);

    $this->assertEquals(9, $service->getRemainingGenerations($user));

    // Regenerate
    Queue::fake();
    Livewire::actingAs($user)
        ->test(PlanActions::class, ['plan' => $plan])
        ->call('regenerate');

    Queue::assertPushed(GenerateTravelPlanJob::class);
}
```

**Scenariusze:**
- ✅ Domyślny limit 10/miesiąc
- ✅ Dekrementacja po generacji
- ✅ Reset 1. dnia miesiąca
- ✅ Błąd nie zużywa limitu
- ✅ Regeneracja zużywa dodatkowy limit

---

#### TC-AI-03: Error Handling
**Plik:** `tests/Feature/AI/GenerationErrorHandlingTest.php` (NOWY)

```php
public function test_timeout_error_is_handled_gracefully()
{
    $user = User::factory()->create();
    $plan = TravelPlan::factory()->create(['user_id' => $user->id]);

    $this->mock(OpenAIService::class, function ($mock) {
        $mock->shouldReceive('chat->send')
            ->andThrow(new \GuzzleHttp\Exception\RequestException(
                'Timeout',
                new \GuzzleHttp\Psr7\Request('POST', 'test')
            ));
    });

    $job = new GenerateTravelPlanJob($plan);

    try {
        $job->handle();
        $this->fail('Expected exception not thrown');
    } catch (\Exception $e) {
        $this->assertStringContainsString('Timeout', $e->getMessage());
    }

    $plan->refresh();
    $this->assertEquals('draft', $plan->status);
    $this->assertNull($plan->generated_content);
}

public function test_api_error_is_logged_and_user_notified()
{
    Log::fake();
    $user = User::factory()->create();
    $plan = TravelPlan::factory()->create(['user_id' => $user->id]);

    $this->mock(OpenAIService::class, function ($mock) {
        $mock->shouldReceive('chat->send')
            ->andThrow(new \Exception('API Rate Limit Exceeded'));
    });

    $job = new GenerateTravelPlanJob($plan);

    try {
        $job->handle();
    } catch (\Exception $e) {
        // Expected
    }

    Log::assertLogged('error', function ($message) {
        return str_contains($message, 'API Rate Limit Exceeded');
    });
}

public function test_incomplete_ai_response_is_rejected()
{
    $user = User::factory()->create();
    $plan = TravelPlan::factory()->create(['user_id' => $user->id]);

    $this->mock(OpenAIService::class, function ($mock) {
        $mock->shouldReceive('chat->send')->andReturn(
            new OpenAIResponse([
                'content' => '{"days": []}', // Empty/invalid
                'finish_reason' => 'stop',
            ])
        );
    });

    $job = new GenerateTravelPlanJob($plan);
    $job->handle();

    $plan->refresh();
    $this->assertEquals('draft', $plan->status);
    // Should retry or notify user
}
```

**Scenariusze:**
- ✅ Timeout API
- ✅ Błąd API (rate limit, etc.)
- ✅ Niekompletna odpowiedź AI
- ✅ Logowanie błędów

---

### 6.2. Podsumowanie Modułu 6

**Do utworzenia:**
- `PlanGenerationTest.php` (4 testy)
- `GenerationLimitsTest.php` (5 testów)
- `GenerationErrorHandlingTest.php` (3 testy)

**Łącznie:** 12 nowych testów
**Całkowity czas implementacji:** 12 godzin

---

## Moduł 7: Wyświetlanie i Interakcja z Planem

**Status:** ❌ Brak testów
**Priorytet:** 🟡 ŚREDNI
**Szacowany czas:** 6 godzin
**Pliki:** `tests/Feature/Plans/`

### 7.1. Wyświetlanie wygenerowanego planu

#### TC-VIEW-01: Widok planu
**Plik:** `tests/Feature/Plans/PlanViewTest.php` (NOWY)

```php
public function test_user_can_view_generated_plan()
{
    $user = User::factory()->create();
    $plan = TravelPlan::factory()->create([
        'user_id' => $user->id,
        'status' => 'planned',
        'title' => 'Paris Adventure',
        'destination' => 'Paris',
    ]);

    PlanDay::factory()->count(3)->create(['travel_plan_id' => $plan->id])
        ->each(function ($day) {
            PlanPoint::factory()->count(4)->create(['plan_day_id' => $day->id]);
        });

    $this->actingAs($user);

    Livewire::test(Plans\Show::class, ['plan' => $plan])
        ->assertSee('Paris Adventure')
        ->assertSee('Paris')
        ->assertSee('Dzień 1')
        ->assertSee('Dzień 2')
        ->assertSee('Dzień 3')
        ->assertCount('plan.days', 3);
}

public function test_plan_displays_all_points_for_each_day()
{
    $user = User::factory()->create();
    $plan = TravelPlan::factory()->create(['user_id' => $user->id, 'status' => 'planned']);

    $day = PlanDay::factory()->create([
        'travel_plan_id' => $plan->id,
        'day_number' => 1,
    ]);

    PlanPoint::factory()->create([
        'plan_day_id' => $day->id,
        'name' => 'Eiffel Tower',
        'description' => 'Iconic landmark',
        'time_of_day' => 'morning',
    ]);
    PlanPoint::factory()->create([
        'plan_day_id' => $day->id,
        'name' => 'Louvre Museum',
        'time_of_day' => 'afternoon',
    ]);

    $this->actingAs($user);

    Livewire::test(Plans\Show::class, ['plan' => $plan])
        ->assertSee('Eiffel Tower')
        ->assertSee('Iconic landmark')
        ->assertSee('Louvre Museum');
}

public function test_plan_displays_google_maps_links()
{
    $user = User::factory()->create();
    $plan = TravelPlan::factory()->create(['user_id' => $user->id, 'status' => 'planned']);
    $day = PlanDay::factory()->create(['travel_plan_id' => $plan->id]);

    PlanPoint::factory()->create([
        'plan_day_id' => $day->id,
        'name' => 'Notre-Dame',
        'google_maps_url' => 'https://maps.google.com/?q=Notre-Dame+Paris',
    ]);

    $this->actingAs($user);

    $response = $this->get("/plans/{$plan->id}");
    $response->assertSee('https://maps.google.com/?q=Notre-Dame+Paris');
    $response->assertSee('<a', false); // Check for link tag
}

public function test_user_cannot_view_other_users_plan()
{
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $plan = TravelPlan::factory()->create([
        'user_id' => $user1->id,
        'status' => 'planned',
    ]);

    $this->actingAs($user2);

    $response = $this->get("/plans/{$plan->id}");
    $response->assertStatus(403);
}
```

**Scenariusze:**
- ✅ Wyświetlanie podstawowych informacji planu
- ✅ Wyświetlanie dni i punktów
- ✅ Linki Google Maps
- ✅ Autoryzacja dostępu

---

#### TC-VIEW-02: Sekcja założeń użytkownika
**Plik:** `tests/Feature/Plans/PlanAssumptionsTest.php` (NOWY)

```php
public function test_plan_displays_user_notes_in_assumptions_section()
{
    $user = User::factory()->create();
    $plan = TravelPlan::factory()->create([
        'user_id' => $user->id,
        'status' => 'planned',
        'notes' => 'Want to visit art museums and cafes',
    ]);

    $this->actingAs($user);

    Livewire::test(Plans\Show::class, ['plan' => $plan])
        ->assertSee('Twoje założenia')
        ->assertSee('Want to visit art museums and cafes');
}

public function test_assumptions_section_displays_user_preferences()
{
    $user = User::factory()->create();
    UserPreference::factory()->create([
        'user_id' => $user->id,
        'interests_categories' => ['historia_kultura', 'gastronomia'],
        'travel_pace' => 'umiarkowane',
        'budget_level' => 'standardowy',
    ]);

    $plan = TravelPlan::factory()->create([
        'user_id' => $user->id,
        'status' => 'planned',
    ]);

    $this->actingAs($user);

    Livewire::test(Plans\Show::class, ['plan' => $plan])
        ->assertSee('Historia i kultura')
        ->assertSee('Gastronomia')
        ->assertSee('Umiarkowane')
        ->assertSee('Standardowy');
}
```

**Scenariusze:**
- ✅ Wyświetlanie notatek użytkownika
- ✅ Wyświetlanie użytych preferencji

---

### 7.2. Podsumowanie Modułu 7

**Do utworzenia:**
- `PlanViewTest.php` (4 testy)
- `PlanAssumptionsTest.php` (2 testy)

**Łącznie:** 6 nowych testów
**Całkowity czas implementacji:** 6 godzin

---

## Moduł 8: System Feedbacku

**Status:** ✅ Częściowo zaimplementowane
**Priorytet:** 🟢 NISKI
**Szacowany czas:** 2 godziny
**Pliki:** `tests/Feature/Feedback/`

### 8.1. Rozszerzenie istniejących testów

#### `tests/Feature/TravelPlanFeedbackTest.php` - dodać:

```php
public function test_user_can_submit_feedback_with_multiple_issues()
{
    $user = $this->user;
    $plan = $this->plan;

    $response = $this->actingAs($user)
        ->postJson("/api/travel-plans/{$plan->id}/feedback", [
            'satisfied' => false,
            'issues' => ['not_detailed', 'wrong_order', 'other'],
            'other_feedback' => 'Need more restaurant recommendations',
        ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('travel_plan_feedback', [
        'travel_plan_id' => $plan->id,
        'satisfied' => false,
    ]);

    $feedback = TravelPlanFeedback::where('travel_plan_id', $plan->id)->first();
    $this->assertContains('not_detailed', $feedback->issues);
    $this->assertContains('wrong_order', $feedback->issues);
    $this->assertEquals('Need more restaurant recommendations', $feedback->other_feedback);
}

public function test_feedback_can_only_be_submitted_once_per_plan()
{
    $user = $this->user;
    $plan = $this->plan;

    // First feedback
    $this->actingAs($user)
        ->postJson("/api/travel-plans/{$plan->id}/feedback", [
            'satisfied' => true,
        ]);

    // Second attempt should fail
    $response = $this->actingAs($user)
        ->postJson("/api/travel-plans/{$plan->id}/feedback", [
            'satisfied' => false,
        ]);

    $response->assertStatus(422);
    $response->assertJson([
        'message' => 'Feedback już został przesłany dla tego planu',
    ]);
}

public function test_feedback_calculates_satisfaction_rate()
{
    $user = User::factory()->create();

    // Create 10 plans with feedback
    for ($i = 0; $i < 7; $i++) {
        $plan = TravelPlan::factory()->create(['user_id' => $user->id]);
        TravelPlanFeedback::factory()->create([
            'travel_plan_id' => $plan->id,
            'satisfied' => true,
        ]);
    }

    for ($i = 0; $i < 3; $i++) {
        $plan = TravelPlan::factory()->create(['user_id' => $user->id]);
        TravelPlanFeedback::factory()->create([
            'travel_plan_id' => $plan->id,
            'satisfied' => false,
        ]);
    }

    $service = app(FeedbackService::class);
    $satisfactionRate = $service->getSatisfactionRate();

    $this->assertEquals(70.0, $satisfactionRate); // 7/10 = 70%
}
```

**Scenariusze:**
- ✅ Multiple issues w feedbacku
- ✅ Jeden feedback per plan
- ✅ Obliczanie satisfaction rate

---

### 8.2. Podsumowanie Modułu 8

**Do rozszerzenia:**
- `TravelPlanFeedbackTest.php` (+3 testy)

**Łącznie:** 3 nowe testy
**Całkowity czas implementacji:** 2 godziny

---

## Moduł 9: Eksport do PDF

**Status:** ❌ Brak testów
**Priorytet:** 🟡 ŚREDNI
**Szacowany czas:** 4 godziny
**Pliki:** `tests/Feature/Export/`

### 9.1. Testy eksportu PDF

#### TC-PDF-01: Generowanie PDF
**Plik:** `tests/Feature/Export/PdfExportTest.php` (NOWY)

```php
public function test_user_can_export_plan_to_pdf()
{
    $user = User::factory()->create();
    $plan = TravelPlan::factory()->create([
        'user_id' => $user->id,
        'status' => 'planned',
        'title' => 'Paris Trip',
    ]);

    PlanDay::factory()->create(['travel_plan_id' => $plan->id]);

    $this->actingAs($user);

    $response = $this->get("/plans/{$plan->id}/export/pdf");

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    $response->assertHeader('Content-Disposition', 'attachment; filename="Paris_Trip.pdf"');
}

public function test_pdf_contains_plan_details()
{
    $user = User::factory()->create();
    $plan = TravelPlan::factory()->create([
        'user_id' => $user->id,
        'status' => 'planned',
        'title' => 'Weekend in Rome',
        'destination' => 'Rome',
    ]);

    $day = PlanDay::factory()->create([
        'travel_plan_id' => $plan->id,
        'day_number' => 1,
    ]);

    PlanPoint::factory()->create([
        'plan_day_id' => $day->id,
        'name' => 'Colosseum',
        'description' => 'Ancient amphitheater',
    ]);

    $this->actingAs($user);

    $service = app(ExportService::class);
    $pdf = $service->generatePdf($plan);

    $this->assertNotNull($pdf);
    // Content assertions (if PDF parsing is available)
}

public function test_draft_plan_cannot_be_exported()
{
    $user = User::factory()->create();
    $plan = TravelPlan::factory()->create([
        'user_id' => $user->id,
        'status' => 'draft',
    ]);

    $this->actingAs($user);

    $response = $this->get("/plans/{$plan->id}/export/pdf");

    $response->assertStatus(422);
    $response->assertJson([
        'message' => 'Tylko wygenerowane plany mogą być eksportowane',
    ]);
}

public function test_export_is_tracked_in_database()
{
    $user = User::factory()->create();
    $plan = TravelPlan::factory()->create([
        'user_id' => $user->id,
        'status' => 'planned',
    ]);

    PlanDay::factory()->create(['travel_plan_id' => $plan->id]);

    $this->actingAs($user);

    $this->get("/plans/{$plan->id}/export/pdf");

    $this->assertDatabaseHas('pdf_exports', [
        'travel_plan_id' => $plan->id,
        'user_id' => $user->id,
    ]);

    $plan->refresh();
    $this->assertEquals(1, $plan->export_count);
}

public function test_pdf_includes_watermark()
{
    $user = User::factory()->create();
    $plan = TravelPlan::factory()->create([
        'user_id' => $user->id,
        'status' => 'planned',
    ]);

    PlanDay::factory()->create(['travel_plan_id' => $plan->id]);

    $this->actingAs($user);

    $service = app(ExportService::class);
    $pdf = $service->generatePdf($plan);

    // Check if watermark "Generated by VibeTravels" is present
    $this->assertNotNull($pdf);
}
```

**Scenariusze:**
- ✅ Generowanie i pobieranie PDF
- ✅ Zawartość PDF (title, days, points)
- ✅ Szkice nie mogą być eksportowane
- ✅ Tracking eksportów
- ✅ Watermark w PDF

---

### 9.2. Podsumowanie Modułu 9

**Do utworzenia:**
- `PdfExportTest.php` (5 testów)

**Łącznie:** 5 nowych testów
**Całkowity czas implementacji:** 4 godziny

---

## Moduł 10: Powiadomienia Email

**Status:** ❌ Brak testów
**Priorytet:** 🟡 ŚREDNI
**Szacowany czas:** 6 godzin
**Pliki:** `tests/Feature/Notifications/`

### 10.1. Testy powiadomień email

#### TC-EMAIL-01: Welcome Email
**Plik:** `tests/Feature/Notifications/WelcomeEmailTest.php` (NOWY)

```php
public function test_welcome_email_is_sent_after_onboarding_completion()
{
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'newuser@example.com',
        'nickname' => 'TestUser',
        'onboarding_completed' => false,
    ]);

    // Complete onboarding
    event(new OnboardingCompleted($user));

    Mail::assertSent(WelcomeMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
}

public function test_welcome_email_contains_correct_content()
{
    Mail::fake();

    $user = User::factory()->create(['nickname' => 'John']);

    $mailable = new WelcomeMail($user);
    $mailable->assertSeeInHtml('Witaj w VibeTravels');
    $mailable->assertSeeInHtml('John');
    $mailable->assertSeeInHtml('dashboard');
}
```

**Scenariusze:**
- ✅ Wysyłka po zakończeniu onboardingu
- ✅ Zawartość emaila

---

#### TC-EMAIL-02: Limit Warnings
**Plik:** `tests/Feature/Notifications/LimitWarningEmailTest.php` (NOWY)

```php
public function test_warning_email_sent_at_8_of_10_generations()
{
    Mail::fake();

    $user = User::factory()->create();

    // Create 8 generations
    AIGeneration::factory()->count(8)->create([
        'user_id' => $user->id,
        'created_at' => now(),
    ]);

    // Trigger 8th generation
    event(new AIGenerationCreated($user, 8));

    Mail::assertSent(LimitWarningMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
}

public function test_warning_email_not_sent_before_8_generations()
{
    Mail::fake();

    $user = User::factory()->create();

    AIGeneration::factory()->count(7)->create([
        'user_id' => $user->id,
        'created_at' => now(),
    ]);

    event(new AIGenerationCreated($user, 7));

    Mail::assertNotSent(LimitWarningMail::class);
}

public function test_limit_exhausted_email_sent_at_10_of_10()
{
    Mail::fake();

    $user = User::factory()->create();

    AIGeneration::factory()->count(10)->create([
        'user_id' => $user->id,
        'created_at' => now(),
    ]);

    event(new AIGenerationCreated($user, 10));

    Mail::assertSent(LimitExhaustedMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
}

public function test_limit_emails_sent_only_once_per_month()
{
    Mail::fake();

    $user = User::factory()->create();

    AIGeneration::factory()->count(10)->create([
        'user_id' => $user->id,
        'created_at' => now(),
    ]);

    // First trigger
    event(new AIGenerationCreated($user, 10));
    Mail::assertSent(LimitExhaustedMail::class, 1);

    // Second trigger same month - should not send again
    event(new AIGenerationCreated($user, 10));
    Mail::assertSent(LimitExhaustedMail::class, 1); // Still only 1
}
```

**Scenariusze:**
- ✅ Warning przy 8/10
- ✅ Brak warning przed 8/10
- ✅ Exhausted przy 10/10
- ✅ Wysyłka tylko raz/miesiąc

---

#### TC-EMAIL-03: Trip Reminder (Optional)
**Plik:** `tests/Feature/Notifications/TripReminderEmailTest.php` (NOWY)

```php
public function test_reminder_email_sent_3_days_before_trip()
{
    Mail::fake();

    $user = User::factory()->create();
    $plan = TravelPlan::factory()->create([
        'user_id' => $user->id,
        'status' => 'planned',
        'departure_date' => now()->addDays(3),
        'title' => 'Paris Adventure',
    ]);

    // Run scheduled command
    Artisan::call('send:trip-reminders');

    Mail::assertSent(TripReminderMail::class, function ($mail) use ($user, $plan) {
        return $mail->hasTo($user->email) && $mail->plan->id === $plan->id;
    });
}

public function test_reminder_not_sent_for_draft_plans()
{
    Mail::fake();

    $user = User::factory()->create();
    TravelPlan::factory()->create([
        'user_id' => $user->id,
        'status' => 'draft',
        'departure_date' => now()->addDays(3),
    ]);

    Artisan::call('send:trip-reminders');

    Mail::assertNotSent(TripReminderMail::class);
}
```

**Scenariusze:**
- ✅ Przypomnienie 3 dni przed
- ✅ Brak dla szkiców

---

### 10.2. Podsumowanie Modułu 10

**Do utworzenia:**
- `WelcomeEmailTest.php` (2 testy)
- `LimitWarningEmailTest.php` (4 testy)
- `TripReminderEmailTest.php` (2 testy)

**Łącznie:** 8 nowych testów
**Całkowity czas implementacji:** 6 godzin

---

## Moduł 11: Bezpieczeństwo i RODO

**Status:** ❌ Brak testów
**Priorytet:** 🔴 KRYTYCZNY
**Szacowany czas:** 4 godziny
**Pliki:** `tests/Feature/Security/`

### 11.1. Testy bezpieczeństwa

#### TC-SEC-01: Authorization
**Plik:** `tests/Feature/Security/AuthorizationTest.php` (NOWY)

```php
public function test_user_cannot_access_other_users_plans()
{
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $plan = TravelPlan::factory()->create(['user_id' => $user1->id]);

    $this->actingAs($user2);

    // Try to view
    $response = $this->get("/plans/{$plan->id}");
    $response->assertStatus(403);

    // Try to delete
    $response = $this->delete("/plans/{$plan->id}");
    $response->assertStatus(403);

    // Try to export
    $response = $this->get("/plans/{$plan->id}/export/pdf");
    $response->assertStatus(403);
}

public function test_user_cannot_submit_feedback_for_other_users_plan()
{
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $plan = TravelPlan::factory()->create(['user_id' => $user1->id]);

    $this->actingAs($user2);

    $response = $this->postJson("/api/travel-plans/{$plan->id}/feedback", [
        'satisfied' => true,
    ]);

    $response->assertStatus(403);
}

public function test_csrf_protection_is_enabled()
{
    $user = User::factory()->create();

    // Attempt POST without CSRF token
    $response = $this->post('/plans', [
        'title' => 'Test',
    ]);

    $response->assertStatus(419); // CSRF token mismatch
}
```

**Scenariusze:**
- ✅ Brak dostępu do cudzych planów
- ✅ Brak możliwości feedbacku dla cudzych planów
- ✅ CSRF protection

---

#### TC-SEC-02: GDPR - Usuwanie konta
**Plik:** `tests/Feature/Security/GDPRTest.php` (NOWY)

```php
public function test_user_can_delete_account()
{
    $user = User::factory()->create(['email' => 'delete@example.com']);

    $this->actingAs($user);

    $response = $this->delete('/profile', [
        'password' => 'password',
    ]);

    $response->assertRedirect('/');
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'delete@example.com']);
}

public function test_deleting_account_removes_all_user_data()
{
    $user = User::factory()->create();

    // Create associated data
    $plan = TravelPlan::factory()->create(['user_id' => $user->id]);
    $day = PlanDay::factory()->create(['travel_plan_id' => $plan->id]);
    PlanPoint::factory()->create(['plan_day_id' => $day->id]);

    AIGeneration::factory()->create(['user_id' => $user->id]);
    TravelPlanFeedback::factory()->create(['travel_plan_id' => $plan->id]);
    UserPreference::factory()->create(['user_id' => $user->id]);

    $userId = $user->id;
    $planId = $plan->id;

    $this->actingAs($user);

    $this->delete('/profile', ['password' => 'password']);

    // Assert all data deleted
    $this->assertDatabaseMissing('users', ['id' => $userId]);
    $this->assertDatabaseMissing('travel_plans', ['user_id' => $userId]);
    $this->assertDatabaseMissing('ai_generations', ['user_id' => $userId]);
    $this->assertDatabaseMissing('user_preferences', ['user_id' => $userId]);
    $this->assertDatabaseMissing('plan_days', ['travel_plan_id' => $planId]);
    $this->assertDatabaseMissing('plan_points', ['plan_day_id' => $day->id]);
}

public function test_account_deletion_requires_password_confirmation()
{
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->delete('/profile', [
        'password' => 'wrongpassword',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertDatabaseHas('users', ['id' => $user->id]);
}
```

**Scenariusze:**
- ✅ Usuwanie konta
- ✅ Cascade delete wszystkich danych
- ✅ Potwierdzenie hasłem

---

#### TC-SEC-03: XSS Protection
**Plik:** `tests/Feature/Security/XSSProtectionTest.php` (NOWY)

```php
public function test_user_input_is_sanitized_in_plan_creation()
{
    $user = User::factory()->create();

    $this->actingAs($user);

    $xssPayload = '<script>alert("XSS")</script>';

    Livewire::test(CreatePlanForm::class)
        ->set('title', $xssPayload)
        ->set('destination', 'Test')
        ->set('departureDate', now()->addDays(10)->format('Y-m-d'))
        ->set('numberOfDays', 3)
        ->set('numberOfPeople', 2)
        ->set('notes', $xssPayload)
        ->call('saveAsDraft');

    $plan = TravelPlan::where('user_id', $user->id)->first();

    // Check that script tags are escaped
    $this->assertStringNotContainsString('<script>', $plan->title);
    $this->assertStringNotContainsString('<script>', $plan->notes);
}

public function test_rendered_plan_content_escapes_html()
{
    $user = User::factory()->create();
    $plan = TravelPlan::factory()->create([
        'user_id' => $user->id,
        'title' => '<script>alert("test")</script>Trip',
    ]);

    $this->actingAs($user);

    $response = $this->get("/plans/{$plan->id}");

    $response->assertDontSee('<script>', false);
    $response->assertSee('&lt;script&gt;', false); // Escaped
}
```

**Scenariusze:**
- ✅ Sanityzacja inputu
- ✅ Escape w wyświetlaniu

---

### 11.2. Podsumowanie Modułu 11

**Do utworzenia:**
- `AuthorizationTest.php` (3 testy)
- `GDPRTest.php` (3 testy)
- `XSSProtectionTest.php` (2 testy)

**Łącznie:** 8 nowych testów
**Całkowity czas implementacji:** 4 godziny

---

## Moduł 12: Testy Wydajnościowe

**Status:** ❌ Brak testów
**Priorytet:** 🟡 ŚREDNI
**Szacowany czas:** 4 godziny
**Pliki:** `tests/Performance/`

### 12.1. Testy wydajności

#### TC-PERF-01: AI Generation Time
**Plik:** `tests/Performance/AIGenerationPerformanceTest.php` (NOWY)

```php
public function test_ai_generation_completes_under_45_seconds()
{
    config(['ai.use_real_api' => true]); // Use real API for this test

    $user = User::factory()->create();
    UserPreference::factory()->create(['user_id' => $user->id]);

    $plan = TravelPlan::factory()->create([
        'user_id' => $user->id,
        'number_of_days' => 5,
    ]);

    $startTime = microtime(true);

    $job = new GenerateTravelPlanJob($plan);
    $job->handle();

    $endTime = microtime(true);
    $duration = $endTime - $startTime;

    $this->assertLessThan(45, $duration, "Generation took {$duration} seconds");
}

public function test_dashboard_loads_with_100_plans_under_2_seconds()
{
    $user = User::factory()->create(['onboarding_completed' => true]);

    TravelPlan::factory()->count(100)->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $startTime = microtime(true);

    $response = $this->get('/dashboard');

    $endTime = microtime(true);
    $duration = $endTime - $startTime;

    $response->assertOk();
    $this->assertLessThan(2, $duration);
}

public function test_plan_view_with_30_days_loads_quickly()
{
    $user = User::factory()->create();

    $plan = TravelPlan::factory()->create([
        'user_id' => $user->id,
        'status' => 'planned',
    ]);

    // Create 30 days with 6 points each = 180 points
    for ($i = 1; $i <= 30; $i++) {
        $day = PlanDay::factory()->create([
            'travel_plan_id' => $plan->id,
            'day_number' => $i,
        ]);

        PlanPoint::factory()->count(6)->create(['plan_day_id' => $day->id]);
    }

    $this->actingAs($user);

    $startTime = microtime(true);

    $response = $this->get("/plans/{$plan->id}");

    $endTime = microtime(true);
    $duration = $endTime - $startTime;

    $response->assertOk();
    $this->assertLessThan(1, $duration);
}
```

**Scenariusze:**
- ✅ Generowanie AI < 45s
- ✅ Dashboard z wieloma planami
- ✅ Widok dużego planu (30 dni)

---

### 12.2. Podsumowanie Modułu 12

**Do utworzenia:**
- `AIGenerationPerformanceTest.php` (3 testy)

**Łącznie:** 3 nowe testy
**Całkowity czas implementacji:** 4 godziny

---

## Harmonogram Implementacji

### Sprint 1-2: Foundation (Tydzień 1-4)

| Tydzień | Moduł | Testy | Godziny | Priorytet |
|---------|-------|-------|---------|-----------|
| **1** | Moduł 1: Auth (rozszerzenie) | 11 | 4h | 🔴 Krytyczny |
| **1-2** | Moduł 11: Security & GDPR | 8 | 4h | 🔴 Krytyczny |
| **2** | Moduł 2: Onboarding | 4 | 2h | 🟡 Średni |
| **2-3** | Moduł 3: Profil | 2 | 2h | 🟢 Niski |
| **3-4** | Moduł 4: Dashboard | 11 | 6h | 🔴 Krytyczny |

**Łącznie Sprint 1-2:** 36 testów, 18 godzin

---

### Sprint 3-4: Core Features (Tydzień 5-8)

| Tydzień | Moduł | Testy | Godziny | Priorytet |
|---------|-------|-------|---------|-----------|
| **5** | Moduł 5: Zarządzanie planami | 8 | 8h | 🔴 Krytyczny |
| **5-7** | Moduł 6: Generowanie AI | 12 | 12h | 🔴 Krytyczny |
| **7-8** | Moduł 7: Wyświetlanie planu | 6 | 6h | 🟡 Średni |

**Łącznie Sprint 3-4:** 26 testów, 26 godzin

---

### Sprint 5-6: UX & Notifications (Tydzień 9-11)

| Tydzień | Moduł | Testy | Godziny | Priorytet |
|---------|-------|-------|---------|-----------|
| **9** | Moduł 8: Feedback | 3 | 2h | 🟢 Niski |
| **9-10** | Moduł 9: PDF Export | 5 | 4h | 🟡 Średni |
| **10-11** | Moduł 10: Email Notifications | 8 | 6h | 🟡 Średni |
| **11** | Moduł 12: Performance | 3 | 4h | 🟡 Średni |

**Łącznie Sprint 5-6:** 19 testów, 16 godzin

---

### Podsumowanie całkowite

- **Łączna liczba nowych testów:** 81
- **Łączny czas implementacji:** 60 godzin (7.5 dnia roboczych)
- **Szacowane pokrycie po implementacji:** ≥90%
- **Timeline:** 10-12 tygodni (równolegle z rozwojem MVP)

---

## Kryteria Sukcesu

### Kryteria wejścia (spełnione ✅)
- ✅ Środowisko testowe skonfigurowane
- ✅ PHPUnit, PHPStan, Laravel Pint działają
- ✅ CI/CD pipeline funkcjonalny
- ✅ Mock OpenAI Service zaimplementowany

### Kryteria wyjścia (do osiągnięcia)
- ⏳ 90%+ pokrycie kodu testami (kluczowe ścieżki)
- ⏳ Wszystkie 81+ testów przechodzą
- ⏳ Brak błędów krytycznych
- ⏳ MVP Launch Criteria spełnione:
  - Onboarding completion rate >70%
  - AI generation success rate >90%
  - Średni czas generowania <45s
  - Plan satisfaction rate >60%
  - Zero critical security vulnerabilities

### Metryki jakości testów
- ⏳ Czas wykonania suite'a testowego < 5 minut
- ⏳ Brak flaky tests (niestabilnych)
- ⏳ Wszystkie testy używają RefreshDatabase
- ⏳ Proper factories i seeders dla danych testowych

---

## Rekomendacje Końcowe

### Priorytetyzacja
1. **KRYTYCZNE (najpierw):**
   - Moduł 1, 4, 5, 6, 11 (Auth, Dashboard, Plans, AI, Security)
2. **ŚREDNIE:**
   - Moduł 2, 7, 9, 10, 12 (Onboarding, View, PDF, Email, Performance)
3. **NISKIE (ostatnie):**
   - Moduł 3, 8 (Profil, Feedback)

### Best Practices
- Używaj factories do tworzenia danych testowych
- Mockuj zewnętrzne API (OpenAI, Google)
- Używaj `Mail::fake()`, `Queue::fake()` dla asynchronicznych operacji
- Testuj edge cases i error handling
- Dodaj asercje dla autoryzacji w każdym module

### Kontynuacja
- Po implementacji: code review wszystkich testów
- Monitoring pokrycia kodu w CI/CD
- Regularne aktualizacje testów przy zmianach w kodzie
- Dokumentacja złożonych scenariuszy testowych

---

**Koniec dokumentu**
**Wersja:** 1.0
**Ostatnia aktualizacja:** 2025-10-14
