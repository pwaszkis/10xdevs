# Prompt: Specyfikacja techniczna modułu autentykacji i onboardingu - VibeTravels

Jesteś doświadczonym full-stack web developerem specjalizującym się w Laravel i Livewire. Opracuj szczegółową architekturę modułu autentykacji i onboardingu użytkowników dla aplikacji VibeTravels na podstawie wymagań z pliku @.ai/prd.md (US-001 do US-010 oraz US-006 do US-008) oraz stacku technologicznego z @.ai/tech-stack.md.

Zadbaj o zgodność z pozostałymi wymaganiami - nie możesz naruszyć istniejącego działania aplikacji opisanego w dokumentacji (@CLAUDE.md, @database/SCHEMA.md).

## Kontekst projektu

VibeTravels to aplikacja webowa do planowania wycieczek z wykorzystaniem AI, zbudowana na stacku:
- **Backend**: Laravel 11 + Laravel Breeze (autentykacja) + Laravel Socialite (Google OAuth)
- **Frontend**: Livewire 3 + Alpine.js + Blade + Tailwind CSS 4 + Wire UI
- **Baza danych**: MySQL 8
- **Komunikacja frontend-backend**: Komponenty Livewire (server-side rendering + reactive updates)

**Kluczowa różnica**: Aplikacja NIE używa REST API do komunikacji między frontendem a backendem. Cała logika jest obsługiwana przez komponenty Livewire, które łączą PHP (backend) z reaktywnymi elementami UI bez konieczności pisania JavaScript API calls.

## Wymagania funkcjonalne do pokrycia

### Autentykacja (US-001 do US-005, US-009 do US-010):
- Rejestracja za pomocą email + hasło z weryfikacją emaila
- Rejestracja i logowanie przez Google OAuth (Laravel Socialite)
- Zarządzanie sesjami (secure cookies, HTTPS)
- Wylogowanie
- Usunięcie konta (hard delete, zgodność z GDPR)

### Onboarding (US-006 do US-008):
- Obowiązkowy proces po pierwszej rejestracji
- Krok 1: Ekran powitalny + dane podstawowe (nick, miasto domowe)
- Krok 2: Wybór kategorii zainteresowań (multi-select: Historia i kultura, Przyroda i outdoor, Gastronomia, Nocne życie, Plaże i relaks, Sporty, Sztuka i muzea)
- Krok 3: Parametry praktyczne (tempo podróży, budżet, transport, ograniczenia)
- Tracking completion rate onboardingu
- Wysyłka welcome emaila po ukończeniu

## Specyfikacja powinna zawierać następujące elementy:

### 1. ARCHITEKTURA INTERFEJSU UŻYTKOWNIKA

- **Struktura komponentów Livewire**:
  - Dokładny opis komponentów Livewire do utworzenia (np. `Auth/Register.php`, `Auth/Login.php`, `Onboarding/WizardStep1.php`, etc.)
  - Odpowiedzialności każdego komponentu (zarządzanie stanem formularza, walidacja, dispatching events)
  - Komunikacja między komponentami (Livewire events, query strings, session flash data)

- **Blade views i layouty**:
  - Struktura widoków Blade dla każdego komponentu
  - Layouty dla trybu auth vs non-auth (guest layout vs authenticated layout)
  - Wykorzystanie Wire UI components (buttons, inputs, modals, etc.)

- **Interakcje Alpine.js**:
  - Minimalne użycie Alpine.js do UI interactions (toggle dropdowns, show/hide sections, local state)
  - Rozdzielenie odpowiedzialności: Livewire = logika biznesowa + backend, Alpine.js = drobne UI interactions

- **Walidacja i komunikaty błędów**:
  - Walidacja real-time w komponentach Livewire (Form Requests vs inline validation)
  - Wyświetlanie błędów walidacji (Blade @error directives, Wire UI notifications)
  - Komunikaty sukcesu (session flash messages, Livewire notifications)

- **Routing i nawigacja**:
  - Struktura routes/web.php i routes/auth.php (Laravel Breeze)
  - Middleware do ochrony tras (auth, verified, guest)
  - Redirects po akcjach (np. po rejestracji → onboarding, po logowaniu → dashboard)

### 2. LOGIKA BACKENDOWA

- **Kontrolery i akcje**:
  - Czy używać kontrolerów Breeze czy komponenty Livewire obsługują akcje inline?
  - Struktura akcji: RegisteredUserController, AuthenticatedSessionController, etc.
  - Integracja z Laravel Socialite (GoogleController, callback handling)

- **Modele i relacje**:
  - Wykorzystanie istniejących modeli: `User`, `UserPreference`
  - Relacje Eloquent (User hasOne UserPreference)
  - Fillable/guarded properties, casts, hidden fields

- **Serwisy biznesowe**:
  - Czy tworzyć dedykowane serwisy (np. `OnboardingService`, `UserProfileService`)?
  - Logika zapisywania preferencji użytkownika w multi-step onboardingu
  - Tracking completion rate onboardingu

