# Architektura UI dla VibeTravels MVP

## 1. Przegląd struktury UI

### 1.1 Podejście architektoniczne

VibeTravels MVP wykorzystuje **mobile-first responsive design** z progresywnym wzbogacaniem dla desktop. Architektura opiera się na trzech głównych layoutach:

- **Guest Layout** - dla użytkowników niezalogowanych (landing, register, login)
- **Onboarding Layout** - dedykowany full-screen layout dla procesu onboardingu (4 kroki)
- **App Layout** - główny layout aplikacji z sidebar (desktop) / topbar (mobile)

### 1.2 Stack techniczny UI

- **Framework**: Laravel Livewire 3 + Alpine.js
- **Styling**: Tailwind CSS 4 (utility-first, mobile-first)
- **Komponenty**: Wire UI (base) + custom components
- **State Management**: Hybrid (Livewire state + API fetch + cache)
- **Dostępność**: WCAG 2.1 Level AA

### 1.3 Kluczowe założenia projektowe

1. **Progressive Enhancement**: Native HTML controls z Wire UI enhancements
2. **Performance-focused**: Skeleton loaders, lazy loading, caching, pagination
3. **Accessibility-first**: Keyboard navigation, ARIA labels, contrast ratios 4.5:1
4. **Pesimistic UI**: Wait for API response (MVP simplicity)
5. **Component-based**: Reusable nested Livewire components

## 2. Lista widoków

### 2.1 Public Views (Guest Layout)

#### 2.1.1 Landing Page
- **Ścieżka**: `/`
- **Layout**: Guest Layout
- **Cel**: Prezentacja produktu i zachęcenie do rejestracji
- **Kluczowe informacje**:
  - Hero section z value proposition
  - Feature highlights (AI planning, personalizacja, eksport PDF)
  - Social proof (opcjonalnie w MVP)
  - CTA buttons: "Zarejestruj się" / "Zaloguj się"
- **Komponenty**:
  - Hero component z ilustracją
  - Features grid (3 kolumny desktop, stack mobile)
  - CTA section
- **UX/Accessibility**: Skip links, focus management, alt texts dla images
- **Bezpieczeństwo**: Brak wrażliwych danych, HTTPS enforced

#### 2.1.2 Rejestracja
- **Ścieżka**: `/register`
- **Layout**: Guest Layout
- **Cel**: Rejestracja nowego użytkownika (email+hasło lub Google OAuth)
- **Kluczowe informacje**:
  - Formularz: email, password, password confirmation
  - "Sign in with Google" button
  - Link do logowania
  - Password strength indicator
- **Komponenty**:
  - PasswordStrength component (Alpine.js local validation)
  - Google OAuth button
  - Form validation (inline errors)
- **API Integration**: `POST /api/auth/register`, `GET /api/auth/google`
- **UX/Accessibility**:
  - aria-required dla required fields
  - aria-invalid dla błędów
  - Focus na pierwszym polu przy load
  - Password visibility toggle
- **Bezpieczeństwo**:
  - CSRF protection (Livewire automatic)
  - Rate limiting: 3 attempts/hour
  - Client-side validation + server-side validation
  - Password strength feedback (min 8 chars)

#### 2.1.3 Logowanie
- **Ścieżka**: `/login`
- **Layout**: Guest Layout
- **Cel**: Logowanie użytkownika
- **Kluczowe informacje**:
  - Formularz: email, password
  - "Sign in with Google" button
  - Link do rejestracji
  - "Remember me" checkbox (opcjonalnie w MVP)
- **Komponenty**:
  - Login form component
  - Google OAuth button
- **API Integration**: `POST /api/auth/login`, `GET /api/auth/google`
- **UX/Accessibility**:
  - Auto-focus na email field
  - Clear error messages dla failed login
  - Keyboard submit (Enter)
- **Bezpieczeństwo**:
  - Rate limiting: 5 attempts/5 min
  - Disabled form + countdown przy rate limit
  - Session cookies (HTTP-only, secure)

### 2.2 Onboarding Views (Onboarding Layout)

#### 2.2.1 Onboarding - Full Flow
- **Ścieżka**: `/onboarding`
- **Layout**: Onboarding Layout (full-screen)
- **Cel**: Zebranie preferencji użytkownika dla personalizacji AI
- **Struktura**: 4 steps (obowiązkowe, sekwencyjne, no skip)

**Step 1: Dane podstawowe**
- **Kluczowe informacje**:
  - Progress indicator: "1/4"
  - Welcome message: "Witaj! Zacznijmy od podstaw"
  - Pola: Nick (required), Kraj/miasto domowe (required)
- **Komponenty**:
  - Progress bar component
  - Text inputs z validation
  - Sticky footer: "Dalej" button (disabled until valid)
- **API Integration**: `PATCH /api/users/me/onboarding` (step: 1)
- **UX/Accessibility**:
  - role="progressbar" z aria-valuenow="1" aria-valuemax="4"
  - Clear field labels
  - Min 44px touch targets dla buttons

**Step 2: Kategorie zainteresowań**
- **Kluczowe informacje**:
  - Progress indicator: "2/4"
  - Pytanie: "Co Cię interesuje podczas podróży?"
  - 7 kategorii (multi-select, min 1 required):
    - Historia i kultura
    - Przyroda i outdoor
    - Gastronomia
    - Nocne życie i rozrywka
    - Plaże i relaks
    - Sporty i aktywności
    - Sztuka i muzea
- **Komponenty**:
  - Checkbox grid (3 cols desktop, 1 col mobile)
  - Każda kategoria: ikona + visible label + checkbox
  - Selected state: border + background color
- **API Integration**: `PATCH /api/users/me/onboarding` (step: 2)
- **UX/Accessibility**:
  - aria-label dla każdej ikony
  - Visible text labels (nie tylko ikony)
  - Checkbox visible (nie hidden)
  - Validation message jeśli brak selection

**Step 3: Parametry praktyczne**
- **Kluczowe informacje**:
  - Progress indicator: "3/4"
  - Pytanie: "Jak planujesz podróżować?"
  - 4 parametry (single-select each):
    - Tempo: Spokojne / Umiarkowane / Intensywne
    - Budżet: Ekonomiczny / Standardowy / Premium
    - Transport: Pieszo i publiczny / Wynajem auta / Mix
    - Ograniczenia: Brak / Dieta / Mobilność
- **Komponenty**:
  - Radio button groups (4 grupy)
  - Card-based selection UI
- **API Integration**: `PATCH /api/users/me/onboarding` (step: 3)
- **UX/Accessibility**:
  - role="radiogroup" dla każdej grupy
  - Clear visual selection state
  - Sticky footer: "Wstecz" + "Zakończ" buttons

**Step 4: Completion**
- **Kluczowe informacje**:
  - Progress indicator: "4/4"
  - Podsumowanie wybranych preferencji
  - Potwierdzenie completion
- **Komponenty**:
  - Summary cards z wybranymi preferencjami
  - "Zakończ" button (trigger welcome email)
- **API Integration**: `PATCH /api/users/me/onboarding` (step: 4)
- **Flow**: Redirect → Welcome Screen → Dashboard

#### 2.2.2 Welcome Screen
- **Ścieżka**: `/welcome` (post-onboarding)
- **Layout**: App Layout (simple variant)
- **Cel**: Powitanie użytkownika i intro do aplikacji
- **Kluczowe informacje**:
  - "Witaj w VibeTravels, [Nick]! 🎉"
  - 2-3 bullet points:
    - "Masz 10 generowań AI miesięcznie"
    - "Twoje preferencje pomogą tworzyć idealne plany"
    - "Eksportuj plany do PDF i zabierz w podróż"
  - Big CTA: "Stwórz swój pierwszy plan"
  - Link: "Przejdź do Dashboard"
- **Komponenty**: Centered card z CTA
- **UX**: Auto-dismiss po 5 sekundach lub click CTA
- **Accessibility**: Focus trap na CTA button

### 2.3 Authenticated Views (App Layout)

