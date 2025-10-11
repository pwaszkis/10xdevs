# Przewodnik implementacji: Formularz tworzenia/edycji planu wycieczki

## 1. Opis usługi

### 1.1 Przegląd funkcjonalności

Formularz tworzenia/edycji planu wycieczki jest kluczowym komponentem aplikacji VibeTravels, umożliwiającym użytkownikom:

1. **Wprowadzenie podstawowych danych wycieczki**
   - Tytuł planu (wymagane)
   - Destynacja (wymagane)
   - Data wyjazdu (wymagane, tylko przyszłe daty)
   - Liczba dni (wymagane, zakres 1-30)
   - Liczba osób (wymagane, zakres 1-10)
   - Szacunkowy budżet na osobę (opcjonalne, z wyborem waluty)
   - Pomysły i notatki (opcjonalne, pole tekstowe)

2. **Zapisanie planu jako szkicu**
   - Zapis danych bez generowania AI
   - Status planu: 'draft'
   - Możliwość późniejszego wygenerowania

3. **Wygenerowanie szczegółowego planu AI**
   - Wywołanie OpenAI API (GPT-4o-mini)
   - Wykorzystanie preferencji użytkownika
   - Sprawdzanie limitów generowań (10/miesiąc)
   - Asynchroniczne przetwarzanie przez kolejkę
   - Automatyczna zmiana statusu na 'planned'

### 1.2 Kontekst techniczny

**Stack:**
- Laravel 11 + Livewire 3 (reaktywne komponenty)
- Alpine.js (minimalne interakcje UI)
- Wire UI (gotowe komponenty formularzy)
- Tailwind CSS 4 (styling)
- MySQL 8 (baza danych)
- Redis (kolejka zadań)
- OpenAI API (generowanie planów)

**Wzorce architektoniczne:**
- Service Layer Pattern (separacja logiki biznesowej)
- Repository Pattern (opcjonalnie, dla złożonych zapytań)
- Queue Jobs (asynchroniczne przetwarzanie AI)
- Events & Listeners (powiadomienia, tracking)
- Policy-based Authorization (zabezpieczenie dostępu)

### 1.3 Miejsce w architekturze aplikacji

```
User Dashboard
    ↓
CreatePlanForm (Livewire Component)
    ├→ TravelService (logika CRUD)
    ├→ LimitService (sprawdzanie limitów)
    ├→ PreferenceService (pobieranie preferencji)
    └→ GenerateTravelPlanJob (kolejka)
           ↓
       AIGenerationService
           ↓
       OpenAI API (GPT-4o-mini)
```

---

## 2. Opis konstruktora

### 2.1 Komponent Livewire: `CreatePlanForm`

Główny komponent zarządzający formularzem tworzenia/edycji planu.

**Konstruktor:**
```php
<?php

namespace App\Livewire\Plans;

use Livewire\Component;
use Livewire\Attributes\Rule;
use App\Services\TravelService;
use App\Services\LimitService;
use App\Services\PreferenceService;
use App\Services\AIGenerationService;
use Illuminate\Support\Facades\Auth;

class CreatePlanForm extends Component
{
    // Wstrzykiwanie zależności przez metody
    protected TravelService $travelService;
    protected LimitService $limitService;
    protected PreferenceService $preferenceService;

    public function boot(
        TravelService $travelService,
        LimitService $limitService,
        PreferenceService $preferenceService
    ) {
        $this->travelService = $travelService;
        $this->limitService = $limitService;
        $this->preferenceService = $preferenceService;
    }

    // Publiczne właściwości (stan komponentu) - patrz sekcja 3
    // Metody publiczne (akcje użytkownika) - patrz sekcja 3
    // Metody prywatne (logika wewnętrzna) - patrz sekcja 4
}
```

**Parametry konstruktora (dependency injection):**

1. **TravelService** - serwis do operacji CRUD na planach
   - Cel: Separacja logiki biznesowej od komponentu UI
   - Odpowiedzialności: tworzenie, aktualizacja, usuwanie planów

2. **LimitService** - serwis zarządzania limitami generowań
   - Cel: Sprawdzanie i aktualizacja limitów miesięcznych
   - Odpowiedzialności: pobieranie liczby generowań, sprawdzanie dostępności, inkrementacja

3. **PreferenceService** - serwis preferencji użytkownika
   - Cel: Pobieranie ustawień użytkownika dla generowania AI
   - Odpowiedzialności: zwracanie kategorii zainteresowań, parametrów praktycznych

### 2.2 Inicjalizacja stanu komponentu

**Metoda mount() - inicjalizacja przy pierwszym renderowaniu:**

```php
public ?int $travelId = null; // ID planu przy edycji (null dla nowego)
public $editMode = false; // Tryb edycji vs tworzenie

public function mount(?int $travelId = null)
{
    $this->travelId = $travelId;
    $this->editMode = !is_null($travelId);

    // Sprawdzanie limitów przy tworzeniu nowego planu
    if (!$this->editMode) {
        $this->checkUserLimit();
    }

    // Ładowanie danych przy edycji
    if ($this->editMode) {
        $this->loadTravelData();
    }
}
```

**Uzasadnienie podejścia:**
- Mount wywoływane tylko raz przy inicjalizacji komponentu
- Unikamy zbędnych zapytań do bazy przy każdym renderze
- Jasne rozróżnienie trybu tworzenia vs edycji
- Wczesne sprawdzenie limitów = lepsza UX (komunikat od razu)

---

## 3. Publiczne metody i pola

### 3.1 Publiczne właściwości (Livewire properties)

Wszystkie właściwości publiczne są automatycznie dostępne w widoku Blade i synchronizowane z frontendem.

```php
// === DANE FORMULARZA (wire:model) ===

#[Rule('required|string|max:255')]
public string $title = '';

#[Rule('required|string|max:255')]
public string $destination = '';

#[Rule('required|date|after:today')]
public ?string $start_date = null;

#[Rule('required|integer|min:1|max:30')]
public int $days_count = 7; // Wartość domyślna: 7 dni

#[Rule('required|integer|min:1|max:10')]
public int $people_count = 2; // Wartość domyślna: 2 osoby

#[Rule('nullable|numeric|min:0')]
public ?float $budget_per_person = null;

#[Rule('nullable|string|max:5000')]
public ?string $notes = null;

// === STAN UI ===

public bool $isGenerating = false; // Loading state podczas generowania
public bool $canGenerate = true; // Czy użytkownik może generować (limity)
public int $generationsUsed = 0; // Liczba wykorzystanych generowań
public int $generationsLimit = 10; // Limit miesięczny
public array $currencies = ['PLN', 'EUR', 'USD', 'GBP']; // Opcje walut
public string $selectedCurrency = 'PLN';

// === KOMUNIKATY ===

public ?string $successMessage = null;
public ?string $errorMessage = null;
```

**Wyjaśnienie atrybutów:**

1. **#[Rule(...)]** - Livewire 3 attributes dla walidacji
   - Walidacja uruchamiana automatycznie przed submit
   - Komunikaty błędów dostępne w widoku przez `@error`
   - Real-time validation możliwa przez `wire:model.live`

2. **Typy właściwości**
   - Silne typowanie poprawia bezpieczeństwo i czytelność
   - `?type` = nullable (budżet, notatki opcjonalne)
   - Wartości domyślne ustawiają początkowy stan formularza

### 3.2 Metoda: `saveAsDraft()`

**Cel:** Zapisanie planu jako szkicu bez generowania AI

```php
/**
 * Zapisuje plan jako szkic (status: draft)
 * Nie zużywa limitu generowań
 *
 * @return void
 */
public function saveAsDraft(): void
{
    // 1. Walidacja danych formularza
    $validated = $this->validate();

    try {
        // 2. Przygotowanie danych do zapisu
        $planData = $this->preparePlanData($validated, status: 'draft');

        // 3. Zapis lub aktualizacja w bazie
        $travel = $this->editMode
            ? $this->travelService->update($this->travelId, $planData)
            : $this->travelService->create($planData);

        // 4. Komunikat sukcesu
        $this->successMessage = $this->editMode
            ? 'Plan został zaktualizowany jako szkic.'
            : 'Plan został zapisany jako szkic.';

        // 5. Przekierowanie do dashboard po 1.5s
        $this->dispatch('plan-saved', travelId: $travel->id);

        // Opcjonalnie: redirect po opóźnieniu
        $this->redirectRoute('dashboard', navigate: true);

    } catch (\Exception $e) {
        // Obsługa błędów zapisu
        $this->handleSaveError($e);
    }
}
```

**Szczegóły implementacyjne:**

1. **Walidacja** - `$this->validate()` wykorzystuje atrybuty #[Rule]
2. **preparePlanData()** - metoda prywatna (sekcja 4.1)
3. **TravelService** - różne metody dla create/update
4. **Livewire events** - `dispatch()` do komunikacji z innymi komponentami
5. **navigate: true** - SPA-like transitions w Livewire 3

**Zwracane wartości:**
- Void (brak return), operacje side-effect (zapis, redirect)

