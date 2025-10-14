# Plan Implementacyjny - VibeTravels MVP
**Wersja**: 1.0
**Data utworzenia**: 2025-10-14
**Status**: W przygotowaniu

---

## ZARYS OGÓLNY

### 1. ANALIZA STANU OBECNEGO
- 1.1. Weryfikacja implementacji
- 1.2. Identyfikacja luk względem PRD

### 2. FUNKCJONALNOŚCI AUTENTYKACJI I AUTORYZACJI
- 2.1. Email verification system
- 2.2. Rate limiting
- 2.3. HTTPS enforcement
- 2.4. Account deletion (GDPR)

### 3. ONBOARDING I PREFERENCJE
- 3.1. Weryfikacja flow onboardingu
- 3.2. Edycja preferencji
- 3.3. Tracking completion rate

### 4. DASHBOARD I NAWIGACJA
- 4.1. UI limit counter
- 4.2. Plan filtering improvements
- 4.3. Sortowanie planów
- 4.4. Język aplikacji (ujednolicenie PL/EN)

### 5. ZARZĄDZANIE PLANAMI
- 5.1. Regeneracja planów
- 5.2. Status transitions
- 5.3. Plan deletion improvements

### 6. SYSTEM EMAIL
- 6.1. Welcome email
- 6.2. Limit notification emails (8/10, 10/10)
- 6.3. Trip reminder email (opcjonalnie)
- 6.4. Email templates improvements

### 7. ANALYTICS I METRYKI
- 7.1. User events tracking
- 7.2. Analytics dashboard
- 7.3. Metrics calculation

### 8. AUTOMATYZACJA (CRON JOBS)
- 8.1. Monthly limit reset
- 8.2. Auto-complete past trips
- 8.3. Email reminders scheduler

### 9. ERROR HANDLING I MONITORING
- 9.1. AI generation error handling
- 9.2. Error logging improvements
- 9.3. User-facing error messages

### 10. TESTY I JAKOŚĆ KODU
- 10.1. Unit tests dla nowych funkcji
- 10.2. Feature tests dla user stories
- 10.3. Code quality improvements

### 11. DOKUMENTACJA I DEPLOYMENT
- 11.1. API documentation
- 11.2. Privacy Policy & Terms of Service
- 11.3. Production deployment checklist

---

## SZCZEGÓŁOWY PLAN IMPLEMENTACJI

### 1. ANALIZA STANU OBECNEGO

#### 1.1. Weryfikacja implementacji
**Cel**: Zweryfikować co jest zaimplementowane vs PRD

**Zadania**:
- [ ] Przegląd wszystkich US (User Stories) z PRD
- [ ] Sprawdzenie funkcjonalności autentykacji
- [ ] Sprawdzenie funkcjonalności onboardingu
- [ ] Sprawdzenie dashboard i zarządzania planami
- [ ] Sprawdzenie generowania AI
- [ ] Sprawdzenie systemu email
- [ ] Sprawdzenie analytics

**Status obecny** (na podstawie CLAUDE.md):
- ✅ **Zaimplementowane**:
  - Authentication (email + Google OAuth)
  - Onboarding wizard z preferencjami
  - Dashboard z listą planów
  - Tworzenie/zapisywanie/usuwanie planów
  - Generowanie AI (OpenAI)
  - Wyświetlanie planów dzień po dniu
  - System feedbacku
  - Eksport PDF
  - Tracking limitów AI (10/miesiąc) - backend

- ⚠️ **Częściowo zaimplementowane**:
  - Email notifications (szablony mogą być niekompletne)
  - Plan filtering/sorting UI
  - Limit counter display w UI
  - Regenerate plan UI

- ❌ **Nie zaimplementowane**:
  - Analytics dashboard
  - Rate limiting
  - Automated cron jobs (monthly reset, auto-complete trips)
  - User events tracking implementation
  - Error handling dla AI failures

#### 1.2. Identyfikacja luk względem PRD
**Cel**: Utworzenie listy brakujących funkcjonalności

**Zadania**:
- [ ] Porównanie z każdym wymaganiem z sekcji 3 PRD
- [ ] Porównanie z każdą User Story (sekcja 5 PRD)
- [ ] Priorytetyzacja zadań (must-have vs nice-to-have)

**Zidentyfikowane luki**:

**KRYTYCZNE (must-have dla MVP)**:
1. Email verification flow (US-003, US-029, US-030)
2. Rate limiting dla wrażliwych operacji (PRD 3.13)
3. Regeneracja planów (US-026)
4. Email notifications (welcome, limit warnings) (US-031, US-032, US-033)
5. Cron job - monthly limit reset (PRD 3.6)
6. Error handling dla AI (US-024)
7. UI - licznik limitów w sidebar/topbar (US-016)
8. UI - filtrowanie planów (US-015)
9. Hard delete account (GDPR) (US-010)
10. Język aplikacji - ujednolicenie PL/EN (PRD 4.3, Changelog v1.1)

**WAŻNE (powinno być w MVP)**:
11. Cron job - auto-complete past trips
12. Analytics tracking podstawowy (PRD 3.14, US-035 do US-040)
13. Status transitions dla planów (draft→planned→completed)
14. Przypomnienie przed wycieczką email (US-034) - opcjonalne
15. Session timeout handling

**NICE-TO-HAVE (można odłożyć)**:
16. Analytics dashboard UI
17. Skeleton loaders
18. Custom error pages (404/403/429)
19. Zaawansowane metryki

---

### 2. FUNKCJONALNOŚCI AUTENTYKACJI I AUTORYZACJI

#### 2.1. Email verification system
**User Stories**: US-003, US-029, US-030
**PRD**: Sekcja 3.1, 3.12

**Status**: Częściowo zaimplementowane (Laravel Breeze default)

**Zadania**:
- [ ] Weryfikacja czy Laravel Breeze email verification działa poprawnie
- [ ] Sprawdzenie szablonu emaila weryfikacyjnego
  - [ ] Temat: "Zweryfikuj swój adres email w VibeTravels"
  - [ ] Polski język
  - [ ] Jasne instrukcje i CTA button
  - [ ] Link ważny 24h
- [ ] Implementacja ponownego wysłania emaila weryfikacyjnego
  - [ ] Banner dla niezweryfikowanych użytkowników (sticky?)
  - [ ] Link "Wyślij ponownie email weryfikacyjny"
  - [ ] Rate limiting: max 1 email / 5 minut
  - [ ] Komunikat potwierdzający wysłanie
- [ ] Testing flow:
  - [ ] Test rejestracji i otrzymania emaila
  - [ ] Test kliknięcia w link (success)
  - [ ] Test wygasłego linku (error + resend option)
  - [ ] Test rate limiting
- [ ] Route: `routes/auth.php` - sprawdzić czy istnieje email verification route
- [ ] Controller: sprawdzić `Auth/VerifyEmailController.php`

**Pliki do modyfikacji/utworzenia**:
- `resources/views/emails/verify-email.blade.php` (jeśli nie istnieje)
- `app/Http/Controllers/Auth/VerifyEmailController.php`
- Możliwy nowy komponent: `app/Livewire/Auth/EmailVerificationBanner.php`
- Test: `tests/Feature/Auth/EmailVerificationTest.php`

**Szacowany czas**: 4-6h

#### 2.2. Rate limiting
**User Stories**: Bezpośrednio nie wymienione, ale wymagane w PRD 3.13
**PRD**: Sekcja 3.13

**Status**: Nie zaimplementowane

**Zadania**:
- [ ] Zdefiniowanie rate limits w `app/Providers/AppServiceProvider.php` lub `RouteServiceProvider`
  - [ ] Login: max 5 prób / 1 minuta
  - [ ] Rejestracja: max 3 próby / 1 minuta
  - [ ] Generowanie AI: max 3 próby / 1 minuta (dodatkowa ochrona poza limitem miesięcznym)
  - [ ] Email verification resend: max 1 / 5 minut (już w 2.1)
  - [ ] Password reset: max 3 próby / 1 minuta
- [ ] Middleware: użycie Laravel `throttle` middleware
- [ ] Custom response dla rate limit exceeded (JSON dla API, flash message dla web)
- [ ] Testing:
  - [ ] Test przekroczenia limitu logowania
  - [ ] Test przekroczenia limitu rejestracji
  - [ ] Test przekroczenia limitu AI generation

**Pliki do modyfikacji**:
- `app/Providers/AppServiceProvider.php` lub `app/Providers/RouteServiceProvider.php`
- `routes/web.php` - dodanie middleware do routes
- `routes/api.php` - dodanie middleware do API routes
- Test: `tests/Feature/RateLimitingTest.php`

**Szacowany czas**: 3-4h

#### 2.3. HTTPS enforcement
**PRD**: Sekcja 3.13

**Status**: Częściowo (tylko w production via env)

**Zadania**:
- [ ] Sprawdzenie czy `AppServiceProvider` wymusza HTTPS w production
- [ ] Dodanie middleware `App\Http\Middleware\HttpsProtocol` jeśli nie istnieje
- [ ] Konfiguracja w `.env.production`:
  ```
  APP_FORCE_HTTPS=true
  ```
- [ ] Testing w staging/production environment