#### 2.3.1 Dashboard
- **Ścieżka**: `/dashboard`
- **Layout**: App Layout (sidebar/topbar + main content)
- **Cel**: Centralne miejsce zarządzania planami podróży
- **Kluczowe informacje**:
  - Hero section: "Cześć [Nick]! Zaplanuj swoją kolejną przygodę"
  - Primary CTA: "Stwórz nowy plan"
  - Lista planów użytkownika (cards)
  - Quick filters: Wszystkie / Szkice / Zaplanowane / Zrealizowane
  - Pagination (20 per page)
  - User stats (opcjonalnie): "Stworzyłeś X planów"
- **Komponenty**:
  - TravelPlanCard (nested, reusable):
    - Miniatura destynacji (opcjonalnie)
    - Tytuł planu
    - Destynacja
    - Daty (od-do)
    - Status badge (Draft/Planned/Completed)
    - Liczba dni/osób
    - Hover actions: "Zobacz szczegóły"
  - Filter buttons (Livewire reactive)
  - Pagination component (Wire UI)
  - Empty state (jeśli 0 planów)
- **API Integration**:
  - `GET /api/travel-plans` (z query params: status, sort, page)
  - Cache: 60s, invalidate on create/delete
- **UX/Accessibility**:
  - Card grid: 1 col mobile, 2 cols tablet, 3 cols desktop
  - Keyboard navigation (Tab przez cards)
  - aria-label dla filter buttons
  - Skip link: "Przejdź do listy planów"
- **Empty State**:
  - Illustration (podróż/mapa)
  - "Nie masz jeszcze żadnych planów podróży"
  - CTA: "Stwórz swój pierwszy plan"
- **Bezpieczeństwo**: Row-level security (tylko plany użytkownika)

#### 2.3.2 Tworzenie Planu
- **Ścieżka**: `/plans/create`
- **Layout**: App Layout
- **Cel**: Utworzenie nowego planu podróży (draft lub z immediate AI generation)
- **Kluczowe informacje**:
  - Page title: "Stwórz nowy plan podróży"
  - Formularz (single page, progressive disclosure):
    - **Required fields (visible)**:
      - Tytuł planu (text input, max 255 chars)
      - Destynacja (text input, max 255 chars)
      - Data wyjazdu (native date picker, not in past)
      - Liczba dni (number input, 1-30)
      - Liczba osób (number input, 1-10)
    - **Optional fields (collapsed)**:
      - "Dodaj budżet ▼" (expand button)
        - Budżet na osobę (number input)
        - Waluta (select: PLN/USD/EUR)
    - **User notes**:
      - Duża textarea: "Twoje pomysły i notatki"
      - Helper text: "Im więcej szczegółów, tym lepszy plan!" (dismissible tooltip, localStorage)
  - Sticky footer z buttons:
    - "Zapisz jako szkic" (secondary)
    - "Generuj plan" (primary, check AI limit)
- **Komponenty**:
  - Form component z progressive disclosure
  - DatePicker (native + Wire UI enhancement)
  - Sticky footer component
  - Validation inline errors
- **API Integration**:
  - `POST /api/travel-plans` z `generate_now: false` (szkic)
  - `POST /api/travel-plans` z `generate_now: true` (AI generation)
  - `GET /api/users/me` (check AI limit przed generation)
- **UX/Accessibility**:
  - wire:model.blur dla większości fields
  - Client-side validation przed submit
  - aria-required dla required fields
  - aria-expanded dla collapsed sections
  - Scroll to first error jeśli validation fails
- **Bezpieczeństwo**:
  - Onboarding must be complete
  - CSRF protection
  - Sanitization user notes
  - Check AI limit server-side
- **Edge Cases**:
  - AI limit 10/10: Button disabled z tooltipem
  - Form timeout 30s: Error toast + retry

#### 2.3.3 AI Generation Loading
- **Ścieżka**: `/plans/{id}/generating`
- **Layout**: App Layout (simplified, no sidebar distractions)
- **Cel**: Pokazanie postępu generowania AI i polling statusu
- **Kluczowe informacje**:
  - Page title: "Generowanie planu..."
  - Animated spinner / progress bar
  - Komunikat: "Generuję plan... To może potrwać do 45 sekund"
  - Progress percentage (jeśli API zwraca)
  - Elapsed time counter
  - Po 90s: "Generowanie trwa dłużej niż zwykle... Proszę czekać."
- **Komponenty**:
  - Loading spinner component
  - Progress bar (estimated based on elapsed time)
  - Status message (dynamic)
- **API Integration**:
  - Livewire `wire:poll.3s` → `GET /api/travel-plans/{id}/generation-status`
  - Status: pending → processing → completed / failed
- **Flow**:
  - Status `completed`: Redirect `/plans/{id}` (+ confetti jeśli pierwszy plan)
  - Status `failed`: Redirect error screen
  - Timeout >120s: Error screen
- **UX/Accessibility**:
  - role="status" aria-live="polite"
  - No cancel button (MVP)
  - Browser beforeunload warning jeśli user próbuje opuścić
- **Bezpieczeństwo**:
  - Generation kontynuuje w tle jeśli user opuści stronę
  - Row-level security check

#### 2.3.4 Szczegóły Planu
- **Ścieżka**: `/plans/{id}`
- **Layout**: App Layout
- **Cel**: Wyświetlenie wygenerowanego planu lub draftu
- **Kluczowe informacje**:

  **Plan Header**:
  - Tytuł planu (h1)
  - Destynacja
  - Daty (od-do)
  - Liczba osób
  - Budżet (jeśli podany)
  - Status badge (Draft/Planned/Completed)
  - Actions: "Usuń plan" (destructive)

  **Sekcja "Twoje założenia" (collapsed)**:
  - Link: "Zobacz Twoje założenia ▼"
  - Expand pokazuje:
    - User notes (textarea content)
    - Preference badges (tempo, budżet, transport, ograniczenia)
    - Selected interest categories

  **Plan Days (accordion, tylko dla generated plans)**:
  - Mobile: wszystkie dni collapsed
  - Desktop: pierwszy dzień expanded
  - Każdy dzień jako card:
    - Header: "Dzień 1 - 15.07.2025" + expand/collapse icon
    - Content (expanded): Plan Points pogrupowane po porze dnia

  **Plan Points (nested w days)**:
  - Collapsed state:
    - Nazwa punktu
    - Ikona pory dnia (rano/południe/popołudnie/wieczór)
    - Czas trwania
  - Expanded state (click anywhere):
    - Nazwa (h3)
    - Opis (2-3 zdania)
    - Uzasadnienie dopasowania (italic, mniejsza czcionka)
    - Czas wizyty (ikona + tekst)
    - Google Maps link: "📍 Zobacz na mapie" (target="_blank", rel="noopener")

  **Footer**:
  - Feedback form (inline, collapsed)
  - "Export do PDF" button
  - "Regeneruj plan" button (warning o zużyciu limitu)

- **Komponenty**:
  - PlanHeader component
  - AssumptionsSection (collapsible)
  - PlanDay component (nested, accordion)
    - PlanPoint component (nested, expandable card)
  - FeedbackForm (inline, collapsed)
  - PDF export button
- **API Integration**:
  - `GET /api/travel-plans/{id}?include=days,days.points,feedback`
  - No cache (zawsze fresh data)
  - `POST /api/travel-plans/{id}/feedback`
  - `GET /api/travel-plans/{id}/pdf`
  - `POST /api/travel-plans/{id}/generate` (regeneration)
- **UX/Accessibility**:
  - Lazy loading: First 3 days loaded, rest on "Pokaż więcej" (dla 20-30 dni planów)
  - aria-expanded dla accordion days
  - Keyboard navigation (Enter = toggle expand)
  - Focus management przy expand/collapse
- **Bezpieczeństwo**:
  - Row-level security
  - 403 jeśli plan nie należy do user
- **Edge Cases**:
  - Draft (no AI content): Pokazać tylko header + assumptions + CTA "Generuj plan"
  - Regeneration z limitem 10/10: Button disabled z tooltipem
  - Generation pending: Disable edit/regenerate