**Wyjątki:**
- `ValidationException` - automatycznie obsługiwane przez Livewire
- `Exception` - generyczne błędy zapisu (catch block)

### 3.3 Metoda: `generatePlan()`

**Cel:** Wygenerowanie szczegółowego planu AI

```php
/**
 * Generuje szczegółowy plan wycieczki przy użyciu AI
 * Sprawdza limity, wysyła do kolejki, aktualizuje status
 *
 * @return void
 */
public function generatePlan(): void
{
    // 1. Walidacja danych formularza
    $validated = $this->validate();

    // 2. Sprawdzenie limitów generowań
    if (!$this->canGeneratePlan()) {
        $this->errorMessage = $this->getLimitExceededMessage();
        return;
    }

    try {
        // 3. Rozpoczęcie loading state
        $this->isGenerating = true;
        $this->errorMessage = null;

        // 4. Przygotowanie danych planu
        $planData = $this->preparePlanData($validated, status: 'generating');

        // 5. Zapis lub aktualizacja planu
        $travel = $this->editMode
            ? $this->travelService->update($this->travelId, $planData)
            : $this->travelService->create($planData);

        // 6. Inkrementacja licznika generowań
        $this->limitService->incrementGenerationCount(Auth::id());

        // 7. Pobranie preferencji użytkownika
        $userPreferences = $this->preferenceService->getUserPreferences(Auth::id());

        // 8. Wysłanie do kolejki (asynchroniczne)
        \App\Jobs\GenerateTravelPlanJob::dispatch($travel, $userPreferences)
            ->onQueue('ai-generation');

        // 9. Komunikat dla użytkownika
        $this->successMessage = 'Generowanie planu rozpoczęte. Zajmie to około 30 sekund...';

        // 10. Przekierowanie do widoku oczekiwania/planu
        $this->redirectRoute('plans.show', ['travel' => $travel->id], navigate: true);

    } catch (\App\Exceptions\LimitExceededException $e) {
        $this->handleLimitError($e);
    } catch (\Exception $e) {
        $this->handleGenerationError($e);
    } finally {
        $this->isGenerating = false;
    }
}
```

**Szczegóły implementacyjne:**

1. **canGeneratePlan()** - sprawdzanie limitów (sekcja 4.2)
2. **Status 'generating'** - pośredni status przed 'planned'
3. **Inkrementacja PRZED wysłaniem do kolejki** - zapobiega race conditions
4. **Preferencje użytkownika** - pobierane z profilu (kategorie, parametry)
5. **Queue 'ai-generation'** - dedykowana kolejka dla AI (można skalować niezależnie)
6. **Finally block** - zawsze wyłącza loading state

**Wyjątki:**
- `LimitExceededException` - niestandardowy wyjątek przy przekroczeniu limitu
- `Exception` - generyczne błędy (API down, brak połączenia z Redis)

**Flow generowania:**
```
generatePlan()
  → Walidacja
  → Sprawdzenie limitów
  → Zapis do DB (status: generating)
  → Inkrementacja licznika
  → Wysłanie do Queue
  → Redirect do widoku planu

[Asynchronicznie w tle]
GenerateTravelPlanJob
  → Wywołanie AIGenerationService
  → Request do OpenAI API
  → Przetworzenie odpowiedzi
  → Aktualizacja DB (status: planned, generated_plan)
  → Event PlanGenerated
  → Notification użytkownika (opcjonalnie)
```

### 3.4 Metoda: `updated($property)`

**Cel:** Real-time reakcje na zmiany wartości formularza

```php
/**
 * Hook Livewire wywoływany przy zmianie właściwości
 * Używany do real-time validation i dynamic UX
 *
 * @param string $property Nazwa zmienionej właściwości
 * @return void
 */
public function updated($property): void
{
    // Walidacja konkretnego pola po jego zmianie
    $this->validateOnly($property);

    // Logika specyficzna dla pól
    match($property) {
        'start_date', 'days_count' => $this->calculateEndDate(),
        'budget_per_person', 'people_count' => $this->calculateTotalBudget(),
        default => null
    };
}
```

**Przykłady użycia:**

1. **Real-time validation**
   - Użytkownik opuszcza pole (blur)
   - Natychmiastowe wyświetlenie błędu walidacji
   - Lepsze UX niż czekanie na submit

2. **Dynamiczne kalkulacje**
   - `calculateEndDate()` - wyświetlenie daty zakończenia na podstawie start_date + days_count
   - `calculateTotalBudget()` - pokazanie całkowitego budżetu (budget * people_count)

3. **Conditional logic**
   - Pokazanie/ukrycie pól na podstawie wartości innych pól
   - Aktualizacja dostępnych opcji (np. waluty w zależności od destynacji)

### 3.5 Metoda: `render()`

**Cel:** Renderowanie widoku komponentu

```php
/**
 * Renderuje widok formularza
 *
 * @return \Illuminate\View\View
 */
public function render(): \Illuminate\View\View
{
    return view('livewire.plans.create-plan-form', [
        'limitInfo' => $this->getLimitInfo(),
        'endDate' => $this->calculateEndDate(),
        'totalBudget' => $this->calculateTotalBudget(),
    ]);
}
```

**Dane przekazywane do widoku:**

1. **limitInfo** - array z informacjami o limitach
   ```php
   [
       'used' => 7,
       'limit' => 10,
       'remaining' => 3,
       'percentage' => 70,
       'canGenerate' => true,
       'resetDate' => '2025-11-01'
   ]
   ```

2. **endDate** - obliczona data zakończenia (Carbon instance)
3. **totalBudget** - całkowity budżet wycieczki

---

## 4. Prywatne metody i pola

### 4.1 Metoda: `preparePlanData()`

**Cel:** Przygotowanie danych do zapisu w bazie

```php
/**
 * Przygotowuje dane planu do zapisu w bazie danych
 * Dodaje user_id, status, metadata
 *
 * @param array $validated Zwalidowane dane formularza
 * @param string $status Status planu ('draft', 'generating', 'planned')
 * @return array
 */
private function preparePlanData(array $validated, string $status = 'draft'): array
{
    return [
        'user_id' => Auth::id(),
        'title' => $validated['title'],
        'destination' => $validated['destination'],
        'start_date' => $validated['start_date'],
        'days_count' => $validated['days_count'],
        'people_count' => $validated['people_count'],
        'budget_per_person' => $validated['budget_per_person'],
        'budget_currency' => $this->selectedCurrency,
        'notes' => $validated['notes'],
        'status' => $status,
        'metadata' => [
            'created_from' => 'web',
            'user_agent' => request()->userAgent(),
            'preferences_snapshot' => $this->preferenceService->getUserPreferences(Auth::id()),
        ],
    ];
}
```

**Wyjaśnienie pól:**

1. **user_id** - właściciel planu (auth user)
2. **status** - stan planu:
   - `draft` - szkic
   - `generating` - w trakcie generowania AI
   - `planned` - wygenerowany plan
   - `completed` - wycieczka zrealizowana
3. **metadata** - dodatkowe informacje:
   - `created_from` - źródło (web, mobile app w przyszłości)
   - `user_agent` - informacja o przeglądarce (analytics)
   - `preferences_snapshot` - snapshot preferencji w momencie generowania (tracking zmian)

**Bezpieczeństwo:**
- User ID zawsze z Auth::id() - użytkownik nie może tworzyć planów dla innych
- Sanityzacja string inputs przez Laravel automatycznie
- Metadata nie zawiera PII (personally identifiable information)

### 4.2 Metoda: `canGeneratePlan()`

**Cel:** Sprawdzenie czy użytkownik może wygenerować plan

```php
/**
 * Sprawdza czy użytkownik może wygenerować plan
 * Weryfikuje limity miesięczne
 *
 * @return bool
 */
private function canGeneratePlan(): bool
{
    $userId = Auth::id();

    // Pobranie liczby generowań w bieżącym miesiącu
    $this->generationsUsed = $this->limitService->getGenerationCount($userId);

    // Sprawdzenie limitu
    $this->canGenerate = $this->generationsUsed < $this->generationsLimit;

    return $this->canGenerate;
}
```

**Logika LimitService:**

```php
// app/Services/LimitService.php

public function getGenerationCount(int $userId): int
{
    return \App\Models\AIGeneration::where('user_id', $userId)
        ->whereMonth('created_at', now()->month)
        ->whereYear('created_at', now()->year)
        ->count();
}

public function incrementGenerationCount(int $userId): void
{
    \App\Models\AIGeneration::create([
        'user_id' => $userId,
        'generated_at' => now(),
        'model' => config('ai.model'), // 'gpt-4o-mini'
        'status' => 'pending',
    ]);
}
```

**Obsługa race conditions:**

Problem: Dwóch użytkowników jednocześnie generuje plan przy 9/10 limitów
- Oba requesty przechodzą sprawdzenie (9 < 10)
- Oba inkrementują licznik (10 i 11)
- Przekroczenie limitu!