- **Walidacja danych**:
  - Form Request classes vs inline validation w Livewire
  - Reguły walidacji dla każdego kroku (email, hasło, preferencje)
  - Custom validation rules jeśli potrzebne

- **Obsługa wyjątków**:
  - Scenariusze błędów: email już istnieje, Google OAuth anulowany, timeout weryfikacji
  - Wyświetlanie błędów użytkownikowi (Livewire notifications vs session flash)

- **Email notifications**:
  - Wykorzystanie Laravel Mail + Mailgun/MailHog
  - Email weryfikacyjny (built-in Laravel Breeze)
  - Welcome email po ukończeniu onboardingu (custom Mailable)
  - Struktura templates emaili (Blade views w resources/views/mail/)

### 3. SYSTEM AUTENTYKACJI

- **Laravel Breeze**:
  - Instalacja i konfiguracja Breeze (Livewire variant)
  - Modyfikacje domyślnych komponentów Breeze pod wymagania VibeTravels
  - Email verification flow
  - Password reset (opcjonalnie - czy w MVP?)

- **Laravel Socialite (Google OAuth)**:
  - Konfiguracja Socialite w config/services.php
  - Implementacja GoogleController: redirect + callback
  - Tworzenie lub łączenie użytkownika z Google account
  - Obsługa przypadków: nowy użytkownik vs existing user z tym samym emailem

- **Middleware i ochrona tras**:
  - `auth`: wymagane zalogowanie
  - `verified`: wymagana weryfikacja emaila
  - `guest`: tylko dla niezalogowanych
  - Custom middleware: `EnsureOnboardingCompleted` (redirect do onboardingu jeśli nieukończony)

- **Session management**:
  - Konfiguracja secure cookies (config/session.php)
  - HTTPS enforcement (middleware, .env settings)
  - Session lifetime i remember me functionality

- **Security**:
  - Hashowanie haseł (bcrypt - domyślnie w Laravel)
  - Rate limiting dla login/register (Laravel's RateLimiter)
  - CSRF protection (automatyczne w Livewire)
  - Input sanitization (automatyczne w Eloquent, ale wspomnij)

### 4. PRZEPŁYW UŻYTKOWNIKA (USER FLOW)

- **Scenariusz 1: Rejestracja email+hasło**:
  - Użytkownik → formularz rejestracji → zapis do DB → email weryfikacyjny → weryfikacja → onboarding (3 kroki) → welcome email → dashboard

- **Scenariusz 2: Rejestracja Google OAuth**:
  - Użytkownik → kliknięcie "Sign in with Google" → redirect do Google → callback → zapis/update użytkownika → onboarding → welcome email → dashboard

- **Scenariusz 3: Logowanie returning user**:
  - Użytkownik → formularz logowania → weryfikacja credentials → redirect do dashboard (jeśli onboarding ukończony) lub onboarding (jeśli nie)

- **Scenariusz 4: Usunięcie konta**:
  - Użytkownik → ustawienia profilu → "Usuń konto" → modal potwierdzenia → hard delete (user + user_preferences + related data) → wylogowanie → redirect do strony głównej

### 5. BAZA DANYCH

- **Migracje**:
  - Które migracje są już gotowe (users, user_preferences - sprawdź @database/SCHEMA.md)
  - Jakie modyfikacje/dodatkowe kolumny potrzebne (np. onboarding_completed_at w users?)

- **Seedy**:
  - Czy przygotować seeder z przykładowymi użytkownikami dla developmentu?

### 6. TESTOWANIE

- **Scenariusze do przetestowania manualnie**:
  - Rejestracja email → weryfikacja → onboarding → dashboard
  - Rejestracja Google → onboarding → dashboard
  - Logowanie existing user
  - Próba dostępu do dashboard bez ukończonego onboardingu
  - Usunięcie konta

- **Integracja z MailHog**:
  - Weryfikacja wysyłki emaili w środowisku deweloperskim

## Format odpowiedzi

Przedstaw kluczowe wnioski w formie **opisowej technicznej specyfikacji** w języku polskim - bez docelowej implementacji kodu, ale ze wskazaniem:
- Poszczególnych komponentów Livewire (nazwy klas i odpowiedzialności)
- Blade views i layoutów
- Kontrolerów i serwisów
- Modeli i relacji
- Middleware i routing
- Przepływów użytkownika krok po kroku
- Punktów integracji z istniejącym kodem

Specyfikacja powinna być na tyle szczegółowa, aby programista Laravel mógł na jej podstawie zaimplementować funkcjonalność bez dodatkowych pytań o architekturę.

## Output

Po ukończeniu zadania, utwórz plik `.ai/auth-onboarding-spec.md` i dodaj tam całą specyfikację w formacie Markdown z klarownymi nagłówkami i listami.