#### 2.3.5 Feedback Form (Inline Component)
- **Lokalizacja**: Footer planu `/plans/{id}`
- **Cel**: Zebranie feedbacku o jakości planu
- **Stan początkowy**: Collapsed
- **Kluczowe informacje**:
  - Link: "Oceń ten plan ▼"
  - Expand pokazuje:
    - Pytanie: "Czy plan spełnia Twoje oczekiwania?"
    - Buttons: "Tak" / "Nie"
    - Jeśli "Nie": Conditional checkboxes:
      - Za mało szczegółów
      - Nie pasuje do moich preferencji
      - Słaba kolejność zwiedzania
      - Inne (+ optional textarea)
    - Submit button: "Wyślij feedback"
- **API Integration**: `POST /api/travel-plans/{id}/feedback`
- **UX/Accessibility**:
  - Smooth expand animation (Alpine.js x-collapse)
  - Toast confirmation po submit: "Dziękujemy za feedback!"
  - One feedback per plan (unique constraint)
- **Edge Case**: Feedback już submitted: Pokazać "Twój feedback: [satisfied/not satisfied]"

#### 2.3.6 Error Screens

**AI Generation Failed**
- **Ścieżka**: `/plans/{id}/error` (lub inline)
- **Kluczowe informacje**:
  - Icon: Error/warning
  - "Nie udało się wygenerować planu"
  - Error message z API (jeśli dostępny)
  - Buttons:
    - "Spróbuj ponownie" (primary, nie zużywa limitu)
    - "Wróć do planu" (secondary, do draft view)
  - Link: "Zgłoś problem" (mailto support)
- **UX**: Clear recovery path, nie irytujący tone

**404 Not Found**
- **Ścieżka**: `/404`
- **Kluczowe informacje**:
  - "Ten plan nie istnieje lub został usunięty"
  - Buttons:
    - "Wróć do Dashboard"
    - "Stwórz nowy plan"
- **UX**: Helpful, nie blame user

**429 Rate Limit**
- **Typ**: Modal (blokujący)
- **Kluczowe informacje**:
  - "Zbyt wiele prób. Spróbuj ponownie za [countdown] sekund"
  - Countdown timer (live update)
  - Wszystkie przyciski disabled
  - Modal nie closeable
- **UX**: Auto-refresh możliwości po countdown

#### 2.3.7 Profil Użytkownika
- **Ścieżka**: `/profile`
- **Layout**: App Layout
- **Cel**: Wyświetlenie i edycja danych profilu
- **Kluczowe informacje**:

  **Dane podstawowe**:
  - Nick (editable)
  - Email (read-only, z oznaczeniem verification status)
  - Kraj/miasto domowe (editable)

  **Statystyki**:
  - "Stworzyłeś X planów"
  - "Zużyłeś X/10 generowań w tym miesiącu"
  - Reset date: "1 listopada 2025"

  **Actions**:
  - "Edytuj profil" button
  - Link do Settings (preferencje)
  - "Usuń konto" (destructive, hidden za expandem)

- **Komponenty**:
  - Profile card component
  - Edit mode (inline lub modal)
  - Stats cards
  - Delete account confirmation modal
- **API Integration**:
  - `GET /api/users/me`
  - `PATCH /api/users/me`
  - `DELETE /api/users/me` (z confirmation: "DELETE")
- **UX/Accessibility**:
  - Clear edit/view mode distinction
  - Confirmation modal dla delete (double-check)
  - Toast success po update
- **Bezpieczeństwo**:
  - Email nie editable (wymaga re-verification)
  - Hard delete cascade (GDPR)
  - Confirmation input: "DELETE"

#### 2.3.8 Ustawienia Preferencji
- **Ścieżka**: `/settings`
- **Layout**: App Layout
- **Cel**: Edycja preferencji turystycznych
- **Kluczowe informacje**:

  **Kategorie zainteresowań**:
  - Multi-select (min 1)
  - 7 kategorii z ikonami
  - UI analogiczne do onboarding step 2

  **Parametry praktyczne**:
  - Tempo podróży (radio group)
  - Budżet (radio group)
  - Transport (radio group)
  - Ograniczenia (radio group)

  **Actions**:
  - "Zapisz zmiany" (primary)
  - "Anuluj" (secondary, discard changes)

- **Komponenty**:
  - Preferences form (reuse onboarding components)
  - Save/Cancel sticky footer
- **API Integration**:
  - `GET /api/users/me/preferences`
  - `PATCH /api/users/me/preferences`
  - Cache: 1h, invalidate on update
- **UX/Accessibility**:
  - Identical UI do onboarding (consistency)
  - Unsaved changes warning jeśli user próbuje navigate away
  - Toast success po save
- **Impact**: Zmiany wpływają na future AI generations

### 2.4 Shared Components (Global)

#### 2.4.1 Email Verification Banner
- **Lokalizacja**: Top wszystkich authenticated pages
- **Warunek**: `email_verified_at === null`
- **Kluczowe informacje**:
  - Sticky banner (żółty background)
  - "Twój email nie jest zweryfikowany"
  - Link: "Wyślij ponownie email weryfikacyjny"
  - Rate limit: 1 email/5 min (countdown jeśli recently sent)
- **API Integration**: `POST /api/auth/resend-verification`
- **UX**: Znika po verification (Livewire reactive)
- **Accessibility**: role="alert", nie blokuje UI

#### 2.4.2 Session Timeout Modal
- **Trigger**: Livewire `wire:poll.60s` check session
- **Warunek**: <5 min do wygaśnięcia (115 min od login)
- **Kluczowe informacje**:
  - Modal (non-closeable)
  - "Sesja wygaśnie za [countdown] minut"
  - Countdown timer (live)
  - Button: "Tak, przedłuż sesję"
- **API Integration**: `POST /api/auth/refresh-session` (custom endpoint)
- **UX**: Graceful handling, nie traci pracy użytkownika
- **Accessibility**: Focus trap na button

#### 2.4.3 Toast Notifications System
- **Lokalizacja**: Prawy górny róg (desktop), top center (mobile)
- **Typy**:
  - Success (zielony, checkmark icon)
  - Error (czerwony, X icon)
  - Warning (pomarańczowy, ! icon)
  - Info (niebieski, i icon)
- **Behavior**:
  - Auto-dismiss: 5 sekund
  - Max 3 toasts stacked
  - Newest on top
  - Slide-in animation
  - Click to dismiss (opcjonalnie)
- **Accessibility**:
  - role="alert"
  - aria-live="polite"
  - Screen reader announces
- **Wire UI Integration**: Wbudowany notifications system

#### 2.4.4 Sidebar Navigation (Desktop)
- **Lokalizacja**: Left side, App Layout
- **Kluczowe informacje**:
  - Logo VibeTravels (top)
  - Navigation links:
    - Dashboard (home icon)
    - Profil (user icon)
    - Ustawienia (gear icon)
  - AI Limit Counter:
    - Badge: "Generowania: X/10"
    - Progress bar (visual)
    - Kolory: 0-7 zielony, 8-9 pomarańczowy, 10 czerwony
    - Tooltip: "Reset limitu: 1 listopada 2025"
  - Logout button (bottom)
- **Componenty**: Sidebar component (Livewire)
- **API Integration**: `GET /api/users/me` (AI counter)
- **UX/Accessibility**:
  - Active link highlight
  - Keyboard navigation
  - role="navigation"
  - aria-current dla active page

#### 2.4.5 Topbar Navigation (Mobile)
- **Lokalizacja**: Top, App Layout mobile
- **Kluczowe informacje**:
  - Hamburger menu (left)
  - Logo center (opcjonalnie)
  - AI limit badge (compact, right)
- **Hamburger menu expand**:
  - Full-screen overlay
  - Navigation links (same as sidebar)
  - Logout button
- **UX/Accessibility**:
  - Touch-friendly (min 44px)
  - Focus trap w expanded menu
  - Escape to close
  - aria-expanded dla hamburger