Rozwiązanie: Database locking lub atomic increment
```php
public function incrementGenerationCount(int $userId): void
{
    DB::transaction(function () use ($userId) {
        // Sprawdzenie z zamkiem
        $count = \App\Models\AIGeneration::where('user_id', $userId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->lockForUpdate() // Pessimistic lock
            ->count();

        if ($count >= 10) {
            throw new \App\Exceptions\LimitExceededException();
        }

        \App\Models\AIGeneration::create([
            'user_id' => $userId,
            'generated_at' => now(),
            'model' => config('ai.model'),
            'status' => 'pending',
        ]);
    });
}
```

### 4.3 Metoda: `calculateEndDate()`

**Cel:** Obliczenie daty zakończenia wycieczki

```php
/**
 * Oblicza datę zakończenia na podstawie start_date + days_count
 *
 * @return \Carbon\Carbon|null
 */
private function calculateEndDate(): ?\Carbon\Carbon
{
    if (empty($this->start_date) || empty($this->days_count)) {
        return null;
    }

    try {
        return \Carbon\Carbon::parse($this->start_date)
            ->addDays($this->days_count - 1); // -1 bo pierwszy dzień to start_date
    } catch (\Exception $e) {
        return null;
    }
}
```

**Przykład:**
- Start: 2025-11-15
- Dni: 7
- End: 2025-11-21 (15 + 6 dni, bo 15.11 to dzień 1)

### 4.4 Metoda: `calculateTotalBudget()`

**Cel:** Obliczenie całkowitego budżetu wycieczki

```php
/**
 * Oblicza całkowity budżet (budget_per_person * people_count)
 *
 * @return float|null
 */
private function calculateTotalBudget(): ?float
{
    if (empty($this->budget_per_person) || empty($this->people_count)) {
        return null;
    }

    return round($this->budget_per_person * $this->people_count, 2);
}
```

**Wyświetlanie w UI:**
```blade
@if($totalBudget)
    <div class="text-sm text-gray-600">
        Całkowity budżet: {{ number_format($totalBudget, 2) }} {{ $selectedCurrency }}
    </div>
@endif
```

### 4.5 Metoda: `getLimitInfo()`

**Cel:** Przygotowanie informacji o limitach dla UI

```php
/**
 * Zwraca informacje o limitach generowań dla UI
 *
 * @return array
 */
private function getLimitInfo(): array
{
    $used = $this->generationsUsed;
    $limit = $this->generationsLimit;
    $remaining = max(0, $limit - $used);
    $percentage = ($used / $limit) * 100;

    return [
        'used' => $used,
        'limit' => $limit,
        'remaining' => $remaining,
        'percentage' => round($percentage, 1),
        'canGenerate' => $used < $limit,
        'resetDate' => now()->addMonth()->startOfMonth()->format('Y-m-d'),
        'displayText' => "{$used}/{$limit} w tym miesiącu",
        'color' => match(true) {
            $percentage >= 90 => 'red',
            $percentage >= 70 => 'yellow',
            default => 'green'
        },
    ];
}
```

**Użycie w widoku:**
```blade
<div class="flex items-center gap-2">
    <span class="text-sm text-gray-700">{{ $limitInfo['displayText'] }}</span>
    <div class="w-24 h-2 bg-gray-200 rounded-full">
        <div class="h-full bg-{{ $limitInfo['color'] }}-500 rounded-full"
             style="width: {{ $limitInfo['percentage'] }}%"></div>
    </div>
</div>
```

### 4.6 Metoda: `getLimitExceededMessage()`

**Cel:** Zwrócenie komunikatu o przekroczeniu limitu

```php
/**
 * Zwraca komunikat o przekroczeniu limitu generowań
 *
 * @return string
 */
private function getLimitExceededMessage(): string
{
    $resetDate = now()->addMonth()->startOfMonth()->translatedFormat('j F Y');

    return "Osiągnąłeś limit {$this->generationsLimit} generowań w tym miesiącu. "
         . "Limit odnowi się {$resetDate}. "
         . "Możesz nadal zapisywać szkice planów.";
}
```

### 4.7 Metoda: `loadTravelData()`

**Cel:** Załadowanie danych planu przy edycji

```php
/**
 * Ładuje dane istniejącego planu do formularza (tryb edycji)
 * Sprawdza uprawnienia użytkownika
 *
 * @return void
 * @throws \Illuminate\Auth\Access\AuthorizationException
 */
private function loadTravelData(): void
{
    $travel = \App\Models\Travel::findOrFail($this->travelId);

    // Sprawdzenie uprawnień (tylko właściciel)
    if ($travel->user_id !== Auth::id()) {
        abort(403, 'Unauthorized action.');
    }

    // Załadowanie danych do właściwości komponentu
    $this->title = $travel->title;
    $this->destination = $travel->destination;
    $this->start_date = $travel->start_date?->format('Y-m-d');
    $this->days_count = $travel->days_count;
    $this->people_count = $travel->people_count;
    $this->budget_per_person = $travel->budget_per_person;
    $this->selectedCurrency = $travel->budget_currency ?? 'PLN';
    $this->notes = $travel->notes;
}
```

**Bezpieczeństwo:**
- Sprawdzenie `user_id` - tylko właściciel może edytować
- `findOrFail()` - 404 dla nieistniejących ID
- Alternative: Policy-based authorization

### 4.8 Metody obsługi błędów

**handleSaveError():**
```php
/**
 * Obsługuje błędy podczas zapisywania planu
 *
 * @param \Exception $e
 * @return void
 */
private function handleSaveError(\Exception $e): void
{
    // Logowanie błędu
    \Log::error('Failed to save travel plan', [
        'user_id' => Auth::id(),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);

    // Komunikat dla użytkownika
    $this->errorMessage = 'Wystąpił błąd podczas zapisywania planu. Spróbuj ponownie.';

    // Dispatch event dla monitoringu
    event(new \App\Events\TravelSaveFailed(Auth::id(), $e));
}
```

**handleGenerationError():**
```php
/**
 * Obsługuje błędy podczas generowania planu AI
 * Rollback licznika limitów
 *
 * @param \Exception $e
 * @return void
 */
private function handleGenerationError(\Exception $e): void
{
    // Rollback licznika (usunięcie wpisu z AIGeneration)
    $this->limitService->rollbackGeneration(Auth::id());

    // Logowanie błędu
    \Log::error('Failed to generate travel plan', [
        'user_id' => Auth::id(),
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);

    // Komunikat specyficzny dla typu błędu
    $this->errorMessage = match(true) {
        $e instanceof \App\Exceptions\AITimeoutException
            => 'Generowanie trwa zbyt długo. Spróbuj ponownie za chwilę.',
        $e instanceof \App\Exceptions\AIAPIException
            => 'Serwis AI jest chwilowo niedostępny. Spróbuj ponownie za chwilę.',
        default
            => 'Wystąpił problem z generowaniem planu. Spróbuj ponownie.',
    };

    // Event dla monitoringu
    event(new \App\Events\AIGenerationFailed(Auth::id(), $e));
}
```

**handleLimitError():**
```php
/**
 * Obsługuje błąd przekroczenia limitu
 *
 * @param \App\Exceptions\LimitExceededException $e
 * @return void
 */
private function handleLimitError(\App\Exceptions\LimitExceededException $e): void
{
    $this->canGenerate = false;
    $this->errorMessage = $this->getLimitExceededMessage();

    // Event do tracking analytics
    event(new \App\Events\GenerationLimitReached(Auth::id()));
}
```

---

## 5. Obsługa błędów

### 5.1 Kategorie błędów

#### 1. Błędy walidacji (ValidationException)

**Scenariusze:**
1. Pola wymagane nie wypełnione
2. Data z przeszłości
3. Liczba dni/osób poza zakresem
4. Nieprawidłowy format danych

**Obsługa:**
- Automatyczna przez Livewire
- Wyświetlanie błędów inline przy polach
- Real-time validation przez `updated()` hook

**Przykład w widoku:**
```blade
<div>
    <x-input wire:model="title" label="Tytuł planu" />
    @error('title')
        <span class="text-sm text-red-600">{{ $message }}</span>
    @enderror
</div>
```

#### 2. Błędy limitów (LimitExceededException)

**Scenariusze:**
1. Użytkownik osiągnął 10/10 generowań
2. Race condition przy równoczesnych requestach

**Obsługa:**
```php
try {
    $this->limitService->incrementGenerationCount(Auth::id());
} catch (\App\Exceptions\LimitExceededException $e) {
    $this->handleLimitError($e);
    return;
}
```

**Komunikat:**
- "Osiągnąłeś limit 10 generowań w tym miesiącu. Limit odnowi się [data]."
- Disabled button "Generuj plan"
- Sugestia: zapisz jako szkic

#### 3. Błędy API AI (AIAPIException, AITimeoutException)

**Scenariusze:**
1. OpenAI API timeout (>30s)
2. OpenAI API error 500/503
3. Rate limit OpenAI (429)
4. Invalid API key
5. Niekompletna odpowiedź