**Pliki do modyfikacji/utworzenia**:
- `app/Providers/AppServiceProvider.php` - dodać `URL::forceScheme('https')` w production
- Lub `app/Http/Middleware/ForceHttps.php` (nowy middleware)
- `app/Http/Kernel.php` - rejestracja middleware

**Szacowany czas**: 1-2h

#### 2.4. Account deletion (GDPR)
**User Stories**: US-010
**PRD**: Sekcja 3.1, 3.13

**Status**: Model ma SoftDeletes, ale brak UI i hard delete

**Zadania**:
- [ ] Analiza czy używać soft delete czy hard delete
  - PRD wymaga: "hard delete, zgodność z GDPR"
  - Decyzja: Hard delete z opcją grace period (soft delete + cron job cleanup po 30 dniach)
- [ ] UI - dodanie opcji w ustawieniach profilu
  - [ ] Przycisk "Usuń konto" w sekcji dangerous actions
  - [ ] Modal z ostrzeżeniem o trwałym usunięciu
  - [ ] Potwierdzenie (wpisanie hasła lub słowa "DELETE")
- [ ] Backend - endpoint usuwania konta
  - [ ] Usunięcie powiązanych danych:
    - [ ] User preferences
    - [ ] Travel plans (+ plan_days, plan_points)
    - [ ] AI generations
    - [ ] Feedback
    - [ ] PDF exports
    - [ ] User events (jeśli istnieją)
  - [ ] Wylogowanie użytkownika
  - [ ] Redirect do landing page z komunikatem
- [ ] Email - potwierdzenie usunięcia konta (opcjonalnie)
- [ ] Testing:
  - [ ] Test usunięcia konta i kaskadowego usuwania danych
  - [ ] Test niemożności zalogowania po usunięciu

**Pliki do modyfikacji/utworzenia**:
- `app/Livewire/Profile/DeleteUserForm.php` (może już istnieć - sprawdzić)
- `resources/views/livewire/profile/delete-user-form.blade.php`
- `app/Actions/User/DeleteUserAccountAction.php` (nowy)
- `app/Models/User.php` - metoda `deleteAccount()` lub w Action
- Test: `tests/Feature/Profile/DeleteAccountTest.php`

**Szacowany czas**: 4-5h

---

### 3. ONBOARDING I PREFERENCJE

#### 3.1. Weryfikacja flow onboardingu
**User Stories**: US-006, US-007, US-008
**PRD**: Sekcja 3.2

**Status**: ✅ Zaimplementowane (3 kroki + completion)

**Zadania weryfikacyjne**:
- [ ] Test pełnego flow onboardingu:
  - [ ] Krok 1: Nickname + Home location
  - [ ] Krok 2: Kategorie zainteresowań (min 1)
  - [ ] Krok 3: Parametry praktyczne (wszystkie 4)
  - [ ] Completion action
- [ ] Sprawdzenie czy nie można pominąć onboardingu (middleware)
- [ ] Sprawdzenie zapisywania danych po każdym kroku
- [ ] Sprawdzenie walidacji na każdym kroku
- [ ] Sprawdzenie czy można wrócić do poprzedniego kroku
- [ ] Sprawdzenie progress bar UI

**Możliwe poprawki**:
- [ ] Jeśli brak - dodać tracking `onboarding_completion_rate` (analytics)
- [ ] Jeśli brak - wysłanie welcome email po completion (patrz 6.1)

**Plik do sprawdzenia**:
- `app/Livewire/Onboarding/OnboardingWizard.php` ✅ (już sprawdzony - wygląda OK)
- `resources/views/livewire/onboarding/onboarding-wizard.blade.php`
- `app/Actions/Onboarding/CompleteOnboardingAction.php`
- `app/Http/Middleware/EnsureOnboardingCompleted.php`

**Szacowany czas**: 2-3h (głównie testing i małe poprawki)

#### 3.2. Edycja preferencji
**User Stories**: US-012, US-013
**PRD**: Sekcja 3.3

**Status**: ⚠️ Wymaga weryfikacji

**Zadania**:
- [ ] Sprawdzenie czy istnieje widok edycji profilu
  - Plik: `resources/views/profile.blade.php` lub `resources/views/livewire/profile/*`
- [ ] Sprawdzenie czy można edytować:
  - [ ] Nickname
  - [ ] Home location
  - [ ] Kategorie zainteresowań
  - [ ] Parametry praktyczne (pace, budget, transport, restrictions)
- [ ] Email powinien być tylko do odczytu (zweryfikowany)
- [ ] UI - sekcja "Preferencje turystyczne" z przyciskiem "Edytuj"
- [ ] Walidacja analogiczna jak w onboardingu
- [ ] Komunikat potwierdzający po zapisaniu
- [ ] Testing:
  - [ ] Test edycji profilu
  - [ ] Test edycji preferencji
  - [ ] Test walidacji
  - [ ] Test czy zmiany są uwzględniane w kolejnych generowaniach AI

**Pliki do sprawdzenia/utworzenia**:
- `resources/views/profile.blade.php` ✅ (istnieje)
- `app/Livewire/Profile/UpdateProfileInformationForm.php` ✅ (istnieje)
- Możliwe brakujące: `app/Livewire/Profile/UpdatePreferencesForm.php`
- `app/Actions/Preferences/UpdateUserPreferencesAction.php` ✅ (istnieje)
- Test: `tests/Feature/Profile/UpdatePreferencesTest.php`

**Szacowany czas**: 3-4h

#### 3.3. Tracking completion rate
**User Stories**: US-035
**PRD**: Sekcja 3.14, 5.8

**Status**: ❌ Nie zaimplementowane (analytics)

**Zadania**:
- [ ] Event tracking dla onboarding:
  - [ ] Event: `OnboardingStarted` (po rejestracji)
  - [ ] Event: `OnboardingStepCompleted` (po każdym kroku)
  - [ ] Event: `OnboardingCompleted` (po zakończeniu)
  - [ ] Event: `OnboardingAbandoned` (jeśli użytkownik nie wraca przez X dni)
- [ ] Zapisywanie eventów w tabeli `user_events`
- [ ] Metoda w `User` model: `hasCompletedProfile()` - sprawdza czy wszystkie pola wypełnione
- [ ] Analytics query: completion rate calculation
  ```sql
  SELECT
    COUNT(DISTINCT CASE WHEN onboarding_completed = 1 THEN id END) / COUNT(*) * 100 AS completion_rate
  FROM users
  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
  ```

**Pliki do utworzenia/modyfikacji**:
- `app/Events/Onboarding/OnboardingStarted.php`
- `app/Events/Onboarding/OnboardingStepCompleted.php`
- `app/Events/Onboarding/OnboardingCompleted.php`
- `app/Listeners/Onboarding/TrackOnboardingEvent.php`
- `app/Models/UserEvent.php` (może już istnieć)
- Migration dla `user_events` ✅ (już istnieje)
- `app/Services/AnalyticsService.php` - metody do obliczania metryk

**Szacowany czas**: 4-5h

---

### 4. DASHBOARD I NAWIGACJA

#### 4.1. UI limit counter
**User Stories**: US-016
**PRD**: Sekcja 3.4

**Status**: ⚠️ Backend OK, UI brak

**Zadania**:
- [ ] Dodanie licznika limitów w sidebar/topbar
  - Lokalizacja: `resources/views/livewire/layout/navigation.blade.php` lub `layouts/app.blade.php`
- [ ] Format wyświetlania: "X/10 w tym miesiącu"
- [ ] Kolorowanie według procentu wykorzystania:
  - Zielony: 0-69% (0-6 użytych)
  - Żółty: 70-89% (7-8 użytych)
  - Czerwony: 90-100% (9-10 użytych)
- [ ] Tooltip z informacją o dacie resetu (1. następnego miesiąca)
- [ ] Link do wyjaśnienia limitów (opcjonalnie)
- [ ] Testing:
  - [ ] Wyświetlanie poprawnej liczby
  - [ ] Zmiana koloru przy różnych wartościach
  - [ ] Aktualizacja po generowaniu planu

**Pliki do modyfikacji**:
- `resources/views/livewire/layout/navigation.blade.php` ✅ (istnieje)
- `resources/views/layouts/app.blade.php` ✅ (istnieje)
- Możliwy nowy komponent: `app/Livewire/Components/LimitCounter.php`
- `app/Services/LimitService.php` ✅ (już istnieje z metodą `getLimitInfo()`)

**Szacowany czas**: 2-3h

#### 4.2. Plan filtering improvements
**User Stories**: US-015
**PRD**: Sekcja 3.4, 3.8

**Status**: ⚠️ Częściowo zaimplementowane (search istnieje, statusy mogą brakować)

**Zadania**:
- [ ] Sprawdzenie obecnego dashboard:
  - Plik: `app/Livewire/Dashboard.php` ✅ (istnieje)
  - Plik: `resources/views/livewire/dashboard.blade.php` ✅ (istnieje)
- [ ] Quick filters nad listą planów:
  - [ ] Wszystkie (default)
  - [ ] Szkice (status = 'draft')
  - [ ] Zaplanowane (status = 'planned')
  - [ ] Zrealizowane (status = 'completed')
- [ ] UI - przyciski filtrów (pill-style buttons)
- [ ] Aktywny filtr wizualnie wyróżniony
- [ ] Liczba wyświetlanych planów odpowiada filtrowi
- [ ] Query w Livewire component dla każdego statusu
- [ ] Testing:
  - [ ] Filtrowanie po każdym statusie
  - [ ] Liczba wyników
  - [ ] Przełączanie między filtrami