#### 2.4.6 Skeleton Loaders
- **Użycie**: Dashboard plan list, plan details loading
- **Variants**:
  - PlanCard skeleton (3 cards, shimmer animation)
  - PlanDay skeleton (accordion structure)
  - Profile skeleton
- **UX**: Pokazuje strukturę przed załadowaniem (lepsze niż spinner)
- **Accessibility**: aria-busy="true", aria-label="Loading content"

## 3. Mapa podróży użytkownika

### 3.1 Journey 1: Nowy Użytkownik → Pierwszy Plan

**Etap 1: Rejestracja i Weryfikacja**
1. User ląduje na Landing Page `/`
2. Klik "Zarejestruj się" → `/register`
3. Wybór:
   - **Opcja A**: Email + hasło → Submit → Email weryfikacyjny wysłany
   - **Opcja B**: "Sign in with Google" → Google OAuth → Auto-verified
4. Email verification:
   - User klika link w emailu → `GET /api/auth/verify-email/{token}`
   - Redirect → `/login` lub auto-login
5. Login → Redirect `/onboarding`

**Etap 2: Onboarding (Obowiązkowy)**
6. **Step 1/4**: Dane podstawowe
   - User wpisuje nick + miasto domowe
   - Klik "Dalej" → `PATCH /api/users/me/onboarding` (step: 1)
7. **Step 2/4**: Kategorie zainteresowań
   - User wybiera min 1 kategorię (multi-select)
   - Klik "Dalej" → `PATCH /api/users/me/onboarding` (step: 2)
8. **Step 3/4**: Parametry praktyczne
   - User wybiera tempo, budżet, transport, ograniczenia
   - Klik "Zakończ" → `PATCH /api/users/me/onboarding` (step: 4)
9. **Completion**: Redirect → `/welcome`

**Etap 3: Welcome & Dashboard**
10. Welcome screen (5s auto-dismiss)
    - "Witaj w VibeTravels, [Nick]!"
    - Intro bullets
    - CTA: "Stwórz swój pierwszy plan"
11. Klik CTA → Redirect `/dashboard` (lub auto po 5s)
12. Dashboard empty state:
    - 0 planów
    - Illustration + "Nie masz jeszcze planów"
    - CTA: "Stwórz swój pierwszy plan"

**Etap 4: Tworzenie Pierwszego Planu**
13. Klik CTA → `/plans/create`
14. User wypełnia formularz:
    - Tytuł, Destynacja, Data, Dni, Osoby (required)
    - Opcjonalnie: Budżet (expand)
    - User notes (duża textarea, helper tooltip)
15. Klik "Generuj plan":
    - Client-side validation
    - Submit → `POST /api/travel-plans` (generate_now: true)
    - Redirect `/plans/{id}/generating`

**Etap 5: AI Generation**
16. Loading screen:
    - Spinner + progress bar
    - "Generuję plan... To może potrwać do 45 sekund"
    - Livewire `wire:poll.3s` → `GET /api/travel-plans/{id}/generation-status`
17. Status updates:
    - pending → processing (progress %)
    - Po 45-90s: "Trwa dłużej niż zwykle..."
18. Success: Status `completed` → Redirect `/plans/{id}`

**Etap 6: Pierwszy Plan (Confetti!)**
19. Plan details page loaded
20. **Confetti animation** (pierwszy plan gamification)
21. Auto-scroll do feedback form (lub modal prompt)
22. User eksploruje plan:
    - Rozwija dni (accordion)
    - Klika punkty (expand cards)
    - Czyta opisy + uzasadnienia
    - Klika Google Maps links

**Etap 7: Feedback & PDF Export**
23. User klika "Oceń ten plan ▼"
24. Expand feedback form:
    - "Czy plan spełnia Twoje oczekiwania?"
    - User wybiera "Tak" → Submit → Toast "Dziękujemy!"
25. User klika "Export do PDF":
    - Button disabled + spinner
    - Toast "Generowanie PDF..."
    - `GET /api/travel-plans/{id}/pdf` → Download triggered
    - Toast "PDF został pobrany pomyślnie"

**Etap 8: Return to Dashboard**
26. User naviguje (sidebar/topbar) → `/dashboard`
27. Dashboard teraz pokazuje 1 plan:
    - Card z tytułem, destynacją, datami
    - Status badge: "Zaplanowane"
    - Badge "Pierwszy plan" (gamification)
28. AI limit counter: "1/10"

### 3.2 Journey 2: Regeneracja Planu

**Scenariusz**: User nie jest zadowolony z planu, chce regenerować

1. User w `/plans/{id}`
2. Klik "Regeneruj plan" button
3. Warning modal:
   - "Spowoduje to wygenerowanie nowego planu (X/10 w tym miesiącu)"
   - "Poprzedni plan zostanie nadpisany. Kontynuować?"
   - Buttons: "Anuluj" / "Regeneruj"
4. User potwierdza → `POST /api/travel-plans/{id}/generate`
5. Redirect `/plans/{id}/generating` (identyczny flow jak pierwszy raz)
6. Success → Redirect `/plans/{id}` (nowy plan overwrite poprzedni)
7. AI limit counter: X+1/10

**Edge Case**: Jeśli AI limit 10/10:
- Button "Regeneruj plan" disabled
- Tooltip: "Osiągnąłeś limit 10/10. Reset 1 listopada 2025"

### 3.3 Journey 3: Edycja Preferencji

**Scenariusz**: User chce zmienić preferencje (np. dodać nową kategorię interest)

1. User w `/dashboard` lub `/profile`
2. Klik link "Ustawienia" (sidebar/topbar) → `/settings`
3. Settings page pokazuje aktualne preferencje:
   - Selected categories (checkboxes pre-checked)
   - Selected parameters (radio pre-selected)
4. User zmienia:
   - Dodaje kategorię "Sztuka i muzea"
   - Zmienia tempo z "Spokojne" na "Umiarkowane"
5. Klik "Zapisz zmiany":
   - `PATCH /api/users/me/preferences`
   - Toast success: "Preferencje zaktualizowane"
   - Cache invalidation (1h cache)
6. **Impact**: Następny AI generation użyje nowych preferencji

### 3.4 Journey 4: Multiple Plans Management

**Scenariusz**: User ma już kilka planów, zarządza nimi

1. User w `/dashboard`
2. Lista planów (20 per page):
   - Plan 1: "Wakacje w Barcelonie" - Zaplanowane
   - Plan 2: "Weekend w Pradze" - Szkic
   - Plan 3: "Roadtrip Italia" - Zrealizowane
3. User używa quick filters:
   - Klik "Szkice" → Lista filtruje (instant, Livewire reactive)
   - Pokazuje tylko Plan 2
4. User klika Plan 2 (szkic) → `/plans/2`
5. Plan details (draft):
   - Header + assumptions
   - Brak AI content
   - CTA: "Generuj plan" (check AI limit)
6. User klika "Generuj plan" → Flow AI generation
7. User wraca do Dashboard → Klik Plan 3 (zrealizowane)
8. Plan 3 details:
   - Status badge: "Zrealizowane"
   - Full content (read-only w MVP)
   - PDF export dostępny

### 3.5 Journey 5: Session Timeout Handling

**Scenariusz**: User pracuje nad planem, sesja bliska wygaśnięcia

1. User w `/plans/create` wypełnia formularz
2. 115 min od login (5 min do timeout)
3. Session timeout modal pojawia się:
   - "Sesja wygaśnie za 5 minut. Kontynuować?"
   - Countdown timer: 5:00, 4:59, 4:58...
4. **Opcja A**: User klika "Tak, przedłuż":
   - `POST /api/auth/refresh-session`
   - Modal znika
   - User kontynuuje pracę
5. **Opcja B**: User ignoruje, countdown dochodzi do 0:
   - Session expired
   - Redirect `/login` z message: "Sesja wygasła. Zaloguj się ponownie."
   - Form data lost (w MVP, post-MVP: localStorage backup)

### 3.6 Journey 6: Error Recovery

**Scenariusz**: AI generation fails, user musi recovery