**Obsługa:**
```php
// app/Services/AIGenerationService.php

public function generatePlan(Travel $travel, array $preferences): array
{
    try {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => $this->buildMessages($travel, $preferences),
            'timeout' => 45, // 45s timeout
        ]);

        return $this->parseResponse($response);

    } catch (\OpenAI\Exceptions\TimeoutException $e) {
        throw new \App\Exceptions\AITimeoutException('AI request timed out', 0, $e);
    } catch (\OpenAI\Exceptions\ErrorException $e) {
        throw new \App\Exceptions\AIAPIException('AI API error: ' . $e->getMessage(), 0, $e);
    }
}
```

**Recovery:**
- Rollback licznika limitów
- Komunikat: "Spróbuj ponownie"
- Opcjonalnie: Automatic retry (1x) w Job

#### 4. Błędy bazy danych (QueryException)

**Scenariusze:**
1. Brak połączenia z MySQL
2. Constraint violation (np. foreign key)
3. Duplicate key
4. Timeout query

**Obsługa:**
```php
try {
    $travel = $this->travelService->create($planData);
} catch (\Illuminate\Database\QueryException $e) {
    // Specyficzna obsługa dla różnych kodów błędów
    if ($e->getCode() === '23000') { // Integrity constraint violation
        $this->errorMessage = 'Wystąpił problem z danymi. Sprawdź formularz.';
    } else {
        $this->errorMessage = 'Problem z połączeniem. Spróbuj ponownie.';
    }

    \Log::error('Database error in CreatePlanForm', [
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
    ]);
}
```

#### 5. Błędy autoryzacji (AuthorizationException)

**Scenariusze:**
1. Użytkownik próbuje edytować cudzy plan
2. Niezalogowany użytkownik

**Obsługa:**
```php
// Middleware w routes/web.php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/plans/create', CreatePlanForm::class)->name('plans.create');
    Route::get('/plans/{travel}/edit', CreatePlanForm::class)->name('plans.edit');
});

// W komponencie
private function loadTravelData(): void
{
    $travel = Travel::findOrFail($this->travelId);

    // Policy check (opcjonalne, zamiast ręcznego sprawdzania)
    $this->authorize('update', $travel);

    // ... load data
}
```

**Policy:**
```php
// app/Policies/TravelPolicy.php

public function update(User $user, Travel $travel): bool
{
    return $user->id === $travel->user_id;
}
```

#### 6. Błędy kolejki (Redis connection errors)

**Scenariusze:**
1. Redis down
2. Pełna kolejka
3. Timeout połączenia

**Obsługa:**
```php
// config/queue.php - fallback do database queue

'connections' => [
    'redis' => [
        'driver' => 'redis',
        // ...
    ],
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        // ...
    ],
],

'failed' => [
    'driver' => 'database-uuids',
    'database' => 'mysql',
    'table' => 'failed_jobs',
],
```

**Automatic fallback:**
```php
try {
    GenerateTravelPlanJob::dispatch($travel, $preferences)->onQueue('ai-generation');
} catch (\Exception $e) {
    // Fallback do database queue
    GenerateTravelPlanJob::dispatch($travel, $preferences)
        ->onConnection('database')
        ->onQueue('ai-generation');
}
```

### 5.2 Centralna obsługa błędów

**Global error handler (Handler.php):**

```php
// app/Exceptions/Handler.php

public function register(): void
{
    $this->reportable(function (\App\Exceptions\AIAPIException $e) {
        // Custom logging dla błędów AI
        \Log::channel('ai')->error('AI API Error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Opcjonalnie: wysłanie do Sentry/monitoring
        // Sentry::captureException($e);
    });

    $this->reportable(function (\App\Exceptions\LimitExceededException $e) {
        // Tracking przekroczeń limitów (analytics)
        event(new \App\Events\GenerationLimitReached($e->getUserId()));
    });
}
```

### 5.3 Monitoring i alerting

**Metryki do monitorowania:**

1. **Error rate** - % failedgenerowań
   - Alert: >5% w ostatniej godzinie

2. **API timeout rate** - % timeout errors
   - Alert: >10% w ostatniej godzinie

3. **Database errors** - liczba błędów DB
   - Alert: >5 w ostatnich 5 minutach

4. **Failed jobs** - liczba failed jobs w kolejce
   - Alert: >10 w ostatniej godzinie

**Implementacja (Laravel Telescope + custom monitoring):**

```php
// app/Providers/AppServiceProvider.php

public function boot(): void
{
    // Event listeners dla monitoringu
    \Event::listen(\App\Events\AIGenerationFailed::class, function ($event) {
        \Cache::increment('metrics:ai_failures:' . now()->format('Y-m-d-H'));
    });

    \Event::listen(\App\Events\TravelSaveFailed::class, function ($event) {
        \Cache::increment('metrics:save_failures:' . now()->format('Y-m-d-H'));
    });
}
```

**Dashboard monitoringu (Livewire component):**
```php
// Wyświetlanie metryk w czasie rzeczywistym
$aiFailures = \Cache::get('metrics:ai_failures:' . now()->format('Y-m-d-H'), 0);
$saveFailures = \Cache::get('metrics:save_failures:' . now()->format('Y-m-d-H'), 0);

if ($aiFailures > 5) {
    // Alert admins
    \Notification::route('mail', config('app.admin_email'))
        ->notify(new \App\Notifications\HighErrorRateAlert('AI Generation', $aiFailures));
}
```

---

## 6. Kwestie bezpieczeństwa

### 6.1 Autentykacja i autoryzacja

#### 1. Route protection (middleware)

```php
// routes/web.php

Route::middleware(['auth', 'verified'])->group(function () {
    // Tylko zweryfikowani użytkownicy mogą tworzyć plany
    Route::get('/plans/create', CreatePlanForm::class)
        ->name('plans.create');

    Route::get('/plans/{travel}/edit', CreatePlanForm::class)
        ->name('plans.edit')
        ->middleware('can:update,travel'); // Policy check
});
```

#### 2. Component-level authorization

```php
// W CreatePlanForm component

public function mount(?int $travelId = null)
{
    // Sprawdzenie autoryzacji przy edycji
    if ($travelId) {
        $travel = Travel::findOrFail($travelId);
        $this->authorize('update', $travel); // Laravel Policy
    }

    // ...
}
```

#### 3. Policy definition

```php
// app/Policies/TravelPolicy.php

class TravelPolicy
{
    public function view(User $user, Travel $travel): bool
    {
        return $user->id === $travel->user_id;
    }

    public function update(User $user, Travel $travel): bool
    {
        return $user->id === $travel->user_id;
    }

    public function delete(User $user, Travel $travel): bool
    {
        return $user->id === $travel->user_id;
    }
}
```

### 6.2 Input validation i sanitization

#### 1. Server-side validation (walidacja back-end)

```php
// Livewire attributes - ZAWSZE server-side
#[Rule('required|string|max:255')]
public string $title = '';

#[Rule('required|string|max:255')]
public string $destination = '';

#[Rule('required|date|after:today')]
public ?string $start_date = null;

#[Rule(['notes' => 'nullable|string|max:5000'])]
public ?string $notes = null;
```

**Dlaczego server-side jest kluczowy:**
- Client-side może być ominięty (JavaScript disabled, manipulacja DOM)
- Server-side = pewność bezpieczeństwa

#### 2. Custom validation rules

```php
// app/Rules/NoMaliciousContent.php

class NoMaliciousContent implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Sprawdzenie XSS patterns
        $patterns = [
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=/i', // onclick, onload, etc.
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $fail("The {$attribute} contains prohibited content.");
            }
        }
    }
}

// Użycie
#[Rule([new NoMaliciousContent, 'max:5000'])]
public ?string $notes = null;
```

#### 3. HTML sanitization (Blade automatic escaping)

```blade
{{-- BEZPIECZNE - automatic escaping --}}
<h1>{{ $title }}</h1>
<p>{{ $notes }}</p>

{{-- NIEBEZPIECZNE - raw HTML --}}
<div>{!! $notes !!}</div> {{-- NIGDY dla user input! --}}

{{-- Dla rich text (jeśli potrzebne w przyszłości) --}}
<div>{!! \Illuminate\Support\Str::sanitizeHtml($notes) !!}</div>
```

#### 4. Database query injection prevention

```php
// BEZPIECZNE - Eloquent/Query Builder
$travels = Travel::where('user_id', Auth::id())
    ->where('destination', $destination)
    ->get();

// NIEBEZPIECZNE - raw SQL bez binding
$travels = DB::select("SELECT * FROM travels WHERE destination = '{$destination}'");

// BEZPIECZNE - raw SQL z binding
$travels = DB::select(
    "SELECT * FROM travels WHERE destination = ?",
    [$destination]
);
```

### 6.3 CSRF Protection

**Laravel domyślnie chroni przed CSRF:**