**Pliki do modyfikacji**:
- `app/Livewire/Dashboard.php` - dodać property `$statusFilter` i metody filtrowania
- `resources/views/livewire/dashboard.blade.php` - dodać UI filtrów
- Test: `tests/Feature/Dashboard/PlanFilteringTest.php`

**Szacowany czas**: 3-4h

#### 4.3. Sortowanie planów
**User Stories**: US-014
**PRD**: Sekcja 3.8

**Status**: ⚠️ Wymaga weryfikacji (domyślnie najnowsze na górze)

**Zadania**:
- [ ] Sprawdzenie obecnego sortowania w Dashboard:
  - Query: `TravelPlan::where('user_id', auth()->id())->latest()->get()`
- [ ] Opcje sortowania (dropdown lub tabs):
  - [ ] Najnowsze (created_at DESC) - default
  - [ ] Ostatnio modyfikowane (updated_at DESC)
  - [ ] Nazwa A-Z (title ASC)
  - [ ] Data wyjazdu (start_date ASC)
- [ ] Zapamiętywanie wybranego sortowania w sesji
- [ ] UI - select/dropdown do wyboru sortowania
- [ ] Testing:
  - [ ] Każde sortowanie działa poprawnie
  - [ ] Domyślne sortowanie

**Pliki do modyfikacji**:
- `app/Livewire/Dashboard.php` - dodać property `$sortBy` i logikę sortowania
- `resources/views/livewire/dashboard.blade.php` - dodać UI sortowania
- Test: `tests/Feature/Dashboard/PlanSortingTest.php`

**Szacowany czas**: 2-3h

#### 4.4. Język aplikacji (ujednolicenie PL/EN)
**PRD**: Sekcja 4.3, Changelog v1.1
**User Stories**: Pośrednio wszystkie (UX)

**Status**: ⚠️ Mieszane (PL w onboarding, EN w landing, backend)

**Decyzja wymagana**: Który język wybrać jako główny?
- **Opcja A**: Polski wszędzie (zgodnie z PRD 4.3)
- **Opcja B**: Angielski wszędzie (skalowanie międzynarodowe)
- **Opcja C**: Multi-language (wymaga więcej pracy - poza MVP)

**Założenie**: Wybieramy **Polski** zgodnie z PRD

**Zadania**:
- [ ] Audit wszystkich widoków:
  - [ ] Landing page: `resources/views/landing.blade.php` - przetłumaczyć na PL
  - [ ] Welcome page: `resources/views/livewire/welcome.blade.php`
  - [ ] Auth views: `resources/views/auth/*` i `resources/views/livewire/pages/auth/*`
  - [ ] Dashboard: `resources/views/livewire/dashboard.blade.php`
  - [ ] Plans: `resources/views/livewire/plans/*`
  - [ ] Profile: `resources/views/profile.blade.php`
  - [ ] Email templates: `resources/views/emails/*`
- [ ] Zmiana wszystkich komunikatów na polski:
  - [ ] Flash messages
  - [ ] Validation messages
  - [ ] Button labels
  - [ ] Form labels
  - [ ] Headings
- [ ] Użycie Laravel localization:
  - [ ] Utworzenie plików `lang/pl/*.php`
  - [ ] Przeniesienie wszystkich stringów do translation files
  - [ ] Użycie `__('messages.key')` w views
- [ ] Konfiguracja:
  - `.env`: `APP_LOCALE=pl`, `APP_FALLBACK_LOCALE=pl`
  - `config/app.php`: `'locale' => 'pl'`
- [ ] Testing:
  - [ ] Wszystkie strony wyświetlają się po polsku
  - [ ] Wszystkie komunikaty są po polsku
  - [ ] Daty formatowane po polsku

**Pliki do utworzenia/modyfikacji**:
- `lang/pl/auth.php`
- `lang/pl/validation.php`
- `lang/pl/messages.php` (custom messages)
- Wszystkie view files - zamiana hardcoded text na `__()` helper
- `config/app.php`
- `.env`

**Szacowany czas**: 8-12h (dużo pracy manualnej)

---

### 5. ZARZĄDZANIE PLANAMI

#### 5.1. Regeneracja planów
**User Stories**: US-026
**PRD**: Sekcja 3.9

**Status**: ⚠️ Częściowo (backend może istnieć, UI i flow może brakować)

**Zadania**:
- [ ] Sprawdzenie czy istnieje endpoint/metoda regeneracji
  - Plik: `app/Livewire/Plans/Show.php` lub `app/Actions/TravelPlan/*`
- [ ] UI - przycisk "Regeneruj plan" w widoku planu:
  - Lokalizacja: `resources/views/livewire/plans/show.blade.php`
  - Plik komponentu: `app/Livewire/Components/PlanActions.php` ✅ (istnieje)
- [ ] Modal z ostrzeżeniem przed regeneracją:
  - Tekst: "Spowoduje to wygenerowanie nowego planu (X/10 w tym miesiącu). Kontynuować?"
  - Przyciski: "Anuluj", "Regeneruj"
- [ ] Backend logic:
  - [ ] Sprawdzenie dostępności limitu (`LimitService::canGenerate()`)
  - [ ] Jeśli limit wyczerpany - pokazać komunikat błędu
  - [ ] Jeśli OK - wywołać `TravelPlanService::regeneratePlan()`
  - [ ] Inkrementacja limitu (`LimitService::incrementGenerationCount()`)
  - [ ] Nadpisanie poprzednich dni/punktów (delete old, create new)
  - [ ] Zachowanie feedbacku? (decyzja: usunąć stary feedback)
  - [ ] Loading state podczas generowania
- [ ] Error handling:
  - [ ] Timeout AI - rollback limitu
  - [ ] API error - rollback limitu
  - [ ] Incomplete response - rollback limitu
- [ ] Testing:
  - [ ] Regeneracja planu z dostępnym limitem
  - [ ] Blokada regeneracji gdy limit wyczerpany
  - [ ] Rollback przy błędzie
  - [ ] Nadpisanie starych danych

**Pliki do modyfikacji/utworzenia**:
- `app/Livewire/Plans/Show.php` - dodać metodę `regeneratePlan()`
- `app/Livewire/Components/PlanActions.php` ✅ (istnieje - dodać button i modal)
- `app/Services/TravelPlanService.php` ✅ (istnieje - dodać metodę `regeneratePlan()`)
- `resources/views/livewire/plans/show.blade.php` - dodać modal
- Test: `tests/Feature/Plans/RegeneratePlanTest.php`

**Szacowany czas**: 5-6h

#### 5.2. Status transitions
**User Stories**: US-019, US-022
**PRD**: Sekcja 3.5, 3.8

**Status**: ⚠️ Częściowo (statusy istnieją: draft, planned, completed)

**Zadania**:
- [ ] Weryfikacja flow statusów:
  - "Zapisz jako szkic" → status = 'draft'
  - "Generuj plan" → status = 'planned'
  - Auto-complete po dacie wyjazdu → status = 'completed'
  - Ręczne oznaczenie jako zrealizowane → status = 'completed'
- [ ] Sprawdzenie czy statuses są używane poprawnie:
  - Plik: `app/Models/TravelPlan.php` ✅ (istnieje)
  - Enum lub string validation w migration
- [ ] UI - możliwość ręcznego oznaczenia jako "Zrealizowane":
  - Przycisk w widoku planu lub w actions dropdown
  - Tylko dla planów z przeszłą datą wyjazdu lub statusem 'planned'
- [ ] Cron job - automatyczne przejście do 'completed':
  - Zadanie: znaleźć plany z `status = 'planned'` i `end_date < NOW()`
  - Zmienić status na 'completed'
  - Komenda: `app/Console/Commands/AutoCompletePastTrips.php`
  - Scheduler: codziennie o 00:00
- [ ] Event: `TravelPlanCompleted` - do wysłania emaila z prośbą o feedback?
- [ ] Testing:
  - [ ] Transitions między statusami
  - [ ] Cron job auto-complete
  - [ ] UI ręcznego oznaczenia

**Pliki do sprawdzenia/utworzenia**:
- `app/Models/TravelPlan.php` - sprawdzić enums/constants dla statusów
- `database/migrations/*_create_travel_plans_table.php` - sprawdzić status field
- `app/Console/Commands/AutoCompletePastTrips.php` (nowy)
- `app/Console/Kernel.php` - zarejestrować scheduled task
- `app/Events/TravelPlanCompleted.php` (nowy - opcjonalnie)
- Test: `tests/Feature/Plans/StatusTransitionsTest.php`
- Test: `tests/Feature/Console/AutoCompletePastTripsTest.php`

**Szacowany czas**: 4-5h

#### 5.3. Plan deletion improvements
**User Stories**: US-021
**PRD**: Sekcja 3.8

**Status**: ✅ Prawdopodobnie zaimplementowane, wymaga weryfikacji

**Zadania weryfikacyjne**:
- [ ] Sprawdzenie czy przycisk "Usuń plan" istnieje:
  - Plik: `app/Livewire/Plans/Show.php` lub `PlanActions.php`