1. User w `/plans/{id}/generating`
2. Polling status → `failed`
3. Redirect error screen:
   - "Nie udało się wygenerować planu"
   - Error message: "OpenAI API timeout. Spróbuj ponownie."
   - Buttons: "Spróbuj ponownie" / "Wróć do planu"
4. User klika "Spróbuj ponownie":
   - `POST /api/travel-plans/{id}/generate` (nie zużywa limitu - rollback)
   - Redirect `/plans/{id}/generating`
   - Polling ponownie
5. Success tym razem → Redirect `/plans/{id}`

**Alternatywnie**: User klika "Wróć do planu":
- Redirect `/plans/{id}` (draft state)
- User może edytować notatki lub spróbować później

## 4. Układ i struktura nawigacji

### 4.1 Główna Nawigacja (Authenticated Users)

**Desktop (>1024px)**:
```
┌─────────────────────────────────────────────────┐
│ [Sidebar]          │ [Main Content]             │
│                    │                             │
│ Logo VibeTravels   │ Page Content Here          │
│                    │                             │
│ [Dashboard]        │                             │
│ [Profil]           │                             │
│ [Ustawienia]       │                             │
│                    │                             │
│ ┌────────────────┐ │                             │
│ │ AI Generowania │ │                             │
│ │ 3/10           │ │                             │
│ │ [Progress Bar] │ │                             │
│ └────────────────┘ │                             │
│                    │                             │
│ [Wyloguj]          │                             │
└─────────────────────────────────────────────────┘
```

**Sidebar Navigation**:
- **Position**: Fixed left, full height
- **Width**: 240px (collapsed: 64px opcjonalnie post-MVP)
- **Links**:
  - Dashboard (home icon + "Dashboard")
  - Profil (user icon + "Profil")
  - Ustawienia (gear icon + "Ustawienia")
- **AI Counter**: Badge + progress bar (visual feedback)
- **Logout**: Bottom-aligned button
- **Active State**: Background color + bold text
- **Accessibility**: role="navigation", aria-current="page"

**Mobile (<640px)**:
```
┌─────────────────────────────────────┐
│ [☰] VibeTravels        [3/10] 🔔   │ ← Topbar
├─────────────────────────────────────┤
│                                     │
│   Page Content Here                 │
│                                     │
│                                     │
└─────────────────────────────────────┘
```

**Topbar Navigation**:
- **Position**: Fixed top, full width
- **Height**: 56px (touch-friendly)
- **Elements**:
  - Hamburger menu (left, 44x44px)
  - Logo center (opcjonalnie)
  - AI counter badge compact (right)
  - Notification icon (opcjonalnie post-MVP)
- **Hamburger Expand**: Full-screen overlay z navigation links

### 4.2 Breadcrumbs (Opcjonalnie w MVP)

**Desktop**: Subtelne breadcrumbs dla deep navigation
```
Dashboard > Plany > Barcelona Summer Trip
```

**Mobile**: Hidden (oszczędność space), rely on back button + page title

### 4.3 Contextual Navigation

**W Plan Details (`/plans/{id}`)**:
- **Top actions bar**:
  - "← Wróć do Dashboard" (secondary button/link)
  - "Usuń plan" (destructive, right-aligned)
  - "Regeneruj plan" (primary, jeśli applicable)

**W Create Plan (`/plans/create`)**:
- **Sticky footer**:
  - "Zapisz jako szkic" (secondary, left)
  - "Generuj plan" (primary, right)

### 4.4 Modal Navigation

**Modals Override Main Navigation**:
- Session timeout modal: Focus trap, no navigation away
- Rate limit modal: Non-closeable, must wait countdown
- Delete account modal: Focus on "Anuluj" / "Potwierdź"

### 4.5 Error Page Navigation

**404 Not Found**:
- Clear navigation options:
  - "Wróć do Dashboard" (primary)
  - "Stwórz nowy plan" (secondary)
- No dead ends

**AI Generation Error**:
- Clear recovery paths:
  - "Spróbuj ponownie" (primary)
  - "Wróć do planu" (secondary)
  - "Zgłoś problem" (link)

### 4.6 Onboarding Navigation

**Linear Flow** (no skip, no sidebar):
```
Step 1 → Step 2 → Step 3 → Step 4 → Welcome → Dashboard
  ↓        ↓        ↓
[Dalej]  [Dalej]  [Zakończ]
  ↑        ↑
[Wstecz] [Wstecz]
```

- **Progress indicator**: Visual 1/4, 2/4, 3/4, 4/4
- **Wstecz button**: Allowed (back to previous step)
- **Skip**: Not allowed (onboarding mandatory)
- **Keyboard**: Enter = Dalej, Tab navigation

## 5. Kluczowe komponenty

### 5.1 Livewire Full-Page Components

#### 5.1.1 Auth/Register.php
- **Odpowiedzialność**: Rejestracja użytkownika
- **State**: email, password, password_confirmation, isLoading
- **Methods**: register(), registerWithGoogle()
- **Validation**: Livewire rules + real-time dla email uniqueness
- **API Calls**: `POST /api/auth/register`, `GET /api/auth/google`

#### 5.1.2 Auth/Login.php
- **Odpowiedzialność**: Logowanie użytkownika
- **State**: email, password, remember, isLoading, loginAttempts
- **Methods**: login(), loginWithGoogle()
- **Rate Limiting**: Track attempts, show countdown przy 429
- **API Calls**: `POST /api/auth/login`

#### 5.1.3 Onboarding/Step.php
- **Odpowiedzialność**: Dynamic component dla 4 kroków onboarding
- **State**: currentStep, formData (step-specific), canProceed
- **Methods**: nextStep(), previousStep(), submitStep()
- **Validation**: Per-step validation rules
- **API Calls**: `PATCH /api/users/me/onboarding`

#### 5.1.4 Dashboard.php
- **Odpowiedzialność**: Lista planów użytkownika
- **State**: plans (collection), activeFilter, currentPage, isLoading
- **Computed**: filteredPlans (cached 60s)
- **Methods**: filterByStatus(), deletePlan(), refreshPlans()
- **API Calls**: `GET /api/travel-plans?status=&page=`
- **Nested**: TravelPlanCard component per plan

#### 5.1.5 Plans/Create.php
- **Odpowiedzialność**: Formularz tworzenia planu
- **State**: formData (title, destination, dates, etc.), showBudget, isGenerating
- **Methods**: saveDraft(), generatePlan(), toggleBudget()
- **Validation**: Client-side + server-side
- **API Calls**: `POST /api/travel-plans` (z generate_now flag)
- **AI Limit Check**: `GET /api/users/me` przed generation

#### 5.1.6 Plans/Show.php
- **Odpowiedzialność**: Wyświetlenie szczegółów planu
- **State**: plan, days, expandedAssumptions, showFeedback
- **Computed**: No cache (fresh data)
- **Methods**: toggleAssumptions(), exportPDF(), regeneratePlan(), deletePlan()
- **API Calls**: `GET /api/travel-plans/{id}?include=days,days.points,feedback`
- **Nested**: PlanDay, PlanPoint, FeedbackForm components

#### 5.1.7 Plans/Generating.php
- **Odpowiedzialność**: Polling AI generation status
- **State**: planId, status, progress, elapsedTime, errorMessage
- **Methods**: checkStatus() (wire:poll.3s)
- **API Calls**: `GET /api/travel-plans/{id}/generation-status`
- **Redirects**:
  - completed → `/plans/{id}`
  - failed → error screen
  - timeout >120s → error screen

#### 5.1.8 Profile/Show.php
- **Odpowiedzialność**: Profil użytkownika
- **State**: user, isEditing, stats
- **Methods**: edit(), save(), cancelEdit(), deleteAccount()
- **API Calls**: `GET /api/users/me`, `PATCH /api/users/me`, `DELETE /api/users/me`
- **Delete Confirmation**: Modal z input "DELETE"

#### 5.1.9 Settings/Preferences.php
- **Odpowiedzialność**: Edycja preferencji turystycznych
- **State**: preferences, hasUnsavedChanges
- **Methods**: save(), cancel()
- **Validation**: Same rules jak onboarding
- **API Calls**: `GET /api/users/me/preferences`, `PATCH /api/users/me/preferences`
- **Cache Invalidation**: Clear 1h cache on save