```blade
{{-- Formularz standard (nie Livewire) --}}
<form method="POST" action="/plans">
    @csrf
    <!-- ... -->
</form>

{{-- Livewire - CSRF token automatycznie --}}
<form wire:submit="generatePlan">
    <!-- @csrf NIE POTRZEBNE - Livewire dodaje automatycznie -->
</form>
```

**Weryfikacja w middleware:**
```php
// app/Http/Middleware/VerifyCsrfToken.php

protected $except = [
    // Wyjątki (webhooks itp.) - UNIKAJ jeśli możliwe
];
```

### 6.4 Rate Limiting

#### 1. Route-level throttling

```php
// routes/web.php

Route::middleware(['auth', 'verified', 'throttle:10,1'])->group(function () {
    // Maksymalnie 10 requestów na minutę
    Route::get('/plans/create', CreatePlanForm::class)->name('plans.create');
});
```

#### 2. Custom rate limiting dla generowania

```php
// app/Providers/RouteServiceProvider.php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    RateLimiter::for('ai-generation', function (Request $request) {
        return Limit::perMinute(3) // Max 3 generowania na minutę
            ->by($request->user()?->id ?: $request->ip())
            ->response(function () {
                return response()->json([
                    'message' => 'Zbyt wiele prób generowania. Spróbuj za chwilę.'
                ], 429);
            });
    });
}
```

**Użycie w komponencie:**
```php
public function generatePlan(): void
{
    // Sprawdzenie rate limit
    if (RateLimiter::tooManyAttempts('generate-plan:' . Auth::id(), 3)) {
        $this->errorMessage = 'Zbyt wiele prób. Spróbuj za chwilę.';
        return;
    }

    RateLimiter::hit('generate-plan:' . Auth::id(), 60); // 60 sekund decay

    // ... kontynuacja generowania
}
```

### 6.5 Mass Assignment Protection

```php
// app/Models/Travel.php

class Travel extends Model
{
    // OPCJA 1: Whitelist (preferowane)
    protected $fillable = [
        'user_id',
        'title',
        'destination',
        'start_date',
        'days_count',
        'people_count',
        'budget_per_person',
        'budget_currency',
        'notes',
        'status',
        'metadata',
    ];

    // OPCJA 2: Blacklist (rzadziej używane)
    // protected $guarded = ['id', 'created_at', 'updated_at'];
}
```

**Dlaczego to ważne:**
```php
// BEZ OCHRONY - użytkownik może zmienić user_id!
$data = $request->all(); // ['title' => 'X', 'user_id' => 999]
Travel::create($data); // Stworzenie planu dla user_id=999!

// Z OCHRONĄ - user_id musi być jawnie ustawiony
$data = $request->validated();
$data['user_id'] = Auth::id(); // BEZPIECZNE
Travel::create($data);
```

### 6.6 Secure API Keys

```env
# .env - NIGDY nie commitować do Git!

OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxx
OPENAI_ORGANIZATION=org-xxxxxxxxxxxxx
```

**Best practices:**
1. API keys w `.env` (nie w kodzie)
2. `.env` w `.gitignore`
3. Rotacja kluczy co 90 dni
4. Osobne klucze dla dev/staging/production
5. Monitoring zużycia API (alert przy anomalii)

```php
// config/ai.php

return [
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
    ],
    'model' => env('AI_MODEL', 'gpt-4o-mini'),
    'use_real_api' => env('AI_USE_REAL_API', false), // Mock w dev
];
```

### 6.7 HTTPS Enforcement

```php
// app/Providers/AppServiceProvider.php

public function boot(): void
{
    if ($this->app->environment('production')) {
        URL::forceScheme('https');
    }
}
```

**Konfiguracja serwera (nginx):**
```nginx
server {
    listen 80;
    server_name vibetravels.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name vibetravels.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    # ...
}
```

### 6.8 Session Security

```php
// config/session.php

return [
    'driver' => env('SESSION_DRIVER', 'redis'),
    'lifetime' => 120, // 2 godziny
    'expire_on_close' => false,
    'encrypt' => true,
    'secure' => env('SESSION_SECURE_COOKIE', true), // HTTPS only
    'http_only' => true, // Ochrona przed XSS
    'same_site' => 'lax', // Ochrona przed CSRF
];
```

### 6.9 Logging i Auditing

```php
// Logowanie wrażliwych operacji
\Log::channel('audit')->info('Travel plan created', [
    'user_id' => Auth::id(),
    'travel_id' => $travel->id,
    'ip' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);

\Log::channel('audit')->info('Travel plan generated', [
    'user_id' => Auth::id(),
    'travel_id' => $travel->id,
    'ai_cost' => $cost,
    'tokens_used' => $tokens,
]);
```

**Log channels:**
```php
// config/logging.php

'channels' => [
    'audit' => [
        'driver' => 'daily',
        'path' => storage_path('logs/audit.log'),
        'level' => 'info',
        'days' => 90, // 3 miesiące retention
    ],
    'ai' => [
        'driver' => 'daily',
        'path' => storage_path('logs/ai.log'),
        'level' => 'debug',
        'days' => 30,
    ],
],
```

---

## 7. Plan wdrożenia krok po kroku

### Krok 1: Przygotowanie bazy danych

#### 1.1 Migracja dla tabeli `travels`

```bash
php artisan make:migration create_travels_table
```

```php
// database/migrations/xxxx_create_travels_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Podstawowe informacje
            $table->string('title');
            $table->string('destination');
            $table->date('start_date');
            $table->unsignedTinyInteger('days_count'); // 1-30
            $table->unsignedTinyInteger('people_count'); // 1-10

            // Budżet (opcjonalny)
            $table->decimal('budget_per_person', 10, 2)->nullable();
            $table->string('budget_currency', 3)->default('PLN');

            // Notatki użytkownika
            $table->text('notes')->nullable();

            // Status planu
            $table->enum('status', ['draft', 'generating', 'planned', 'completed'])
                ->default('draft');

            // Wygenerowany plan (JSON)
            $table->json('generated_plan')->nullable();

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indeksy
            $table->index(['user_id', 'status']);
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travels');
    }
};
```

#### 1.2 Migracja dla tabeli `ai_generations`

```bash
php artisan make:migration create_ai_generations_table
```

```php
// database/migrations/xxxx_create_ai_generations_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('travel_id')->nullable()->constrained()->onDelete('set null');

            // Informacje o generowaniu
            $table->string('model'); // 'gpt-4o-mini'
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                ->default('pending');

            // Koszty i metryki
            $table->unsignedInteger('tokens_used')->nullable();
            $table->decimal('cost_usd', 8, 4)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();

            // Error tracking
            $table->text('error_message')->nullable();

            $table->timestamp('generated_at');
            $table->timestamps();

            // Indeksy dla limitów
            $table->index(['user_id', 'generated_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
    }
};
```

#### 1.3 Uruchomienie migracji

```bash
php artisan migrate
```

### Krok 2: Stworzenie modeli Eloquent

#### 2.1 Model Travel

```bash
php artisan make:model Travel
```

```php
// app/Models/Travel.php

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Travel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'destination',
        'start_date',
        'days_count',
        'people_count',
        'budget_per_person',
        'budget_currency',
        'notes',
        'status',
        'generated_plan',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'days_count' => 'integer',
        'people_count' => 'integer',
        'budget_per_person' => 'decimal:2',
        'generated_plan' => 'array',
        'metadata' => 'array',
    ];

    // Relacje
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AIGeneration::class);
    }

    // Accessors
    public function getEndDateAttribute(): ?\Carbon\Carbon
    {
        return $this->start_date?->addDays($this->days_count - 1);
    }

    public function getTotalBudgetAttribute(): ?float
    {
        if (!$this->budget_per_person) {
            return null;
        }
        return round($this->budget_per_person * $this->people_count, 2);
    }

    // Scopes
    public function scopeDrafts($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePlanned($query)
    {
        return $query->where('status', 'planned');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
```

#### 2.2 Model AIGeneration

```bash
php artisan make:model AIGeneration
```

```php
// app/Models/AIGeneration.php

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIGeneration extends Model
{
    protected $fillable = [
        'user_id',
        'travel_id',
        'model',
        'status',
        'tokens_used',
        'cost_usd',
        'duration_seconds',
        'error_message',
        'generated_at',
    ];

    protected $casts = [
        'tokens_used' => 'integer',
        'cost_usd' => 'decimal:4',
        'duration_seconds' => 'integer',
        'generated_at' => 'datetime',
    ];

    // Relacje
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function travel(): BelongsTo
    {
        return $this->belongsTo(Travel::class);
    }

    // Scopes
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('generated_at', now()->month)
            ->whereYear('generated_at', now()->year);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
```

### Krok 3: Stworzenie serwisów (Service Layer)

#### 3.1 TravelService

```bash
mkdir -p app/Services
touch app/Services/TravelService.php
```