- [ ] Modal z potwierdzeniem przed usunięciem
- [ ] Kaskadowe usuwanie powiązanych danych:
  - plan_days (przez relację onDelete cascade w migration)
  - plan_points (przez relację onDelete cascade)
  - feedback (przez relację onDelete cascade lub set null)
  - AI generation records (set null na travel_plan_id)
  - PDF exports (set null lub delete)
- [ ] Redirect do dashboard po usunięciu
- [ ] Flash message: "Plan został usunięty"
- [ ] Testing:
  - [ ] Usunięcie planu
  - [ ] Kaskadowe usuwanie
  - [ ] Niemożność odzyskania usuniętego planu

**Pliki do sprawdzenia**:
- `app/Actions/TravelPlan/DeleteTravelPlanAction.php` ✅ (istnieje)
- `app/Livewire/Plans/Show.php` lub `PlanActions.php`
- Migrations - sprawdzić `onDelete('cascade')` w foreign keys
- Test: `tests/Feature/Plans/DeletePlanTest.php`

**Szacowany czas**: 2-3h (głównie testing)

---

### 6. SYSTEM EMAIL

#### 6.1. Welcome email
**User Stories**: US-031
**PRD**: Sekcja 3.12

**Status**: ❌ Nie zaimplementowany

**Zadania**:
- [ ] Utworzenie Mailable class:
  - Plik: `app/Mail/WelcomeEmail.php`
  - Temat: "Witaj w VibeTravels! Zacznij planować swoją przygodę"
- [ ] Szablon email:
  - Plik: `resources/views/emails/welcome.blade.php`
  - Treść:
    - Powitanie z imieniem/nickiem
    - Krótkie intro VibeTravels
    - Podstawowe wskazówki (tips):
      - Jak utworzyć plan
      - Jak wykorzystać limity (10/miesiąc)
      - Link do dashboard
    - CTA button: "Stwórz swój pierwszy plan"
  - Design: prosty, czytelny (zgodnie z PRD 4.2)
- [ ] Wysyłka emaila:
  - Event: `OnboardingCompleted` lub bezpośrednio w `CompleteOnboardingAction`
  - Queue: `queue('emails')` - asynchronicznie
- [ ] Testing:
  - [ ] Test wysłania emaila po ukończeniu onboardingu
  - [ ] Test treści emaila (Markdown/HTML)
  - [ ] Test w MailHog (development)

**Pliki do utworzenia**:
- `app/Mail/WelcomeEmail.php`
- `resources/views/emails/welcome.blade.php`
- `app/Events/Onboarding/OnboardingCompleted.php` (jeśli nie istnieje)
- `app/Listeners/Onboarding/SendWelcomeEmail.php`
- `app/Providers/EventServiceProvider.php` - zarejestrować listener
- Test: `tests/Feature/Mail/WelcomeEmailTest.php`

**Szacowany czas**: 3-4h

#### 6.2. Limit notification emails (8/10, 10/10)
**User Stories**: US-032, US-033
**PRD**: Sekcja 3.12

**Status**: ❌ Nie zaimplementowane

**Zadania**:

**Email 1: Limit 8/10 (approaching limit)**
- [ ] Mailable: `app/Mail/LimitApproachingEmail.php`
- [ ] Temat: "Pozostały Ci 2 generowania w tym miesiącu"
- [ ] Szablon: `resources/views/emails/limit-approaching.blade.php`
- [ ] Treść:
  - Info o wykorzystanych generowaniach (8/10)
  - Przypomnienie o dacie resetu (1. następnego miesiąca)
  - Zachęta do stworzenia kolejnych planów
  - CTA: "Stwórz plan"
- [ ] Wysyłka: Po 8. generowaniu (w `TravelPlanService` lub Event Listener)
- [ ] Tracking: `email_logs` table - upewnić się że wysyłamy tylko raz w miesiącu

**Email 2: Limit 10/10 (limit exceeded)**
- [ ] Mailable: `app/Mail/LimitExceededEmail.php`
- [ ] Temat: "Wykorzystałeś limit generowań w tym miesiącu"
- [ ] Szablon: `resources/views/emails/limit-exceeded.blade.php`
- [ ] Treść:
  - Info o wykorzystaniu limitu
  - Data odnowienia (1. następnego miesiąca, obliczona dynamicznie)
  - Opcjonalnie: link do waitlist na premium (placeholder - bez płatności w MVP)
  - CTA: może "Przeglądaj swoje plany"
- [ ] Wysyłka: Po 10. generowaniu
- [ ] Tracking: tylko raz w miesiącu

**Wspólne zadania**:
- [ ] Service method: `LimitService::checkAndSendLimitEmail(User $user)`
- [ ] Model `EmailLog`:
  - Pola: user_id, email_type, sent_at, month_year
  - Sprawdzanie czy email już wysłany w tym miesiącu
- [ ] Migration: jeśli tabela email_logs nie istnieje (✅ już istnieje migration)
- [ ] Testing:
  - [ ] Email wysyłany po 8. generowaniu
  - [ ] Email wysyłany po 10. generowaniu
  - [ ] Brak duplikatów w tym samym miesiącu
  - [ ] Treść emaili

**Pliki do utworzenia/modyfikacji**:
- `app/Mail/LimitApproachingEmail.php`
- `app/Mail/LimitExceededEmail.php`
- `resources/views/emails/limit-approaching.blade.php`
- `resources/views/emails/limit-exceeded.blade.php`
- `app/Models/EmailLog.php` (może już istnieć)
- `app/Services/LimitService.php` - dodać metodę wysyłki emaili
- Test: `tests/Feature/Mail/LimitNotificationEmailsTest.php`

**Szacowany czas**: 5-6h

#### 6.3. Trip reminder email (opcjonalnie)
**User Stories**: US-034
**PRD**: Sekcja 3.12

**Status**: ❌ Nie zaimplementowany (nice-to-have)

**Zadania**:
- [ ] Mailable: `app/Mail/TripReminderEmail.php`
- [ ] Temat: "Twoja wycieczka do [Destynacja] już za 3 dni!"
- [ ] Szablon: `resources/views/emails/trip-reminder.blade.php`
- [ ] Treść:
  - Tytuł planu
  - Destynacja
  - Daty (od-do)
  - Link do pełnego planu w aplikacji
  - Zachęta do pobrania PDF
  - CTA: "Zobacz plan" lub "Pobierz PDF"
- [ ] Cron job - wysyłka emaili:
  - Komenda: `app/Console/Commands/SendTripReminders.php`
  - Logika: Znajdź plany z `start_date = NOW() + 3 days` i `status = 'planned'`
  - Scheduler: codziennie o 09:00
- [ ] Tracking: `email_logs` - jeden email na plan
- [ ] Testing:
  - [ ] Email wysyłany 3 dni przed wycieczką
  - [ ] Tylko dla planów 'planned'
  - [ ] Brak duplikatów

**Priorytet**: NICE-TO-HAVE - można odłożyć jeśli brak czasu

**Pliki do utworzenia**:
- `app/Mail/TripReminderEmail.php`
- `resources/views/emails/trip-reminder.blade.php`
- `app/Console/Commands/SendTripReminders.php`
- `app/Console/Kernel.php` - zarejestrować scheduled task
- Test: `tests/Feature/Mail/TripReminderEmailTest.php`

**Szacowany czas**: 4-5h

#### 6.4. Email templates improvements
**Wszystkie User Stories email**: US-029 do US-034

**Status**: ⚠️ Wymaga poprawy

**Zadania**:
- [ ] Layout email ogólny:
  - Plik: `resources/views/emails/layouts/app.blade.php`
  - Header z logo VibeTravels
  - Footer z linkami: Privacy Policy, Terms, Unsubscribe (opcjonalnie w MVP)
  - Responsive design (mobile-friendly)
  - Prosty design (zgodnie z PRD 4.2)
- [ ] Consistency wszystkich emaili:
  - Jednolity styl (kolory, fonty, spacing)
  - Polski język
  - Czytelna struktura
- [ ] Testing wszystkich emaili w MailHog
- [ ] Konfiguracja email service:
  - Development: MailHog (✅ już skonfigurowane)
  - Production: Mailgun (zgodnie z CLAUDE.md)

**Pliki do utworzenia/modyfikacji**:
- `resources/views/emails/layouts/app.blade.php`
- Wszystkie szablony emaili (welcome, limit, verification, trip reminder)
- `config/mail.php` - sprawdzić konfigurację

**Szacowany czas**: 3-4h

---

### 7. ANALYTICS I METRYKI

#### 7.1. User events tracking
**User Stories**: US-035 do US-040
**PRD**: Sekcja 3.14, 5.8

**Status**: ❌ Nie zaimplementowane (tabela istnieje, tracking brak)

**Zadania**:
- [ ] Model `UserEvent`:
  - Plik: `app/Models/UserEvent.php` (może już istnieć)
  - Pola: user_id, event_type, event_data (JSON), created_at
- [ ] Eventy do trackowania:
  - **Onboarding**:
    - [ ] `onboarding_started` (po rejestracji)
    - [ ] `onboarding_step_completed` (każdy krok + step number)
    - [ ] `onboarding_completed`
  - **Plans**:
    - [ ] `plan_created` (draft)
    - [ ] `plan_generated` (AI generation)
    - [ ] `plan_regenerated`
    - [ ] `plan_deleted`
    - [ ] `plan_exported_pdf`
    - [ ] `plan_viewed`
  - **Auth**:
    - [ ] `user_registered`
    - [ ] `user_logged_in`
    - [ ] `email_verified`
  - **Preferences**:
    - [ ] `preferences_updated`
  - **Feedback**:
    - [ ] `feedback_submitted`