### 5.2 Nested Reusable Components

#### 5.2.1 Components/TravelPlanCard.php
- **Props**: plan (object)
- **Odpowiedzialność**: Single plan card w liście
- **Wyświetla**:
  - Miniatura destynacji (opcjonalnie)
  - Tytuł + destynacja
  - Daty (formatted)
  - Status badge (colored)
  - Liczba dni/osób
  - Hover action: "Zobacz szczegóły"
- **Click**: Navigate `/plans/{plan.id}`
- **Responsive**: Full width mobile, grid item desktop

#### 5.2.2 Components/PlanDay.php
- **Props**: day (object), expanded (boolean), isMobile (boolean)
- **Odpowiedzialność**: Single day accordion w planie
- **State**: isExpanded (local toggle)
- **Wyświetla**:
  - Header: "Dzień X - DD.MM.YYYY" + expand icon
  - Content (gdy expanded): PlanPoint components
- **Methods**: toggleExpand()
- **Nested**: PlanPoint per point in day
- **Accessibility**: aria-expanded, role="button"

#### 5.2.3 Components/PlanPoint.php
- **Props**: point (object)
- **Odpowiedzialność**: Single point expandable card
- **State**: isExpanded (local toggle)
- **Wyświetla**:
  - Collapsed: nazwa + ikona pory dnia + czas
  - Expanded: opis + uzasadnienie + czas wizyty + Google Maps link
- **Methods**: toggleExpand()
- **Click Anywhere**: Toggle expand
- **Accessibility**: Keyboard Enter = toggle

#### 5.2.4 Components/FeedbackForm.php
- **Props**: planId
- **Odpowiedzialność**: Inline feedback form w plan footer
- **State**: isExpanded, satisfied, issues, otherComment
- **Methods**: toggle(), submit()
- **Conditional Logic**: Jeśli satisfied=false → show checkboxes
- **API Calls**: `POST /api/travel-plans/{id}/feedback`
- **Validation**: satisfied required, issues required if satisfied=false
- **Success**: Toast + collapse form + show submitted feedback

### 5.3 Shared Utility Components

#### 5.3.1 Components/Notifications.php
- **Odpowiedzialność**: Global toast system (Wire UI)
- **Methods**: success(), error(), warning(), info()
- **Config**:
  - Position: top-right desktop, top-center mobile
  - Auto-dismiss: 5s
  - Max stack: 3
- **Usage**: `$this->dispatch('notify', type: 'success', message: '...')`

#### 5.3.2 Components/SessionTimeout.php
- **Odpowiedzialność**: Monitor session + show warning modal
- **State**: showWarning, countdown (seconds)
- **Methods**: checkSession() (wire:poll.60s), extendSession()
- **Trigger**: Gdy session <5 min do expiry
- **Modal**: Non-dismissible, countdown timer, "Przedłuż" button
- **API Calls**: `POST /api/auth/refresh-session` (custom endpoint)

#### 5.3.3 Components/PasswordStrength.php
- **Odpowiedzialność**: Password strength indicator (Alpine.js)
- **Props**: password (x-model)
- **Display**:
  - Progress bar (weak/medium/strong)
  - Color coding: red/orange/green
  - Text label: "Słabe" / "Średnie" / "Silne"
- **Logic**: Local Alpine.js (no backend calls)
- **Criteria**: Length, mixed case, numbers, special chars

#### 5.3.4 Components/SkeletonLoader.php
- **Odpowiedzialność**: Loading placeholder
- **Variants**: PlanCard, PlanDay, Profile
- **Props**: type (string), count (int)
- **Display**: Shimmer animation, gray boxes struktura
- **Usage**: Podczas fetch API data
- **Accessibility**: aria-busy="true"

#### 5.3.5 Components/EmailVerificationBanner.php
- **Odpowiedzialność**: Persistent banner dla niezweryfikowanego emaila
- **State**: isVisible (computed from user.email_verified_at)
- **Display**:
  - Sticky top banner (żółty background)
  - "Twój email nie jest zweryfikowany"
  - Link: "Wyślij ponownie"
  - Rate limit countdown jeśli recently sent
- **API Calls**: `POST /api/auth/resend-verification`
- **Reactive**: Znika gdy email verified (Livewire poll lub event)

#### 5.3.6 Components/Sidebar.php (Desktop)
- **Odpowiedzialność**: Left sidebar navigation
- **State**: user (computed), aiLimit (computed, cached 1h)
- **Display**:
  - Logo + brand
  - Navigation links (active highlighting)
  - AI counter badge + progress bar
  - Logout button
- **API Calls**: `GET /api/users/me` (dla AI limit)
- **Accessibility**: role="navigation", aria-current

#### 5.3.7 Components/Topbar.php (Mobile)
- **Odpowiedzialność**: Top mobile navigation
- **State**: menuOpen (boolean)
- **Display**:
  - Hamburger button (44x44px)
  - Logo center
  - AI counter compact
- **Menu Expand**: Full-screen overlay z links
- **Accessibility**: Focus trap w expanded menu, aria-expanded

### 5.4 Alpine.js Micro-Components

**ProgressiveDisclosure** (Budget toggle w Create Plan):
```html
<div x-data="{ open: false }">
  <button @click="open = !open">Dodaj budżet ▼</button>
  <div x-show="open" x-collapse>
    <!-- Budget fields -->
  </div>
</div>
```

**Accordion** (Plan Days):
```html
<div x-data="{ expanded: @js($isFirst) }">
  <button @click="expanded = !expanded" aria-expanded="expanded">
    Dzień 1 - 15.07.2025
  </button>
  <div x-show="expanded" x-collapse>
    <!-- Day content -->
  </div>
</div>
```

**ExpandableCard** (Plan Points):
```html
<div x-data="{ open: false }" @click="open = !open" class="cursor-pointer">
  <div x-show="!open">Collapsed view</div>
  <div x-show="open" x-collapse>Expanded view</div>
</div>
```

**PasswordToggle** (Show/Hide password):
```html
<div x-data="{ show: false }">
  <input :type="show ? 'text' : 'password'" />
  <button @click="show = !show">
    <span x-text="show ? 'Ukryj' : 'Pokaż'"></span>
  </button>
</div>
```

### 5.5 Wire UI Components (Wykorzystane)

- **Buttons**: Primary, Secondary, Destructive variants
- **Inputs**: Text, Number, Date (native + enhancement)
- **Select**: Single-select dla radio groups
- **Checkbox**: Multi-select dla kategorii
- **Modal**: Confirmation dialogs (delete account, regenerate plan)
- **Notifications**: Toast system (success/error/warning/info)
- **Badge**: Status badges, AI counter
- **Progress Bar**: Onboarding progress, AI counter visual
- **Pagination**: Dashboard plan list
- **Loading**: Spinner component dla buttons

### 5.6 Component Communication

**Parent → Child** (Props):
```php
// Dashboard → TravelPlanCard
<livewire:components.travel-plan-card :plan="$plan" :key="$plan->id" />
```

**Child → Parent** (Events):
```php
// TravelPlanCard dispatch event
$this->dispatch('plan-deleted', planId: $this->plan->id);

// Dashboard listen
#[On('plan-deleted')]
public function handlePlanDeleted($planId) {
    $this->refreshPlans();
}
```

**Global Events** (Toast notifications):
```php
// Any component
$this->dispatch('notify', [
    'type' => 'success',
    'message' => 'Plan utworzony!'
]);
```

**Livewire Polling** (AI generation status):
```html
<!-- Plans/Generating component -->
<div wire:poll.3s="checkStatus">
    <!-- Loading UI -->
</div>
```

## 6. Mapowanie Wymagań PRD → Elementy UI

### 6.1 System Autentykacji (PRD 3.1)