```php
// app/Services/TravelService.php

<?php

namespace App\Services;

use App\Models\Travel;
use Illuminate\Support\Facades\DB;

class TravelService
{
    /**
     * Tworzy nowy plan wycieczki
     */
    public function create(array $data): Travel
    {
        return DB::transaction(function () use ($data) {
            return Travel::create($data);
        });
    }

    /**
     * Aktualizuje istniejący plan
     */
    public function update(int $travelId, array $data): Travel
    {
        return DB::transaction(function () use ($travelId, $data) {
            $travel = Travel::findOrFail($travelId);
            $travel->update($data);
            return $travel->fresh();
        });
    }

    /**
     * Usuwa plan
     */
    public function delete(int $travelId): bool
    {
        $travel = Travel::findOrFail($travelId);
        return $travel->delete();
    }

    /**
     * Pobiera plany użytkownika
     */
    public function getUserTravels(int $userId, ?string $status = null)
    {
        $query = Travel::forUser($userId)->latest();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }
}
```

#### 3.2 LimitService

```php
// app/Services/LimitService.php

<?php

namespace App\Services;

use App\Models\AIGeneration;
use App\Exceptions\LimitExceededException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LimitService
{
    public const MONTHLY_LIMIT = 10;

    /**
     * Pobiera liczbę generowań w bieżącym miesiącu
     */
    public function getGenerationCount(int $userId): int
    {
        return AIGeneration::forUser($userId)
            ->thisMonth()
            ->count();
    }

    /**
     * Sprawdza czy użytkownik może wygenerować plan
     */
    public function canGenerate(int $userId): bool
    {
        return $this->getGenerationCount($userId) < self::MONTHLY_LIMIT;
    }

    /**
     * Inkrementuje licznik generowań z ochroną przed race conditions
     */
    public function incrementGenerationCount(int $userId, ?int $travelId = null): AIGeneration
    {
        return DB::transaction(function () use ($userId, $travelId) {
            // Sprawdzenie z zamkiem pessimistic lock
            $count = AIGeneration::forUser($userId)
                ->thisMonth()
                ->lockForUpdate()
                ->count();

            if ($count >= self::MONTHLY_LIMIT) {
                throw new LimitExceededException("Monthly generation limit exceeded");
            }

            return AIGeneration::create([
                'user_id' => $userId,
                'travel_id' => $travelId,
                'model' => config('ai.model', 'gpt-4o-mini'),
                'status' => 'pending',
                'generated_at' => now(),
            ]);
        });
    }

    /**
     * Rollback generowania (przy błędzie)
     */
    public function rollbackGeneration(int $userId): void
    {
        AIGeneration::forUser($userId)
            ->thisMonth()
            ->where('status', 'pending')
            ->latest()
            ->first()
            ?->delete();
    }

    /**
     * Data resetu limitu (pierwszy dzień następnego miesiąca)
     */
    public function getResetDate(): Carbon
    {
        return now()->addMonth()->startOfMonth();
    }
}
```

#### 3.3 PreferenceService

```php
// app/Services/PreferenceService.php

<?php

namespace App\Services;

use App\Models\User;

class PreferenceService
{
    /**
     * Pobiera preferencje użytkownika
     */
    public function getUserPreferences(int $userId): array
    {
        $user = User::findOrFail($userId);

        return [
            'interests' => $user->interests ?? [],
            'travel_pace' => $user->travel_pace ?? 'moderate',
            'budget_level' => $user->budget_level ?? 'standard',
            'transport_preference' => $user->transport_preference ?? 'mixed',
            'restrictions' => $user->restrictions ?? [],
        ];
    }
}
```

#### 3.4 AIGenerationService

```bash
composer require openai-php/laravel
```

```php
// app/Services/AIGenerationService.php

<?php

namespace App\Services;

use App\Models\Travel;
use App\Models\AIGeneration;
use App\Exceptions\AITimeoutException;
use App\Exceptions\AIAPIException;
use OpenAI\Laravel\Facades\OpenAI;
use Carbon\Carbon;

class AIGenerationService
{
    /**
     * Generuje plan wycieczki przy użyciu OpenAI API
     */
    public function generatePlan(Travel $travel, array $userPreferences): array
    {
        $startTime = now();

        try {
            $response = OpenAI::chat()->create([
                'model' => config('ai.model', 'gpt-4o-mini'),
                'messages' => $this->buildMessages($travel, $userPreferences),
                'temperature' => 0.7,
                'max_tokens' => 3000,
            ]);

            $duration = now()->diffInSeconds($startTime);
            $content = $response->choices[0]->message->content;
            $parsedPlan = $this->parseResponse($content);

            // Zwrot danych dla Job
            return [
                'plan' => $parsedPlan,
                'tokens' => $response->usage->totalTokens,
                'cost' => $this->calculateCost($response->usage->totalTokens),
                'duration' => $duration,
            ];

        } catch (\OpenAI\Exceptions\TimeoutException $e) {
            throw new AITimeoutException('AI request timed out', 0, $e);
        } catch (\OpenAI\Exceptions\ErrorException $e) {
            throw new AIAPIException('AI API error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Buduje messages array dla OpenAI API
     */
    private function buildMessages(Travel $travel, array $preferences): array
    {
        $systemPrompt = $this->getSystemPrompt($preferences);
        $userPrompt = $this->getUserPrompt($travel);

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];
    }

    /**
     * System prompt z preferencjami użytkownika
     */
    private function getSystemPrompt(array $preferences): string
    {
        $interests = implode(', ', $preferences['interests'] ?? []);

        return <<<PROMPT
        Jesteś ekspertem w planowaniu wycieczek. Twoim zadaniem jest stworzenie szczegółowego,
        dzień po dniu planu wycieczki, uwzględniając preferencje użytkownika.

        Preferencje użytkownika:
        - Zainteresowania: {$interests}
        - Tempo podróży: {$preferences['travel_pace']}
        - Poziom budżetu: {$preferences['budget_level']}
        - Transport: {$preferences['transport_preference']}

        Wygeneruj plan w formacie JSON zgodnym z poniższym schematem:
        {
            "days": [
                {
                    "day_number": 1,
                    "date": "2025-11-15",
                    "periods": {
                        "morning": [...],
                        "afternoon": [...],
                        "evening": [...]
                    }
                }
            ]
        }

        Każda aktywność powinna zawierać:
        - name: nazwa miejsca/atrakcji
        - description: 2-3 zdania opisu
        - reasoning: dlaczego pasuje do preferencji użytkownika
        - duration: szacowany czas wizyty (np. "2 godziny")
        - google_maps_url: link do Google Maps
        PROMPT;
    }

    /**
     * User prompt z danymi wycieczki
     */
    private function getUserPrompt(Travel $travel): string
    {
        $budget = $travel->budget_per_person
            ? "{$travel->budget_per_person} {$travel->budget_currency} na osobę"
            : "nie określony";

        return <<<PROMPT
        Zaplanuj wycieczkę do: {$travel->destination}
        Data wyjazdu: {$travel->start_date->format('Y-m-d')}
        Liczba dni: {$travel->days_count}
        Liczba osób: {$travel->people_count}
        Budżet: {$budget}

        Dodatkowe notatki użytkownika:
        {$travel->notes}
        PROMPT;
    }

    /**
     * Parsuje odpowiedź AI do struktury array
     */
    private function parseResponse(string $content): array
    {
        // Wydobycie JSON z odpowiedzi
        preg_match('/\{[\s\S]*\}/', $content, $matches);

        if (empty($matches)) {
            throw new AIAPIException('Invalid AI response format');
        }

        $decoded = json_decode($matches[0], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new AIAPIException('Failed to parse AI JSON response');
        }

        return $decoded;
    }

    /**
     * Oblicza koszt generowania
     */
    private function calculateCost(int $tokens): float
    {
        // GPT-4o-mini pricing (przykładowe wartości)
        // Input: $0.15 / 1M tokens
        // Output: $0.60 / 1M tokens
        // Uproszczenie: średnio $0.30 / 1M tokens

        return round(($tokens / 1_000_000) * 0.30, 4);
    }
}
```

### Krok 4: Stworzenie Job dla kolejki

```bash
php artisan make:job GenerateTravelPlanJob
```

```php
// app/Jobs/GenerateTravelPlanJob.php

<?php

namespace App\Jobs;

use App\Models\Travel;
use App\Models\AIGeneration;
use App\Services\AIGenerationService;
use App\Events\PlanGenerated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateTravelPlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2; // Maksymalnie 2 próby
    public $timeout = 60; // 60 sekund timeout

    public function __construct(
        public Travel $travel,
        public array $userPreferences
    ) {}

    public function handle(AIGenerationService $aiService): void
    {
        // Aktualizacja statusu generowania
        $aiGeneration = AIGeneration::where('travel_id', $this->travel->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        $aiGeneration?->update(['status' => 'processing']);

        try {
            // Wywołanie AI API
            $result = $aiService->generatePlan($this->travel, $this->userPreferences);

            // Aktualizacja planu w bazie
            $this->travel->update([
                'generated_plan' => $result['plan'],
                'status' => 'planned',
            ]);

            // Aktualizacja metadata AI Generation
            $aiGeneration?->update([
                'status' => 'completed',
                'tokens_used' => $result['tokens'],
                'cost_usd' => $result['cost'],
                'duration_seconds' => $result['duration'],
            ]);

            // Event - plan wygenerowany
            event(new PlanGenerated($this->travel));

            Log::info('Travel plan generated successfully', [
                'travel_id' => $this->travel->id,
                'tokens' => $result['tokens'],
                'cost' => $result['cost'],
            ]);

        } catch (\Exception $e) {
            // Obsługa błędu
            $aiGeneration?->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $this->travel->update(['status' => 'draft']);

            Log::error('Failed to generate travel plan', [
                'travel_id' => $this->travel->id,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw dla failed jobs queue
        }
    }

    public function failed(\Throwable $exception): void
    {
        // Powiadomienie użytkownika o błędzie (opcjonalnie)
        Log::error('GenerateTravelPlanJob permanently failed', [
            'travel_id' => $this->travel->id,
            'exception' => $exception->getMessage(),
        ]);
    }
}
```