- [ ] Service: `app/Services/EventTrackingService.php`
  - Metoda: `track(User $user, string $eventType, array $data = [])`
  - Asynchroniczny zapis (Queue) dla performance
- [ ] Integration w existing code:
  - [ ] Onboarding wizard - dodać tracking
  - [ ] TravelPlanService - dodać tracking
  - [ ] Auth controllers - dodać tracking
  - [ ] Feedback form - dodać tracking
- [ ] Testing:
  - [ ] Test zapisu eventów
  - [ ] Test struktury JSON data

**Pliki do utworzenia/modyfikacji**:
- `app/Models/UserEvent.php`
- `app/Services/EventTrackingService.php`
- `app/Events/*` (opcjonalnie - można używać bezpośrednio service)
- Wszystkie miejsca gdzie trzeba dodać tracking
- Test: `tests/Feature/Analytics/EventTrackingTest.php`

**Szacowany czas**: 6-8h

#### 7.2. Analytics dashboard (podstawowy)
**User Stories**: US-035 do US-040
**PRD**: Sekcja 3.14, 5.8

**Status**: ❌ Nie zaimplementowane

**Zadania**:
- [ ] Route: `/admin/analytics` (chroniony - tylko dla adminów)
- [ ] Middleware: `isAdmin()` check
- [ ] Controller lub Livewire component:
  - Plik: `app/Livewire/Admin/Analytics.php` lub `app/Http/Controllers/Admin/AnalyticsController.php`
- [ ] View: `resources/views/admin/analytics.blade.php`
- [ ] Metryki do wyświetlenia:

  **Onboarding metrics**:
  - [ ] Completion rate (% użytkowników z completed onboarding)
  - [ ] Average time to complete
  - [ ] Drop-off rate per step

  **User engagement**:
  - [ ] Total users
  - [ ] Monthly Active Users (MAU)
  - [ ] 30-day retention rate
  - [ ] % użytkowników z wypełnionymi preferencjami

  **Plans metrics**:
  - [ ] Liczba planów per użytkownik (średnia)
  - [ ] % użytkowników z ≥3 planami
  - [ ] Rozkład statusów planów (draft/planned/completed)

  **AI metrics**:
  - [ ] Generowania dziennie/miesięcznie
  - [ ] Średni koszt na plan
  - [ ] Suma kosztów AI
  - [ ] Suma zużytych tokenów
  - [ ] Success rate (% bez błędów)

  **Feedback metrics**:
  - [ ] Satisfaction rate (% pozytywnych)
  - [ ] Breakdown negatywnych feedbacków (za mało szczegółów, etc.)

  **Export metrics**:
  - [ ] Export rate (% planów eksportowanych)
  - [ ] Średnia liczba eksportów per plan
  - [ ] Łączna liczba eksportów

- [ ] Service: `app/Services/AnalyticsService.php`
  - Metody obliczające każdą metrykę
  - Cache'owanie wyników (1h TTL) dla performance
- [ ] UI components:
  - [ ] Stat cards (Tailwind/DaisyUI)
  - [ ] Charts (Chart.js lub ApexCharts) - opcjonalnie
  - [ ] Tables z danymi
  - [ ] Date range picker (filtry czasowe)
- [ ] Testing:
  - [ ] Test każdej metryki
  - [ ] Test uprawnień (tylko admin)

**Priorytet**: WAŻNE - podstawowe metryki, rozbudowane można odłożyć

**Pliki do utworzenia**:
- `app/Livewire/Admin/Analytics.php` lub `app/Http/Controllers/Admin/AnalyticsController.php`
- `app/Services/AnalyticsService.php`
- `resources/views/admin/analytics.blade.php`
- `app/Http/Middleware/EnsureUserIsAdmin.php` (jeśli nie istnieje)
- Test: `tests/Feature/Admin/AnalyticsTest.php`

**Szacowany czas**: 10-15h (duży zakres, można fazować)

#### 7.3. Metrics calculation (queries)
**User Stories**: US-035 do US-040
**PRD**: Sekcja 6 (Metryki sukcesu)

**Status**: ❌ Nie zaimplementowane

**Zadania - implementacja queries w AnalyticsService**:

**1. Onboarding completion rate** (US-035):
```php
public function getOnboardingCompletionRate(int $days = 30): float
{
    $total = User::where('created_at', '>=', now()->subDays($days))->count();
    $completed = User::where('created_at', '>=', now()->subDays($days))
        ->where('onboarding_completed', true)
        ->count();

    return $total > 0 ? ($completed / $total) * 100 : 0;
}
```

**2. Plans per user** (US-036):
```php
public function getAveragePlansPerUser(): float
{
    return TravelPlan::whereHas('user')
        ->selectRaw('COUNT(*) / COUNT(DISTINCT user_id) as avg_plans')
        ->value('avg_plans') ?? 0;
}

public function getUsersWithMinPlans(int $minPlans = 3, int $months = 12): int
{
    return User::whereHas('travelPlans', function($q) use ($months) {
        $q->where('status', '!=', 'draft')
          ->where('created_at', '>=', now()->subMonths($months));
    }, '>=', $minPlans)->count();
}
```

**3. Satisfaction rate** (US-037):
```php
public function getSatisfactionRate(): array
{
    $total = TravelPlanFeedback::count();
    $positive = TravelPlanFeedback::where('satisfied', true)->count();

    $issues = TravelPlanFeedback::where('satisfied', false)
        ->selectRaw('JSON_EXTRACT(issues, "$") as issue_list')
        ->get()
        ->flatMap(fn($f) => json_decode($f->issue_list, true))
        ->countBy()
        ->toArray();

    return [
        'rate' => $total > 0 ? ($positive / $total) * 100 : 0,
        'total_feedbacks' => $total,
        'positive_count' => $positive,
        'negative_count' => $total - $positive,
        'issues_breakdown' => $issues,
    ];
}
```

**4. Export rate** (US-038):
```php
public function getExportRate(): array
{
    $totalPlans = TravelPlan::where('status', '!=', 'draft')->count();
    $exportedPlans = TravelPlan::where('pdf_exports_count', '>', 0)->count();

    return [
        'rate' => $totalPlans > 0 ? ($exportedPlans / $totalPlans) * 100 : 0,
        'total_plans' => $totalPlans,
        'exported_plans' => $exportedPlans,
        'total_exports' => PdfExport::count(),
        'avg_exports_per_plan' => $exportedPlans > 0
            ? PdfExport::count() / $exportedPlans
            : 0,
    ];
}
```

**5. AI costs** (US-039):
```php
public function getAICosts(string $period = 'month'): array
{
    $query = AIGeneration::where('status', 'completed');

    if ($period === 'month') {
        $query->whereMonth('created_at', now()->month);
    } elseif ($period === 'day') {
        $query->whereDate('created_at', now()->toDateString());
    }

    $stats = $query->selectRaw('
        SUM(tokens_used) as total_tokens,
        SUM(cost_usd) as total_cost,
        AVG(cost_usd) as avg_cost,
        COUNT(*) as total_generations
    ')->first();

    return [
        'total_tokens' => $stats->total_tokens ?? 0,
        'total_cost' => $stats->total_cost ?? 0,
        'avg_cost_per_plan' => $stats->avg_cost ?? 0,
        'total_generations' => $stats->total_generations ?? 0,
    ];
}
```

**6. MAU & Retention** (US-040):
```php
public function getMonthlyActiveUsers(): int
{
    return User::where('last_activity_at', '>=', now()->subDays(30))->count();
}

public function getRetentionRate(int $days = 30): float
{
    $cohort = User::where('created_at', '>=', now()->subDays($days + 5))
        ->where('created_at', '<=', now()->subDays($days - 5))
        ->pluck('id');

    $returned = User::whereIn('id', $cohort)
        ->where('last_activity_at', '>=', now()->subDays(5))
        ->count();

    return $cohort->count() > 0 ? ($returned / $cohort->count()) * 100 : 0;
}
```

- [ ] Implementacja wszystkich powyższych metod w `AnalyticsService`
- [ ] Cache'owanie wyników (Redis, 1h TTL)
- [ ] Testing każdej metryki z fixture data
- [ ] Dokumentacja metod (PHPDoc)

**Pliki do modyfikacji**:
- `app/Services/AnalyticsService.php`
- Test: `tests/Unit/Services/AnalyticsServiceTest.php`

**Szacowany czas**: 6-8h

---

### 8. AUTOMATYZACJA (CRON JOBS)

#### 8.1. Monthly limit reset
**PRD**: Sekcja 3.6
**User Stories**: Pośrednio US-022, US-023

**Status**: ❌ Nie zaimplementowany

**Zadania**:
- [ ] Komenda: `app/Console/Commands/ResetMonthlyAILimits.php`
- [ ] Logika:
  ```php
  // Reset counter for all users on the 1st of month
  User::query()->update([
      'ai_generations_count_current_month' => 0,
      'ai_generations_reset_at' => now(),
  ]);

  // Log activity
  Log::info('Monthly AI limits reset completed', [
      'users_affected' => $usersCount,
      'reset_date' => now()->toDateString(),
  ]);
  ```