| Wymaganie PRD | Element UI | Lokalizacja |
|---------------|------------|-------------|
| Rejestracja email+hasło | Register form | `/register` |
| Logowanie email+hasło | Login form | `/login` |
| Google OAuth | "Sign in with Google" button | `/register`, `/login` |
| Weryfikacja email | Email verification banner + resend link | Global (authenticated pages) |
| Hashowanie haseł | Password strength indicator | `/register` |
| Wylogowanie | Logout button | Sidebar/Topbar |
| Usunięcie konta | Delete account w Settings | `/profile` (expandable) |

### 6.2 Onboarding (PRD 3.2)

| Wymaganie PRD | Element UI | Lokalizacja |
|---------------|------------|-------------|
| Obowiązkowy proces | Forced flow (no skip) | `/onboarding` |
| Ekran powitalny | Welcome message Step 1 | `/onboarding` step 1 |
| Dane podstawowe | Nick + Home location fields | `/onboarding` step 1 |
| Kategorie zainteresowań | Checkbox grid (7 opcji) | `/onboarding` step 2 |
| Parametry praktyczne | Radio groups (4 parametry) | `/onboarding` step 3 |
| Tracking completion | Progress indicator 1/4→4/4 | `/onboarding` (global) |

### 6.3 Profil Użytkownika (PRD 3.3)

| Wymaganie PRD | Element UI | Lokalizacja |
|---------------|------------|-------------|
| Wyświetlanie profilu | Profile view | `/profile` |
| Edycja danych | Edit mode (inline lub modal) | `/profile` |
| Zarządzanie preferencjami | Preferences form | `/settings` |
| Dostęp z dashboard | Sidebar/Topbar link "Profil" | Global (authenticated) |
| Tracking wypełnienia | Stats display: "Zużyłeś X/10" | `/profile` |

### 6.4 Dashboard (PRD 3.4)

| Wymaganie PRD | Element UI | Lokalizacja |
|---------------|------------|-------------|
| Hero section | "Cześć [Nick]! Zaplanuj..." | `/dashboard` top |
| CTA "Stwórz nowy plan" | Primary button | `/dashboard` hero |
| Lista planów | TravelPlanCard grid | `/dashboard` main |
| Quick filters | Filter buttons (reactive) | `/dashboard` above list |
| Sidebar navigation | Sidebar component | Global (desktop) |
| Licznik limitów AI | Badge + progress bar | Sidebar/Topbar |

### 6.5 Tworzenie Planu (PRD 3.5)

| Wymaganie PRD | Element UI | Lokalizacja |
|---------------|------------|-------------|
| Formularz tworzenia | Create plan form | `/plans/create` |
| Pola required | Title, Destination, Date, Days, People | Form top section |
| Pola optional | Budget (collapsed) | Form middle (progressive disclosure) |
| User notes | Textarea + helper tooltip | Form bottom |
| "Generuj plan" | Primary button (check limit) | Sticky footer |
| "Zapisz jako szkic" | Secondary button | Sticky footer |
| Walidacja | Inline errors + toast | Per field + global |

### 6.6 Generowanie AI (PRD 3.6)

| Wymaganie PRD | Element UI | Lokalizacja |
|---------------|------------|-------------|
| System limitów 10/miesiąc | AI counter badge | Sidebar/Topbar |
| Sprawdzanie limitu | Disabled button + tooltip | `/plans/create` jeśli 10/10 |
| Loading state | Spinner + progress + message | `/plans/{id}/generating` |
| Proces generowania | Polling co 3s | `/plans/{id}/generating` |
| Tracking metadanych | (Backend, nie UI) | - |
| Obsługa błędów | Error screen z retry | Error page |
| Po generowaniu | Redirect + feedback form | `/plans/{id}` |

### 6.7 Wygenerowany Plan (PRD 3.7)

| Wymaganie PRD | Element UI | Lokalizacja |
|---------------|------------|-------------|
| Header planu | Title, destination, dates, people, budget, status | `/plans/{id}` top |
| "Twoje założenia" | Collapsible section | `/plans/{id}` below header |
| Plan dzień po dniu | Accordion PlanDay cards | `/plans/{id}` main |
| Punkty planu | Expandable PlanPoint cards | Nested w PlanDay |
| Footer feedback | Inline FeedbackForm | `/plans/{id}` footer |
| Export PDF | "Export do PDF" button | `/plans/{id}` footer |
| Regeneruj plan | "Regeneruj plan" button + warning | `/plans/{id}` footer |

### 6.8 Feedback (PRD 3.10)

| Wymaganie PRD | Element UI | Lokalizacja |
|---------------|------------|-------------|
| Pytanie podstawowe | "Czy plan spełnia oczekiwania?" + Tak/Nie | `/plans/{id}` footer (collapsed) |
| Przy "nie" checkboxes | 4 opcje problemów | Conditional show |
| Zapisywanie feedbacku | Submit button + toast | FeedbackForm component |
| Feedback opcjonalny | Możliwość pominięcia | Collapsible (nie force) |

### 6.9 Export PDF (PRD 3.11)

| Wymaganie PRD | Element UI | Lokalizacja |
|---------------|------------|-------------|
| Przycisk export | "Export do PDF" button | `/plans/{id}` footer |
| Loading state | Button disabled + spinner + toast | During generation |
| Download PDF | Browser download trigger | After generation <10s |
| Tracking eksportów | (Backend, nie UI) | - |

### 6.10 Email Notifications (PRD 3.12)

| Wymaganie PRD | Element UI | Lokalizacja |
|---------------|------------|-------------|
| Email weryfikacyjny | Verification banner + resend link | Global (authenticated, unverified) |
| Welcome email | (Email, nie UI) | - |
| Powiadomienie limitu | (Email, nie UI) | - |
| Przypomnienie przed wycieczką | (Email, nie UI) | - |

## 7. User Pain Points → UI Solutions

### Pain Point 1: "Trudność w przekształceniu luźnych pomysłów w konkretny plan"

**UI Solution**:
- **Duża textarea** "Twoje pomysły i notatki" w formularzu tworzenia
- **Helper tooltip**: "Im więcej szczegółów, tym lepszy plan!" (subtle, dismissible)
- **Progressive disclosure**: Optional budget collapsed (nie przytłacza)
- **Clear labels**: "Destynacja", "Liczba dni" (konkretne pytania)
- **AI generation**: One-click "Generuj plan" przekształca notatki w structured plan

### Pain Point 2: "Brak spersonalizowanych rekomendacji"

**UI Solution**:
- **Obowiązkowy onboarding**: Zbiera preferencje (kategorie + parametry)
- **Visual preference selection**: Checkbox grid z ikonami (easy to understand)
- **Editable preferences**: Link "Ustawienia" zawsze dostępny
- **Uzasadnienia w planie**: Każdy punkt pokazuje "Pasuje do Twoich zainteresowań: Historia i kultura"
- **Feedback loop**: Form feedbacku pomaga improve future generations

### Pain Point 3: "Konieczność przeszukiwania wielu źródeł"

**UI Solution**:
- **All-in-one plan view**: Wszystkie punkty w jednym miejscu
- **Google Maps links**: Direct links do każdego miejsca (no manual search)
- **Grouped by day part**: Rano/Południe/Popołudnie/Wieczór (clear structure)
- **Czas wizyty**: Każdy punkt pokazuje orientacyjny czas (easy planning)
- **PDF export**: Take-away format (offline access w podróży)

### Pain Point 4: "Czasochłonny research"

**UI Solution**:
- **AI automation**: Generowanie 30-120s vs. hours manual research
- **Loading feedback**: Progress bar + messages (nie zostawia w niepewności)
- **Retry on failure**: Jeśli AI fails, easy retry (nie zużywa limitu)
- **Save as draft**: Możliwość zapisania i dokończenia później
- **Quick filters**: Easy znajdowanie planów (Szkice/Zaplanowane/Zrealizowane)

### Pain Point 5: "Brak narzędzia łączącego kreatywność z praktyką"