### Krok 5: Stworzenie custom exceptions

```php
// app/Exceptions/LimitExceededException.php

<?php

namespace App\Exceptions;

use Exception;

class LimitExceededException extends Exception
{
    protected int $userId;

    public function __construct(string $message = "", int $userId = 0, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->userId = $userId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
```

```php
// app/Exceptions/AITimeoutException.php

<?php

namespace App\Exceptions;

use Exception;

class AITimeoutException extends Exception
{
    //
}
```

```php
// app/Exceptions/AIAPIException.php

<?php

namespace App\Exceptions;

use Exception;

class AIAPIException extends Exception
{
    //
}
```

### Krok 6: Stworzenie komponentu Livewire

```bash
php artisan make:livewire Plans/CreatePlanForm
```

```php
// app/Livewire/Plans/CreatePlanForm.php

<?php

namespace App\Livewire\Plans;

use Livewire\Component;
use Livewire\Attributes\Rule;
use App\Services\TravelService;
use App\Services\LimitService;
use App\Services\PreferenceService;
use App\Jobs\GenerateTravelPlanJob;
use Illuminate\Support\Facades\Auth;

class CreatePlanForm extends Component
{
    // Dependency Injection
    protected TravelService $travelService;
    protected LimitService $limitService;
    protected PreferenceService $preferenceService;

    public function boot(
        TravelService $travelService,
        LimitService $limitService,
        PreferenceService $preferenceService
    ) {
        $this->travelService = $travelService;
        $this->limitService = $limitService;
        $this->preferenceService = $preferenceService;
    }

    // Form properties
    #[Rule('required|string|max:255')]
    public string $title = '';

    #[Rule('required|string|max:255')]
    public string $destination = '';

    #[Rule('required|date|after:today')]
    public ?string $start_date = null;

    #[Rule('required|integer|min:1|max:30')]
    public int $days_count = 7;

    #[Rule('required|integer|min:1|max:10')]
    public int $people_count = 2;

    #[Rule('nullable|numeric|min:0')]
    public ?float $budget_per_person = null;

    #[Rule('nullable|string|max:5000')]
    public ?string $notes = null;

    // UI state
    public bool $isGenerating = false;
    public bool $canGenerate = true;
    public int $generationsUsed = 0;
    public int $generationsLimit = 10;
    public array $currencies = ['PLN', 'EUR', 'USD', 'GBP'];
    public string $selectedCurrency = 'PLN';

    // Messages
    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    // Edit mode
    public ?int $travelId = null;
    public bool $editMode = false;

    public function mount(?int $travelId = null)
    {
        $this->travelId = $travelId;
        $this->editMode = !is_null($travelId);

        if (!$this->editMode) {
            $this->checkUserLimit();
        }

        if ($this->editMode) {
            $this->loadTravelData();
        }
    }

    public function saveAsDraft(): void
    {
        $validated = $this->validate();

        try {
            $planData = $this->preparePlanData($validated, status: 'draft');

            $travel = $this->editMode
                ? $this->travelService->update($this->travelId, $planData)
                : $this->travelService->create($planData);

            $this->successMessage = $this->editMode
                ? 'Plan został zaktualizowany jako szkic.'
                : 'Plan został zapisany jako szkic.';

            $this->dispatch('plan-saved', travelId: $travel->id);

            $this->redirectRoute('dashboard', navigate: true);

        } catch (\Exception $e) {
            $this->handleSaveError($e);
        }
    }

    public function generatePlan(): void
    {
        $validated = $this->validate();

        if (!$this->canGeneratePlan()) {
            $this->errorMessage = $this->getLimitExceededMessage();
            return;
        }

        try {
            $this->isGenerating = true;
            $this->errorMessage = null;

            $planData = $this->preparePlanData($validated, status: 'generating');

            $travel = $this->editMode
                ? $this->travelService->update($this->travelId, $planData)
                : $this->travelService->create($planData);

            $this->limitService->incrementGenerationCount(Auth::id(), $travel->id);

            $userPreferences = $this->preferenceService->getUserPreferences(Auth::id());

            GenerateTravelPlanJob::dispatch($travel, $userPreferences)
                ->onQueue('ai-generation');

            $this->successMessage = 'Generowanie planu rozpoczęte. Zajmie to około 30 sekund...';

            $this->redirectRoute('plans.show', ['travel' => $travel->id], navigate: true);

        } catch (\App\Exceptions\LimitExceededException $e) {
            $this->handleLimitError($e);
        } catch (\Exception $e) {
            $this->handleGenerationError($e);
        } finally {
            $this->isGenerating = false;
        }
    }

    public function updated($property): void
    {
        $this->validateOnly($property);

        match($property) {
            'start_date', 'days_count' => $this->calculateEndDate(),
            'budget_per_person', 'people_count' => $this->calculateTotalBudget(),
            default => null
        };
    }

    public function render()
    {
        return view('livewire.plans.create-plan-form', [
            'limitInfo' => $this->getLimitInfo(),
            'endDate' => $this->calculateEndDate(),
            'totalBudget' => $this->calculateTotalBudget(),
        ]);
    }

    // Private methods - wszystkie metody z sekcji 4
    // (preparePlanData, canGeneratePlan, calculateEndDate, etc.)
    // [Kod metod prywatnych jak w sekcji 4]
}
```

### Krok 7: Stworzenie widoku Blade

```blade
{{-- resources/views/livewire/plans/create-plan-form.blade.php --}}

<div class="max-w-3xl mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">
            {{ $editMode ? 'Edytuj plan' : 'Stwórz nowy plan' }}
        </h1>
        <p class="mt-2 text-gray-600">
            Wypełnij podstawowe informacje o wycieczce
        </p>
    </div>

    {{-- Limit Info --}}
    @if(!$editMode)
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-900">
                        Generowania AI w tym miesiącu
                    </p>
                    <p class="text-xs text-blue-700 mt-1">
                        {{ $limitInfo['displayText'] }} • Reset {{ $limitInfo['resetDate'] }}
                    </p>
                </div>
                <div class="w-32">
                    <div class="h-2 bg-blue-200 rounded-full">
                        <div class="h-full bg-{{ $limitInfo['color'] }}-500 rounded-full transition-all"
                             style="width: {{ $limitInfo['percentage'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Messages --}}
    @if($successMessage)
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-800">{{ $successMessage }}</p>
        </div>
    @endif

    @if($errorMessage)
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-800">{{ $errorMessage }}</p>
        </div>
    @endif

    {{-- Formularz --}}
    <form wire:submit.prevent="generatePlan" class="space-y-6">

        {{-- Tytuł planu --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Tytuł planu <span class="text-red-500">*</span>
            </label>
            <input type="text"
                   wire:model.blur="title"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                   placeholder="np. Wakacje w Toskanii">
            @error('title')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Destynacja --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Destynacja <span class="text-red-500">*</span>
            </label>
            <input type="text"
                   wire:model.blur="destination"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                   placeholder="np. Florencja, Włochy">
            @error('destination')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Data wyjazdu --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Data wyjazdu <span class="text-red-500">*</span>
            </label>
            <input type="date"
                   wire:model.blur="start_date"
                   min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            @error('start_date')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @if($endDate)
                <p class="mt-1 text-sm text-gray-600">
                    Data powrotu: {{ $endDate->format('d.m.Y') }}
                </p>
            @endif
        </div>

        {{-- Liczba dni i osób (grid) --}}
        <div class="grid grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Liczba dni <span class="text-red-500">*</span>
                </label>
                <input type="number"
                       wire:model.blur="days_count"
                       min="1"
                       max="30"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('days_count')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Liczba osób <span class="text-red-500">*</span>
                </label>
                <input type="number"
                       wire:model.blur="people_count"
                       min="1"
                       max="10"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('people_count')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Budżet --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Szacunkowy budżet na osobę (opcjonalnie)
            </label>
            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-2">
                    <input type="number"
                           wire:model.blur="budget_per_person"
                           min="0"
                           step="0.01"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="np. 1500">
                    @error('budget_per_person')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <select wire:model="selectedCurrency"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @foreach($currencies as $currency)
                            <option value="{{ $currency }}">{{ $currency }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @if($totalBudget)
                <p class="mt-1 text-sm text-gray-600">
                    Całkowity budżet: {{ number_format($totalBudget, 2) }} {{ $selectedCurrency }}
                </p>
            @endif
        </div>

        {{-- Notatki --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Twoje pomysły i notatki (opcjonalnie)
            </label>
            <textarea wire:model.blur="notes"
                      rows="6"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                      placeholder="Opisz czego oczekujesz od tej wycieczki, jakie miejsca chcesz odwiedzić, czy masz jakieś specjalne preferencje..."></textarea>
            @error('notes')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-sm text-gray-500">
                {{ strlen($notes ?? '') }}/5000 znaków
            </p>
        </div>

        {{-- Przyciski akcji --}}
        <div class="flex gap-4 pt-4">
            <button type="submit"
                    wire:loading.attr="disabled"
                    wire:target="generatePlan"
                    @if(!$canGenerate) disabled @endif
                    class="flex-1 px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors">
                <span wire:loading.remove wire:target="generatePlan">
                    Generuj plan AI
                </span>
                <span wire:loading wire:target="generatePlan" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Generowanie...
                </span>
            </button>

            <button type="button"
                    wire:click="saveAsDraft"
                    wire:loading.attr="disabled"
                    wire:target="saveAsDraft"
                    class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 focus:ring-4 focus:ring-gray-100 disabled:bg-gray-100 disabled:cursor-not-allowed transition-colors">
                <span wire:loading.remove wire:target="saveAsDraft">
                    Zapisz jako szkic
                </span>
                <span wire:loading wire:target="saveAsDraft">
                    Zapisywanie...
                </span>
            </button>
        </div>

        @if(!$canGenerate)
            <p class="text-sm text-center text-red-600">
                {{ $limitInfo['displayText'] }}. Możesz nadal zapisywać szkice.
            </p>
        @endif
    </form>
</div>
```