- [ ] Scheduler: `app/Console/Kernel.php`
  ```php
  $schedule->command('limits:reset-monthly')
      ->monthlyOn(1, '00:00')
      ->timezone('Europe/Warsaw');
  ```
- [ ] Email notification (opcjonalnie):
  - Wysłanie emaila "Twoje limity zostały odnowione" do aktywnych użytkowników
- [ ] Testing:
  - [ ] Unit test komendy
  - [ ] Test czy counters są resetowane
  - [ ] Test schedulera (manual trigger)

**Pliki do utworzenia**:
- `app/Console/Commands/ResetMonthlyAILimits.php`
- `app/Console/Kernel.php` - dodać scheduled task
- Test: `tests/Feature/Console/ResetMonthlyAILimitsTest.php`

**Szacowany czas**: 3-4h

#### 8.2. Auto-complete past trips
**PRD**: Sekcja 3.8, 5.2 (tego dokumentu)
**User Stories**: US-019

**Status**: ❌ Nie zaimplementowany

**Zadania**:
- [ ] Komenda: `app/Console/Commands/AutoCompletePastTrips.php`
- [ ] Logika:
  ```php
  // Find plans with status 'planned' and end_date in the past
  $plans = TravelPlan::where('status', 'planned')
      ->where('end_date', '<', now()->toDateString())
      ->get();

  foreach ($plans as $plan) {
      $plan->update(['status' => 'completed']);

      // Optionally dispatch event
      event(new TravelPlanCompleted($plan));
  }

  Log::info('Auto-completed past trips', [
      'count' => $plans->count(),
      'date' => now()->toDateString(),
  ]);
  ```
- [ ] Scheduler: codziennie o 00:00
  ```php
  $schedule->command('plans:auto-complete-past')
      ->daily()
      ->timezone('Europe/Warsaw');
  ```
- [ ] Event: `TravelPlanCompleted` (opcjonalnie)
  - Może wysłać email z prośbą o feedback po wycieczce
- [ ] Testing:
  - [ ] Test znajdujące przeszłe plany
  - [ ] Test zmiany statusu
  - [ ] Test event dispatch

**Pliki do utworzenia**:
- `app/Console/Commands/AutoCompletePastTrips.php`
- `app/Events/TravelPlanCompleted.php` (opcjonalnie)
- `app/Console/Kernel.php` - dodać scheduled task
- Test: `tests/Feature/Console/AutoCompletePastTripsTest.php`

**Szacowany czas**: 3-4h

#### 8.3. Email reminders scheduler
**PRD**: Sekcja 3.12
**User Stories**: US-034 (opcjonalnie)

**Status**: ❌ Nie zaimplementowany (nice-to-have)

**Zadania**:
- [ ] Komenda: `app/Console/Commands/SendTripReminders.php`
- [ ] Logika:
  ```php
  // Find plans starting in exactly 3 days
  $plans = TravelPlan::where('status', 'planned')
      ->whereDate('start_date', now()->addDays(3)->toDateString())
      ->with('user')
      ->get();

  foreach ($plans as $plan) {
      // Check if reminder email already sent
      $alreadySent = EmailLog::where('user_id', $plan->user_id)
          ->where('email_type', 'trip_reminder')
          ->where('travel_plan_id', $plan->id)
          ->exists();

      if (!$alreadySent) {
          Mail::to($plan->user)->queue(new TripReminderEmail($plan));

          EmailLog::create([
              'user_id' => $plan->user_id,
              'email_type' => 'trip_reminder',
              'travel_plan_id' => $plan->id,
              'sent_at' => now(),
          ]);
      }
  }
  ```
- [ ] Scheduler: codziennie o 09:00
  ```php
  $schedule->command('emails:send-trip-reminders')
      ->dailyAt('09:00')
      ->timezone('Europe/Warsaw');
  ```
- [ ] Testing:
  - [ ] Test wysyłki emaili
  - [ ] Test deduplication (brak duplikatów)
  - [ ] Test tylko dla planów za 3 dni

**Priorytet**: NICE-TO-HAVE

**Pliki do utworzenia**:
- `app/Console/Commands/SendTripReminders.php`
- `app/Console/Kernel.php` - dodać scheduled task
- Test: `tests/Feature/Console/SendTripRemindersTest.php`

**Szacowany czas**: 3-4h

---

### 9. ERROR HANDLING I MONITORING

#### 9.1. AI generation error handling
**User Stories**: US-024
**PRD**: Sekcja 3.6

**Status**: ⚠️ Częściowo (podstawowe exception classes istnieją)

**Zadania**:
- [ ] Sprawdzenie istniejących exception classes:
  - `app/Exceptions/OpenAI*.php` ✅ (istnieją)
- [ ] Comprehensive error handling w `TravelPlanService`:
  - [ ] Timeout (max 60s wait)
  - [ ] API errors (4xx, 5xx)
  - [ ] Niekompletna odpowiedź (validation failed)
  - [ ] Rate limit exceeded (429)
  - [ ] Network errors
- [ ] User-facing error messages:
  - Timeout: "Generowanie trwa zbyt długo. Spróbuj ponownie."
  - API error: "Wystąpił problem z generowaniem planu. Spróbuj ponownie."
  - Niekompletna odpowiedź: "Nie udało się wygenerować pełnego planu. Spróbuj ponownie."
  - Rate limit: "Serwis AI jest przeciążony. Spróbuj za chwilę."
- [ ] Rollback logic:
  - [ ] Rollback AI generation count (`LimitService::rollbackGeneration()`) ✅ (już istnieje)
  - [ ] Set AI generation status to 'failed'
  - [ ] Nie zmieniać statusu planu z 'draft' na 'planned' przy błędzie
- [ ] Retry logic (opcjonalnie):
  - Max 2 retry dla transient errors (timeout, 5xx)
  - Exponential backoff
- [ ] Logging:
  - Log każdego błędu z contextem (user_id, plan_id, error details)
  - Oddzielny log channel: `logs/ai-errors.log`
- [ ] Testing:
  - [ ] Mock different error scenarios
  - [ ] Test rollback
  - [ ] Test user messages
  - [ ] Test logging

**Pliki do modyfikacji**:
- `app/Services/TravelPlanService.php`
- `app/Services/OpenAI/OpenAIService.php`
- `app/Exceptions/*` - użycie istniejących exception classes
- `config/logging.php` - dodać channel 'ai-errors'
- Test: `tests/Feature/AI/ErrorHandlingTest.php`

**Szacowany czas**: 5-6h

#### 9.2. Error logging improvements
**PRD**: Sekcja 3.14

**Status**: ⚠️ Podstawowy Laravel logging, wymaga poprawy

**Zadania**:
- [ ] Konfiguracja log channels:
  - `config/logging.php`:
    - Channel 'ai-errors' (już w 9.1)
    - Channel 'user-activity' (user events)
    - Channel 'email' (email sending issues)
- [ ] Structured logging (JSON format):
  ```php
  Log::channel('ai-errors')->error('AI generation failed', [
      'user_id' => $userId,
      'plan_id' => $planId,
      'error_type' => get_class($exception),
      'error_message' => $exception->getMessage(),
      'trace' => $exception->getTraceAsString(),
  ]);
  ```
- [ ] Error monitoring (opcjonalnie - poza MVP):
  - Integracja Sentry lub podobne (nice-to-have)
  - Slack notifications dla critical errors
- [ ] Log rotation:
  - Daily rotation
  - Keep last 14 days
- [ ] Testing:
  - [ ] Test każdego log channel
  - [ ] Test struktury JSON logs

**Pliki do modyfikacji**:
- `config/logging.php`
- Wszystkie serwisy - dodać structured logging
- `.env` - log level configuration

**Szacowany czas**: 2-3h

#### 9.3. User-facing error messages
**User Stories**: Wszystkie (UX)
**PRD**: Pośrednio sekcja 3

**Status**: ⚠️ Wymaga standaryzacji

**Zadania**:
- [ ] Standaryzacja flash messages:
  - Success: zielony, ikona check
  - Error: czerwony, ikona X
  - Warning: żółty, ikona !
  - Info: niebieski, ikona i
- [ ] Komponenty Blade dla messages:
  - `resources/views/components/alert.blade.php`
  - Props: type, message, dismissible
- [ ] Polski język dla wszystkich komunikatów błędów:
  - Validation errors
  - Auth errors
  - Business logic errors
- [ ] Graceful degradation:
  - Fallback messages gdy szczegółowy błąd nie jest dostępny
- [ ] Testing:
  - [ ] Visual testing różnych typów alertów
  - [ ] Test dismissible functionality

**Pliki do utworzenia/modyfikacji**:
- `resources/views/components/alert.blade.php`
- `lang/pl/errors.php` (custom error messages)
- Wszystkie controllers/components - użycie standardowych messages

**Szacowany czas**: 3-4h

---

### 10. TESTY I JAKOŚĆ KODU

#### 10.1. Unit tests dla nowych funkcji
**PRD**: Sekcja 6.5 (launch criteria)

**Status**: ⚠️ Częściowo (niektóre testy istnieją)

**Zadania - pokrycie testami**:

**Services**:
- [ ] `LimitService` ✅ (może już istnieć - sprawdzić)
  - [ ] getGenerationCount()
  - [ ] getRemainingGenerations()
  - [ ] canGenerate()
  - [ ] incrementGenerationCount()
  - [ ] rollbackGeneration()
  - [ ] getLimitInfo()

- [ ] `AnalyticsService`
  - [ ] Wszystkie metody metryk (7.3)

- [ ] `EventTrackingService`
  - [ ] track() method
  - [ ] Queue dispatching

- [ ] `TravelPlanService`
  - [ ] regeneratePlan()
  - [ ] Error handling scenarios

**Models**:
- [ ] `User`
  - [ ] hasCompletedOnboarding()
  - [ ] getRemainingAiGenerations()
  - [ ] needsOnboarding()

- [ ] `TravelPlan`
  - [ ] Status transitions
  - [ ] Relationships

**Actions**:
- [ ] `DeleteUserAccountAction` (nowy)
- [ ] `CompleteOnboardingAction` ✅ (może już istnieć)

**Cel**: Minimum 70% code coverage dla business logic

**Szacowany czas**: 8-10h

#### 10.2. Feature tests dla user stories
**User Stories**: Wszystkie
**PRD**: Sekcja 6.5

**Status**: ⚠️ Częściowo

**Zadania - testy end-to-end**:

**Authentication flow** (US-001 do US-010):
- [ ] Test rejestracji email+hasło
- [ ] Test rejestracji Google OAuth
- [ ] Test email verification flow
- [ ] Test logowania
- [ ] Test wylogowania
- [ ] Test usunięcia konta

**Onboarding flow** (US-006 do US-008):
- [ ] Test pełnego onboardingu (3 kroki)
- [ ] Test walidacji na każdym kroku
- [ ] Test zapisywania danych
- [ ] Test completion

**Plans management** (US-018 do US-026):
- [ ] Test tworzenia szkicu
- [ ] Test generowania planu z AI (mock)
- [ ] Test regeneracji planu
- [ ] Test usuwania planu
- [ ] Test filtrowania/sortowania
- [ ] Test status transitions

**Feedback & Export** (US-027, US-028):
- [ ] Test submit feedback
- [ ] Test PDF export

**Email notifications** (US-029 do US-034):
- [ ] Test wysyłki każdego typu emaila
- [ ] Test rate limiting resend verification

**Dashboard** (US-014 do US-017):
- [ ] Test wyświetlania planów
- [ ] Test filtrowania
- [ ] Test limit counter

**Cel**: Minimum 1 test per User Story (40+ testów)

**Szacowany czas**: 12-15h

#### 10.3. Code quality improvements
**PRD**: Pośrednio sekcja 6.5

**Status**: ⚠️ PHPStan i Pint skonfigurowane, wymaga przejścia przez kod

**Zadania**:
- [ ] PHPStan level 8 compliance:
  - `make phpstan` - fix wszystkie errors
  - Dodanie typehintów gdzie brakuje
  - Fixing potential bugs znalezionych przez PHPStan

- [ ] Laravel Pint (code style):
  - `make cs-fix` - auto-fix PSR-12 violations
  - `make cs-check` - sprawdzenie compliance

- [ ] PHPCS (PSR-12):
  - `docker compose exec app ./vendor/bin/phpcs`
  - `docker compose exec app ./vendor/bin/phpcbf` - auto-fix

- [ ] Code refactoring:
  - [ ] Extract magic numbers to constants
  - [ ] Extract long methods to smaller ones
  - [ ] Remove code duplication (DRY)
  - [ ] Improve naming (meaningful variable/method names)

- [ ] Documentation:
  - [ ] PHPDoc dla wszystkich public methods
  - [ ] README updates jeśli potrzebne
  - [ ] Inline comments dla złożonej logiki

**Cel**: Zero PHPStan errors, 100% PSR-12 compliance

**Szacowany czas**: 6-8h

---

### 11. DOKUMENTACJA I DEPLOYMENT

#### 11.1. API documentation (opcjonalnie)
**PRD**: Nie wymienione bezpośrednio

**Status**: ❌ Nie zaimplementowane

**Zadania**:
- [ ] OpenAPI/Swagger spec dla API endpoints:
  - `routes/api.php` - dokumentacja endpointów
- [ ] Narzędzie: Scramble Laravel lub L5-Swagger
- [ ] Dokumentacja:
  - Request/Response schemas
  - Authentication (Sanctum)
  - Error codes
- [ ] UI: Swagger UI dostępne pod `/api/documentation`
- [ ] Testing: Swagger spec validation

**Priorytet**: NICE-TO-HAVE (jeśli API będzie używane zewnętrznie)

**Pliki do utworzenia**:
- Konfiguracja Scramble/L5-Swagger
- PHPDoc annotations dla API controllers

**Szacowany czas**: 4-6h (jeśli potrzebne)

#### 11.2. Privacy Policy & Terms of Service
**PRD**: Sekcja 3.13
**User Stories**: Pośrednio US-010 (GDPR)

**Status**: ❌ Nie zaimplementowane (wymaga prawnika - poza scope developmentu)

**Zadania**:
- [ ] Utworzenie stron:
  - `/privacy-policy`
  - `/terms-of-service`
- [ ] Views:
  - `resources/views/legal/privacy-policy.blade.php`
  - `resources/views/legal/terms-of-service.blade.php`
- [ ] Treść (placeholder w MVP):
  - Podstawowy template Privacy Policy (GDPR compliance)
  - Podstawowy template ToS
  - **UWAGA**: Wymaga konsultacji z prawnikiem przed production!
- [ ] Linki w footer aplikacji i emailach
- [ ] Checkbox akceptacji ToS podczas rejestracji (opcjonalnie)

**Priorytet**: KRYTYCZNE przed public launch, ale content może być placeholder

**Pliki do utworzenia**:
- `resources/views/legal/privacy-policy.blade.php`
- `resources/views/legal/terms-of-service.blade.php`
- `routes/web.php` - dodać routes
- `resources/views/components/footer.blade.php` - dodać linki

**Szacowany czas**: 2-3h (bez content prawnego)

#### 11.3. Production deployment checklist
**PRD**: Sekcja 6.5 (launch criteria)

**Status**: ❌ Do przygotowania

**Zadania - Pre-launch checklist**:

**Environment & Config**:
- [ ] `.env.production` skonfigurowany:
  - [ ] `APP_ENV=production`
  - [ ] `APP_DEBUG=false`
  - [ ] `APP_URL` - właściwy domain
  - [ ] `DB_*` - production database
  - [ ] `MAIL_*` - Mailgun credentials
  - [ ] `OPENAI_API_KEY` - production key
  - [ ] `SESSION_DRIVER=redis`
  - [ ] `CACHE_DRIVER=redis`
  - [ ] `QUEUE_CONNECTION=redis`
- [ ] HTTPS wymuszony
- [ ] Secure cookies enabled
- [ ] CORS properly configured

**Database**:
- [ ] Migrations run: `php artisan migrate --force`
- [ ] Seeders (jeśli potrzebne): basic data
- [ ] Backup strategy skonfigurowany
- [ ] Database indexes zoptymalizowane

**Performance**:
- [ ] Config cached: `php artisan config:cache`
- [ ] Routes cached: `php artisan route:cache`
- [ ] Views cached: `php artisan view:cache`
- [ ] Assets compiled: `npm run build`
- [ ] Redis configured i running
- [ ] Queue worker running: `php artisan queue:work --daemon`

**Scheduler**:
- [ ] Cron job skonfigurowany:
  ```
  * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
  ```
- [ ] Verify all scheduled tasks working

**Monitoring & Logging**:
- [ ] Log rotation enabled
- [ ] Error monitoring (Sentry - opcjonalnie)
- [ ] Uptime monitoring
- [ ] Application performance monitoring

**Security**:
- [ ] Rate limiting enabled
- [ ] CSRF protection active
- [ ] SQL injection protection (używamy Eloquent - OK)
- [ ] XSS protection (Blade escaping - OK)
- [ ] Security headers configured
- [ ] SSL certificate zainstalowany i ważny

**Testing**:
- [ ] All tests passing: `make test`
- [ ] PHPStan clean: `make phpstan`
- [ ] Code style clean: `make cs-check`
- [ ] Manual testing critical flows:
  - [ ] Rejestracja + email verification
  - [ ] Onboarding flow
  - [ ] Tworzenie planu
  - [ ] Generowanie AI (z real API)
  - [ ] Eksport PDF
  - [ ] Feedback submit
  - [ ] Email sending (wszystkie typy)

**Metrics baseline**:
- [ ] Onboarding completion rate >70% (w beta testing)
- [ ] AI generation success rate >90%
- [ ] Średni czas generowania <45s
- [ ] Plan satisfaction rate >60% (w beta)
- [ ] Zero critical security vulnerabilities
- [ ] Email delivery rate >95%

**Documentation**:
- [ ] README updated
- [ ] DEPLOYMENT.md aktualne
- [ ] Changelog updated
- [ ] Privacy Policy & ToS published

**Pliki do utworzenia**:
- `DEPLOYMENT.md` - szczegółowa instrukcja deployment
- `.env.production.example` - template dla production env
- `deployment-checklist.md` - pełna checklist

**Szacowany czas**: 6-8h (przygotowanie + weryfikacja)

---

## PODSUMOWANIE I HARMONOGRAM

### Szacowany całkowity czas implementacji