**UI Solution**:
- **User notes + AI**: Kreatywne pomysły + praktyczny structured plan
- **Parametry praktyczne**: Tempo/Budżet/Transport (practical constraints)
- **Accordion days**: Expandable structure (overview + details)
- **Regeneration**: Nie zadowolony? Regeneruj z jednym klikiem
- **Gamification**: Confetti dla pierwszego planu (celebrate achievement)

## 8. Accessibility & Security Features

### 8.1 WCAG 2.1 Level AA Compliance

**Contrast Ratios**:
- Tekst normalny: 4.5:1 (sprawdzić w Tailwind config)
- Duży tekst (18px+): 3:1
- Interactive elements borders: 3:1

**Keyboard Navigation**:
- **Tab order**: Logiczny top→bottom, left→right
- **Focus visible**: Tailwind `focus:ring-2 focus:ring-primary`
- **Skip links**: "Przejdź do głównej treści" dla screen readers
- **Enter submit**: Forms submit na Enter
- **Escape close**: Modals close na Escape (gdzie applicable)

**ARIA Landmarks**:
- `role="navigation"` dla Sidebar/Topbar
- `role="main"` dla głównej treści
- `role="complementary"` dla sidebars
- `role="progressbar"` dla onboarding progress
- `aria-live="polite"` dla toast notifications
- `aria-expanded` dla accordions/collapsible

**Screen Reader Support**:
- `aria-label` dla icon-only buttons
- `<label>` dla wszystkich form inputs
- `aria-describedby` dla field help texts
- `aria-invalid` dla validation errors
- `.sr-only` classes dla screen-reader-only content

**Touch Targets**:
- Minimum 44x44px (WCAG guideline)
- Primary CTA: `py-3 px-6` (large, prominent)
- Secondary buttons: `py-2 px-4`
- Icon buttons: min 44px square

### 8.2 Security Features UI

**CSRF Protection**:
- Livewire automatic CSRF token injection (nie visible w UI)
- All form submissions protected

**XSS Prevention**:
- Blade `{{ }}` auto-escaping (user content safe)
- User notes sanitized przed display
- No `{!! !!}` raw output bez explicit sanitization

**Rate Limiting Feedback**:
- **Login failures**: Disabled form + countdown "Spróbuj za 120s"
- **AI generation limit**: Disabled button + tooltip
- **Email resend**: Disabled link + countdown
- **Modal blokujący**: 429 response → full-screen modal z countdown

**Session Management**:
- **Timeout warning**: Modal 5 min przed expiry
- **Countdown timer**: Live feedback (5:00, 4:59...)
- **Extend option**: "Tak, przedłuż sesję" button
- **Auto-logout**: Redirect login po expiry

**Sensitive Data**:
- **Password fields**: `type="password"` (nie visible)
- **Email masking**: `u***@example.com` w niektórych views (opcjonalnie)
- **Delete confirmation**: Input "DELETE" dla account deletion

**HTTPS Enforcement**:
- Wymuszony na backend (nie UI responsibility)
- Secure cookies (HTTP-only, secure flags)

## 9. Performance Optimizations UI

### 9.1 Loading Strategies

**Skeleton Loaders**:
- Dashboard plan list (3 cards shimmer)
- Plan details loading (day structure visible)
- Profile loading
- Better UX niż blank screen + spinner

**Lazy Loading**:
- Plan days: First 3 loaded, rest on scroll lub "Pokaż więcej"
- Images (miniatures): Native lazy loading `loading="lazy"`
- Livewire components: `wire:lazy` dla heavy components

**Pagination**:
- Dashboard: 20 plans per page (Wire UI pagination)
- Scroll to top on page change
- Preserve filter state w query params

### 9.2 Caching Strategy

**Livewire Computed Properties**:
```php
// Dashboard
#[Computed(cache: true, seconds: 60)]
public function plans() {
    return TravelPlan::where('user_id', auth()->id())
        ->orderBy('created_at', 'desc')
        ->paginate(20);
}
```

**Cache Invalidation**:
- Plan create/delete → invalidate dashboard cache
- Preferences update → invalidate preferences cache (1h)
- AI generation complete → invalidate AI counter cache

**No Cache**:
- Plan details (zawsze fresh: `/plans/{id}`)
- AI generation status (polling real-time)

### 9.3 Optimistic vs Pesimistic UI

**Pesimistic** (MVP default):
- Wait for API response
- Show loading state (spinner/disabled)
- Clear success/error feedback
- Simpler implementation, fewer edge cases

**Optimistic** (exceptions):
- Filter toggle: Instant UI update (Livewire reactive, no API call)
- Accordion expand: Local state (no backend)
- Modal open/close: Client-side

### 9.4 Asset Optimization

**Tailwind CSS**:
- Purge unused styles (production)
- JIT mode (fast builds)
- Minimal custom CSS

**JavaScript**:
- Livewire + Alpine.js (minimal bundle)
- No heavy frameworks (React/Vue)
- Wire UI components (pre-optimized)

**Images**:
- Lazy loading native
- Responsive images (srcset opcjonalnie post-MVP)
- Compressed formats (WebP opcjonalnie)

## 10. Responsive Behavior Matrix

| Widok | Mobile (<640px) | Tablet (640-1024px) | Desktop (>1024px) |
|-------|-----------------|---------------------|-------------------|
| **Landing** | Single col, stacked CTA | 2 col features | 3 col features, hero side-by-side |
| **Register/Login** | Full width form, stack buttons | Centered card (max-w-md) | Centered card + illustration |
| **Onboarding** | Full screen, single col | Full screen, single col | Full screen, max-w-2xl center |
| **Dashboard** | Single col cards, hamburger menu | 2 col grid, topbar | 3 col grid, sidebar |
| **Create Plan** | Stacked fields, sticky footer | Centered form (max-w-2xl) | Centered form, sidebar visible |
| **Plan Details** | Accordion collapsed, stack | Accordion first open, 2 col header | Sidebar, accordion, 3 col header |
| **Profile** | Single col, stack sections | 2 col layout | Sidebar + 2 col content |
| **Settings** | Single col checkboxes | 2 col checkboxes (interests) | 3 col checkboxes, sidebar |

**Key Responsive Patterns**:
- **Navigation**: Hamburger mobile → Sidebar desktop
- **Grids**: 1 col → 2 col → 3 col progression
- **Forms**: Full width mobile → Centered card desktop
- **Accordions**: Collapsed mobile → First open desktop
- **Sticky elements**: Footer mobile → Sidebar desktop

## 11. Nierozwiązane Kwestie (Wymaga Decyzji)

### 11.1 Design Assets
- [ ] **Brand color palette**: Dokładny hex code dla primary (niebieski/turkusowy)
- [ ] **Illustrations**: Źródło (unDraw, Streamline, custom?) dla empty states
- [ ] **Logo**: Custom logo czy placeholder w MVP?

### 11.2 Localization
- [ ] **Język MVP**: Polski czy angielski? (CRITICAL DECISION)
- [ ] **i18n System**: Laravel localization system czy hardcoded strings?
- [ ] **Tone of voice**: Formal (Pani/Pan) czy informal (Ty)?

### 11.3 Analytics
- [ ] **Tool**: Plausible, custom DB tracking, czy Google Analytics exempt?
- [ ] **Privacy**: Jak trackować bez cookies? (PRD: "brak tracking cookies")

### 11.4 Performance Budgets
- [ ] **FCP Target**: <1.5s na mobile 3G?
- [ ] **TTI Target**: <3s?
- [ ] **Dashboard SSR**: Ile plan cards renderować initial load?

### 11.5 Browser Support
- [ ] **Min versions**: Last 2 versions Safari/Chrome/Firefox/Edge?
- [ ] **Mobile devices**: Testing matrix (iPhone 12+, Samsung S20+)?

### 11.6 Testing Strategy
- [ ] **Accessibility audit**: Lighthouse + axe DevTools wystarczy czy manual testing?
- [ ] **Screen readers**: NVDA/JAWS/VoiceOver testing alokować czas?
- [ ] **E2E**: Dusk czy tylko HTTP feature tests?

---

**Dokument Version**: 1.0
**Data**: 2025-01-10
**Status**: Ready for Development Planning