### Krok 8: Konfiguracja routes

```php
// routes/web.php

use App\Livewire\Plans\CreatePlanForm;

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Create plan
    Route::get('/plans/create', CreatePlanForm::class)
        ->name('plans.create');

    // Edit plan
    Route::get('/plans/{travel}/edit', CreatePlanForm::class)
        ->name('plans.edit')
        ->middleware('can:update,travel');

    // Show plan (do stworzenia osobno)
    Route::get('/plans/{travel}', ShowPlan::class)
        ->name('plans.show')
        ->middleware('can:view,travel');
});
```

### Krok 9: Konfiguracja queue i Redis

```env
# .env

QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

```bash
# Uruchomienie queue worker
php artisan queue:work --queue=ai-generation

# Production: supervisor config
# /etc/supervisor/conf.d/laravel-worker.conf
```

### Krok 10: Testy

#### 10.1 Unit test - LimitService

```bash
php artisan make:test Services/LimitServiceTest --unit
```

```php
// tests/Unit/Services/LimitServiceTest.php

<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\LimitService;
use App\Models\User;
use App\Models\AIGeneration;
use App\Exceptions\LimitExceededException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LimitServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LimitService $limitService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->limitService = new LimitService();
    }

    /** @test */
    public function it_returns_zero_generations_for_new_user()
    {
        $user = User::factory()->create();

        $count = $this->limitService->getGenerationCount($user->id);

        $this->assertEquals(0, $count);
    }

    /** @test */
    public function it_increments_generation_count()
    {
        $user = User::factory()->create();

        $this->limitService->incrementGenerationCount($user->id);
        $count = $this->limitService->getGenerationCount($user->id);

        $this->assertEquals(1, $count);
    }

    /** @test */
    public function it_throws_exception_when_limit_exceeded()
    {
        $user = User::factory()->create();

        // Stworzenie 10 generowań (limit)
        AIGeneration::factory()->count(10)->create([
            'user_id' => $user->id,
            'generated_at' => now(),
        ]);

        $this->expectException(LimitExceededException::class);
        $this->limitService->incrementGenerationCount($user->id);
    }

    /** @test */
    public function it_only_counts_current_month_generations()
    {
        $user = User::factory()->create();

        // Generowanie z poprzedniego miesiąca
        AIGeneration::factory()->create([
            'user_id' => $user->id,
            'generated_at' => now()->subMonth(),
        ]);

        $count = $this->limitService->getGenerationCount($user->id);

        $this->assertEquals(0, $count);
    }
}
```

#### 10.2 Feature test - CreatePlanForm

```bash
php artisan make:test Livewire/CreatePlanFormTest
```

```php
// tests/Feature/Livewire/CreatePlanFormTest.php

<?php

namespace Tests\Feature\Livewire;

use Tests\TestCase;
use App\Livewire\Plans\CreatePlanForm;
use App\Models\User;
use App\Models\Travel;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreatePlanFormTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function component_renders_successfully()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreatePlanForm::class)
            ->assertStatus(200);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreatePlanForm::class)
            ->call('saveAsDraft')
            ->assertHasErrors(['title', 'destination', 'start_date', 'days_count', 'people_count']);
    }

    /** @test */
    public function it_saves_plan_as_draft()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreatePlanForm::class)
            ->set('title', 'Test Plan')
            ->set('destination', 'Paris')
            ->set('start_date', now()->addDays(7)->format('Y-m-d'))
            ->set('days_count', 5)
            ->set('people_count', 2)
            ->call('saveAsDraft')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('travels', [
            'user_id' => $user->id,
            'title' => 'Test Plan',
            'status' => 'draft',
        ]);
    }
}
```

### Krok 11: Code review i refactoring

**Checklist:**
- [ ] Wszystkie metody mają odpowiednie type hints
- [ ] Kod jest zgodny z PSR-12
- [ ] Brak duplikacji (DRY principle)
- [ ] Service Layer oddzielony od UI
- [ ] Wszystkie inputs są walidowane
- [ ] Błędy są obsługiwane gracefully
- [ ] Testy pokrywają kluczowe scenariusze
- [ ] Dokumentacja (PHPDoc) dla publicznych metod
- [ ] Security best practices zastosowane
- [ ] Performance considerations (N+1 queries, cache)

```bash
# Laravel Pint (code style)
./vendor/bin/pint

# PHPStan (static analysis)
./vendor/bin/phpstan analyse

# Pest/PHPUnit (tests)
php artisan test
```

---

## 8. Przykłady użycia

### 8.1 Tworzenie nowego planu

**Flow użytkownika:**
1. Kliknięcie "Stwórz nowy plan" na dashboard
2. Wypełnienie formularza
3. Kliknięcie "Generuj plan AI"
4. Przekierowanie do widoku generowania (loading state)
5. Po ~30s: wyświetlenie wygenerowanego planu

### 8.2 Zapisanie jako szkic

**Flow użytkownika:**
1. Wypełnienie formularza częściowo
2. Kliknięcie "Zapisz jako szkic"
3. Przekierowanie do dashboard
4. Szkic widoczny na liście z opcją "Generuj plan"

### 8.3 Edycja istniejącego planu

**Flow użytkownika:**
1. Kliknięcie "Edytuj" przy planie
2. Formularz załadowany z danymi
3. Modyfikacja pól
4. Zapisanie lub regeneracja

---

## 9. Optymalizacje i best practices

### 9.1 Performance

1. **Eager loading** relacji
   ```php
   $travels = Travel::with('user', 'aiGenerations')->get();
   ```

2. **Cache limitów**
   ```php
   Cache::remember("user:{$userId}:limit", 3600, function() use ($userId) {
       return $this->limitService->getGenerationCount($userId);
   });
   ```

3. **Database indexes**
   - Index na `user_id`, `status`, `start_date`
   - Composite index na `(user_id, generated_at)` dla limitów

### 9.2 Skalowanie

1. **Horizontal scaling** - wiele queue workers
2. **Redis cluster** dla wysokiej dostępności
3. **CDN** dla static assets
4. **Database read replicas** dla raportów

### 9.3 Monitoring

1. **Laravel Telescope** (development)
2. **Laravel Horizon** (queue monitoring)
3. **Custom metrics** (Prometheus/Grafana)
4. **Error tracking** (Sentry)

---

## 10. Podsumowanie

Przewodnik implementacji przedstawia kompleksowe podejście do stworzenia formularza tworzenia/edycji planu wycieczki w aplikacji VibeTravels. Kluczowe elementy:

1. **Architektura warstwowa** - separacja UI (Livewire), logiki biznesowej (Services), i danych (Models)
2. **Bezpieczeństwo** - walidacja, autoryzacja, sanityzacja, rate limiting
3. **Skalowalność** - asynchroniczne przetwarzanie, kolejki, cache
4. **Obsługa błędów** - graceful degradation, rollback, monitoring
5. **Developer Experience** - czytelny kod, testy, dokumentacja

Implementacja zgodna z Laravel best practices i przygotowana na przyszłe rozszerzenia (premium plans, collaborative editing, itp.).