| Sekcja | Zadania | Szacowany czas | Priorytet |
|--------|---------|----------------|-----------|
| **1. Analiza** | Weryfikacja i identyfikacja luk | 4-6h | KRYTYCZNE |
| **2. Auth & Security** | Email verification, rate limiting, HTTPS, account deletion | 12-17h | KRYTYCZNE |
| **3. Onboarding** | Weryfikacja flow, edycja preferencji, tracking | 9-12h | WAŻNE |
| **4. Dashboard & UI** | Limit counter, filtering, sorting, język PL | 15-22h | KRYTYCZNE |
| **5. Plans Management** | Regeneracja, status transitions, deletion | 11-14h | KRYTYCZNE |
| **6. Email System** | Welcome, limit notifications, reminders, templates | 15-19h | KRYTYCZNE |
| **7. Analytics** | Event tracking, dashboard, metrics queries | 22-31h | WAŻNE |
| **8. Cron Jobs** | Limit reset, auto-complete, email reminders | 9-12h | KRYTYCZNE |
| **9. Error Handling** | AI errors, logging, user messages | 10-13h | KRYTYCZNE |
| **10. Testing & Quality** | Unit tests, feature tests, code quality | 26-33h | KRYTYCZNE |
| **11. Documentation** | API docs, Privacy/ToS, deployment | 12-17h | WAŻNE |
| **TOTAL** | | **145-196h** | |

**Przeliczenie na dni robocze** (8h/dzień):
- Minimum: ~18 dni roboczych
- Maximum: ~24 dni roboczych
- **Szacunek realistyczny**: 20-22 dni roboczych (4-5 tygodni)

### Priorytetyzacja - MVP Milestone

**MILESTONE 1: MVP CORE (KRYTYCZNE)** - ~80-100h (10-12 dni)
1. Email verification flow (2.1) - 4-6h
2. Rate limiting (2.2) - 3-4h
3. Account deletion (2.4) - 4-5h
4. UI limit counter (4.1) - 2-3h
5. Plan filtering (4.2) - 3-4h
6. Regeneracja planów (5.1) - 5-6h
7. Email system podstawowy (6.1, 6.2, 6.4) - 11-14h
8. Cron: limit reset + auto-complete (8.1, 8.2) - 6-8h
9. AI error handling (9.1, 9.3) - 8-10h
10. Feature tests core (10.2 - częściowo) - 8-10h
11. Język PL (4.4) - 8-12h
12. Deployment prep (11.3) - 6-8h

**MILESTONE 2: MVP POLISH (WAŻNE)** - ~40-50h (5-6 dni)
1. Edycja preferencji (3.2) - 3-4h
2. Event tracking (7.1) - 6-8h
3. Analytics queries (7.3) - 6-8h
4. Status transitions (5.2) - 4-5h
5. Error logging improvements (9.2) - 2-3h
6. Unit tests (10.1) - 8-10h
7. Code quality (10.3) - 6-8h
8. Privacy/ToS pages (11.2) - 2-3h

**MILESTONE 3: MVP COMPLETE (NICE-TO-HAVE)** - ~25-35h (3-4 dni)
1. Analytics dashboard UI (7.2) - 10-15h
2. Trip reminder emails (6.3, 8.3) - 7-9h
3. Tracking completion rate (3.3) - 4-5h
4. API documentation (11.1) - 4-6h

### Harmonogram przykładowy (zespół 1-2 devs)

**Tydzień 1: Fundamenty** (MILESTONE 1 - część 1)
- Dzień 1-2: Analiza + Email verification + Rate limiting
- Dzień 3: Account deletion + HTTPS
- Dzień 4-5: Język PL (ujednolicenie)

**Tydzień 2: Core Features** (MILESTONE 1 - część 2)
- Dzień 1: UI limit counter + Plan filtering
- Dzień 2-3: Regeneracja planów + Status transitions
- Dzień 4-5: Email system (welcome, limit notifications)

**Tydzień 3: Automation & Testing** (MILESTONE 1 - część 3)
- Dzień 1: Cron jobs (reset limits, auto-complete)
- Dzień 2: AI error handling
- Dzień 3-5: Feature tests + Deployment prep

**Tydzień 4: Analytics & Polish** (MILESTONE 2)
- Dzień 1-2: Event tracking + Analytics queries
- Dzień 3: Edycja preferencji + Error logging
- Dzień 4-5: Unit tests + Code quality

**Tydzień 5: Final Polish & Launch Prep** (MILESTONE 2 + 3)
- Dzień 1-2: Analytics dashboard (jeśli czas)
- Dzień 3: Privacy/ToS + Final testing
- Dzień 4-5: Deployment + Bug fixes

### Launch Criteria (z PRD 6.5)

**Must-have przed public beta**:
- [x] Onboarding completion rate >70% (w testach beta)
- [x] AI generation success rate >90%
- [x] Średni czas generowania <45 sekund
- [x] Plan satisfaction rate >60% (w testach beta)
- [x] Zero critical security vulnerabilities
- [x] Podstawowy monitoring i error tracking działają
- [x] Email delivery rate >95%
- [x] Wszystkie KRYTYCZNE funkcje z MILESTONE 1 zaimplementowane
- [x] Wszystkie testy passing (make test, make phpstan, make cs-check)

**Success criteria po 3 miesiącach** (z PRD):
- 100-500 zarejestrowanych użytkowników
- Metryka 1 (preferencje): >80% (cel 90%)
- 30-day retention: >40% (cel 50%)
- Plan satisfaction rate: >65% (cel 70%)
- MAU: >50% (cel 60%)

### Uwagi implementacyjne

**Kolejność wykonywania**:
1. Rozpocznij od MILESTONE 1 - są to funkcje krytyczne dla MVP
2. Każdą sekcję implementuj w kolejności: backend → UI → testy
3. Po każdym zadaniu uruchom quality checks (phpstan, pint)
4. Commit często, małymi jednostkami pracy
5. Pull requesty z opisem i linkiem do US/PRD

**Testing strategy**:
- Pisz testy równolegle z kodem (TDD approach zalecany)
- Najpierw feature tests (happy path)
- Potem unit tests (edge cases)
- Na końcu integration tests

**Code review**:
- Każdy PR wymaga review przed merge
- Sprawdzaj zgodność z PRD i US
- Weryfikuj czy kod jest self-documenting
- Uruchom manualnie critical paths

**Risk management**:
- **Największe ryzyka**:
  1. Język PL - dużo pracy manualnej (8-12h) - można fazować
  2. Analytics dashboard - duży zakres (10-15h) - można odłożyć do M3
  3. Testing - czasochłonne (26-33h) - priorytetyzować core flows
- **Mitigacja**: Fazuj pracę, dostarczaj iteracyjnie, testuj wcześnie

### Następne kroki

1. **Review planu** z zespołem/stakeholderami
2. **Priorytetyzacja** - potwierdzić co jest must-have vs nice-to-have
3. **Utworzenie tasków** w systemie zarządzania (Jira/Linear/GitHub Issues)
4. **Przypisanie** tasków do developerów
5. **Kickoff** - rozpoczęcie implementacji MILESTONE 1
6. **Daily standups** - tracking progress
7. **Weekly demos** - pokazywanie postępu
8. **Code review process** - ustalenie zasad
9. **Testing plan** - określenie strategii QA
10. **Launch plan** - przygotowanie deployment do production

---

## DODATEK: Checklisty do kopiowania

### Checklist - Pre-development
```markdown
- [ ] Plan zreviewowany z zespołem
- [ ] Priorytetyzacja zatwierdzona
- [ ] Tasks utworzone w project management tool
- [ ] Development environment setup (Docker running)
- [ ] Dependencies zainstalowane (composer, npm)
- [ ] Database migrations run
- [ ] .env skonfigurowany poprawnie
- [ ] Quality tools działają (make test, make phpstan)
```

### Checklist - Per Feature
```markdown
- [ ] User Story przeczytana i zrozumiana
- [ ] Design/mockup dostępny (jeśli UI)
- [ ] Backend logic zaimplementowana
- [ ] Frontend UI zaimplementowana
- [ ] Validation dodana
- [ ] Error handling dodany
- [ ] Feature tests napisane
- [ ] Unit tests napisane (jeśli service/action)
- [ ] PHPStan passing (make phpstan)
- [ ] Code style OK (make cs-check)
- [ ] Manual testing wykonane
- [ ] Code review completed
- [ ] Merged to main
```

### Checklist - Pre-deployment
```markdown
- [ ] Wszystkie MILESTONE 1 features complete
- [ ] All tests passing (make test)
- [ ] PHPStan level 8 clean (make phpstan)
- [ ] Code style 100% (make cs-check)
- [ ] Manual testing all critical paths
- [ ] Performance benchmarks OK (<45s AI generation)
- [ ] Security audit done (basic)
- [ ] Privacy Policy & ToS published
- [ ] .env.production prepared
- [ ] Database migrations tested on staging
- [ ] Email sending tested (all types)
- [ ] Cron jobs tested
- [ ] Backup strategy in place
- [ ] Monitoring configured
- [ ] Launch criteria met (PRD 6.5)
```

---

**Dokument przygotowany**: 2025-10-14
**Autor**: Claude Code Analysis
**Wersja**: 1.0
**Status**: Gotowy do review i implementacji

