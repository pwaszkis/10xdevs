# Plan implementacji widoku Szczegółów Planu

## 1. Przegląd

Widok Szczegółów Planu (`/plans/{id}`) jest najbardziej krytycznym elementem aplikacji VibeTravels. Odpowiada za wyświetlenie wygenerowanego przez AI planu podróży lub szkicu planu, prezentując pełną strukturę dzień po dniu z możliwością interakcji użytkownika. Widok obsługuje zarówno drafty (bez treści AI) jak i w pełni wygenerowane plany, umożliwiając użytkownikom przeglądanie, eksport do PDF, udzielanie feedbacku oraz regenerację planów.

Widok charakteryzuje się złożoną strukturą hierarchiczną (3 poziomy zagnieżdżenia: Plan → Days → Points) oraz zaawansowanymi interakcjami (accordion, expandable cards, collapsible sections). Implementacja wykorzystuje Livewire 3 do zarządzania stanem po stronie serwera oraz Alpine.js do lokalnych interakcji (expand/collapse, progressive disclosure).

## 2. Routing widoku

### Definicja routingu

```php
// routes/web.php
use App\Livewire\Plans\Show as PlansShow;

Route::middleware(['auth', 'verified', 'onboarding.completed'])->group(function () {
    Route::get('/plans/{id}', PlansShow::class)
        ->name('plans.show');
});
```

### Middleware chain

1. **auth** - wymaga zalogowanego użytkownika
2. **verified** - wymaga zweryfikowanego adresu email
3. **onboarding.completed** - wymaga ukończonego onboardingu

### Parametry routingu

- `{id}` - ID planu podróży (integer, wymagany)
- Walidacja własności planu odbywa się w komponencie Livewire (row-level security)

## 3. Struktura komponentów

### Hierarchia komponentów

```
PlansShow (Full-page Livewire Component)
├── PlanHeader (Nested Livewire Component)
├── AssumptionsSection (Nested Livewire Component)
│   └── PreferenceBadge (Nested Livewire Component) [multiple]
├── PlanDay (Nested Livewire Component) [multiple]
│   └── PlanPoint (Nested Livewire Component) [multiple]
├── FeedbackForm (Nested Livewire Component)
└── PlanActions (Nested Livewire Component)
```

### Layout

```
App Layout (resources/views/layouts/app.blade.php)
└── PlansShow Component
```

### Komponenty zewnętrzne

- **Wire UI**: Używane dla modali, alertów, notyfikacji
- **Alpine.js**: Używane dla lokalnych stanów (expand/collapse)

## 4. Szczegóły komponentów

### 4.1 PlansShow (Komponent główny)

**Opis:**
Komponent full-page Livewire odpowiedzialny za zarządzanie całym widokiem szczegółów planu. Obsługuje pobieranie danych planu z API, zarządzanie stanem komponentu, akcje użytkownika (usuwanie, regeneracja) oraz routing do innych widoków.

**Główne elementy:**
- Header z metadanymi planu (tytuł, destynacja, daty, budżet, status)
- Sekcja "Twoje założenia" (collapsed by default)
- Lista dni planu (tylko dla planów wygenerowanych)
- Footer z akcjami (feedback, export PDF, regeneracja)
- Modals dla potwierdzenia akcji destruktywnych

**Obsługiwane zdarzenia:**
- `mount($id)` - Inicjalizacja komponentu, pobieranie danych planu
- `deletePlan()` - Usunięcie planu (z potwierdzeniem modal)
- `confirmDelete()` - Potwierdzenie usunięcia planu
- `regeneratePlan()` - Inicjalizacja regeneracji (z warning modal)
- `confirmRegenerate()` - Potwierdzenie regeneracji planu
- `exportPdf()` - Eksport planu do PDF (trigger download)
- `refreshPlan()` - Odświeżenie danych planu (polling podczas generowania)

**Warunki walidacji:**
- Plan musi należeć do zalogowanego użytkownika (403 Forbidden jeśli nie)
- Plan musi istnieć i nie być soft-deleted (404 Not Found jeśli nie)
- Regeneracja wymaga dostępnego limitu AI (10/month)
- Export PDF wymaga wygenerowanego planu (nie dla draftów)
- Usunięcie planu wymaga potwierdzenia użytkownika

**Typy (DTO i ViewModel):**
- `TravelPlanDTO` - DTO dla podstawowych danych planu
- `PlanDayViewModel` - ViewModel dla pojedynczego dnia
- `PlanPointViewModel` - ViewModel dla pojedynczego punktu
- `FeedbackViewModel` - ViewModel dla feedbacku
- `UserPreferencesDTO` - DTO dla preferencji użytkownika (w założeniach)

**Propsy:**
- Brak (komponent full-page przyjmuje parametr z routingu: `$id`)

**Livewire properties:**
```php
// Model
public TravelPlan $plan;
public ?Feedback $feedback = null;

// UI State
public bool $showAssumptions = false;
public bool $showDeleteModal = false;
public bool $showRegenerateModal = false;
public bool $isExportingPdf = false;
public bool $isGenerating = false;
public int $generationProgress = 0;

// User context
public int $aiGenerationsRemaining;
public int $aiGenerationsLimit = 10;
```

---

### 4.2 PlanHeader

**Opis:**
Nested Livewire component wyświetlający header planu z kluczowymi metadanymi oraz badge statusu. Komponent jest statyczny (read-only), nie obsługuje edycji.

**Główne elementy:**
- Tytuł planu (`<h1>`)
- Destynacja (ikona + tekst)
- Zakres dat (ikona + "od DD.MM.YYYY do DD.MM.YYYY")
- Liczba osób (ikona + liczba)
- Budżet (opcjonalnie, jeśli podany: ikona + kwota + waluta)
- Status badge (Draft/Planned/Completed z odpowiednim kolorem)

**Obsługiwane interakcje:**
- Brak (komponent read-only)

**Obsługiwana walidacja:**
- Brak (tylko prezentacja danych)

**Typy:**
- `TravelPlanDTO` - dane planu

**Propsy:**
```php
#[Prop]
public TravelPlan $plan;
```

---

### 4.3 AssumptionsSection

**Opis:**
Nested Livewire component wyświetlający sekcję "Twoje założenia" (collapsed by default). Pokazuje oryginalne notatki użytkownika oraz preferencje użyte podczas generowania planu (kategorie zainteresowań, parametry praktyczne).

**Główne elementy:**
- Link "Zobacz Twoje założenia ▼" (toggle expand/collapse)
- Expanded content:
  - User notes (textarea content, formatted)
  - Preference badges grid (kategorie zainteresowań)
  - Practical parameters (tempo, budżet, transport, ograniczenia)

**Obsługiwane interakcje:**
- `toggleAssumptions()` - Toggle expand/collapse sekcji (Alpine.js local state)

**Obsługiwana walidacja:**
- Brak (tylko prezentacja danych)

**Typy:**
- `TravelPlanDTO` - notatki użytkownika
- `UserPreferencesDTO` - preferencje użytkownika

**Propsy:**
```php
#[Prop]
public ?string $userNotes = null;

#[Prop]
public array $preferences = [];

#[Prop]
public bool $expanded = false; // Kontrola stanu z poziomu Alpine.js
```

---

### 4.4 PreferenceBadge

**Opis:**
Mały nested Livewire component (lub Blade partial) wyświetlający pojedynczy badge preferencji (np. "Historia i kultura", "Gastronomia"). Reużywalny komponent.

**Główne elementy:**
- Badge z ikoną i tekstem
- Kolor/style zależny od kategorii

**Obsługiwane interakcje:**
- Brak (statyczny badge)

**Obsługiwana walidacja:**
- Brak

**Typy:**
- `string` - nazwa kategorii preferencji

**Propsy:**
```php
#[Prop]
public string $category;

#[Prop]
public ?string $icon = null; // Opcjonalna ikona
```

---

### 4.5 PlanDay

**Opis:**
Nested Livewire component reprezentujący pojedynczy dzień w planie. Implementuje wzorzec accordion - każdy dzień może być expanded/collapsed. Zawiera listę PlanPoint komponentów.

**Główne elementy:**
- Header dnia:
  - "Dzień {number} - DD.MM.YYYY"
  - Expand/collapse icon
  - Summary (opcjonalnie, jeśli dostępne)
- Content (expanded):
  - Lista PlanPoint komponentów pogrupowanych po porze dnia
  - Separatory między porami dnia (rano, południe, popołudnie, wieczór)

**Obsługiwane interakcje:**
- `toggleExpand()` - Toggle expand/collapse dnia
- Keyboard navigation: Enter = toggle expand

**Obsługiwana walidacja:**
- Brak (tylko prezentacja danych)

**Typy:**
- `PlanDayViewModel` - dane dnia

**Propsy:**
```php
#[Prop]
public array $day; // Dane dnia z API (day_number, date, summary, points)

#[Prop]
public bool $expanded = false; // Desktop: pierwszy dzień expanded, mobile: wszystkie collapsed

#[Prop]
public bool $isMobile = false; // Kontrola domyślnego stanu
```

**Alpine.js local state:**
```javascript
x-data="{
  expanded: @entangle('expanded'),
  toggle() {
    this.expanded = !this.expanded;
  }
}"
```

---

### 4.6 PlanPoint

**Opis:**
Nested Livewire component reprezentujący pojedynczy punkt w planie dnia (atrakcja/miejsce do odwiedzenia). Implementuje progressive disclosure - collapsed state pokazuje podstawowe info, expanded pokazuje pełne szczegóły.

**Główne elementy:**
- Collapsed state:
  - Ikona pory dnia (🌅 rano, ☀️ południe, 🌇 popołudnie, 🌙 wieczór)
  - Nazwa punktu
  - Czas trwania (np. "2h 30min")
- Expanded state (click anywhere na card):
  - Nazwa (h3)
  - Opis (2-3 zdania)
  - Uzasadnienie dopasowania (italic, mniejsza czcionka)
  - Czas wizyty (ikona ⏱️ + tekst)
  - Google Maps link: "📍 Zobacz na mapie" (target="_blank", rel="noopener")

**Obsługiwane interakcje:**
- `toggleExpand()` - Toggle expand/collapse punktu
- Click na całym card area (nie tylko na ikonie)
- Keyboard navigation: Enter = toggle expand

**Obsługiwana walidacja:**
- Brak (tylko prezentacja danych)

**Typy:**
- `PlanPointViewModel` - dane punktu

**Propsy:**
```php
#[Prop]
public array $point; // Dane punktu z API

#[Prop]
public bool $expanded = false; // Domyślnie collapsed
```

**Alpine.js local state:**
```javascript
x-data="{
  expanded: false,
  toggle() {
    this.expanded = !this.expanded;
  }
}"
```

---

### 4.7 FeedbackForm

**Opis:**
Nested Livewire component wyświetlający formularz feedbacku (inline, collapsed by default). Pozwala użytkownikowi ocenić wygenerowany plan i zgłosić problemy.

**Główne elementy:**
- Collapsed state:
  - Link "Oceń ten plan" lub "Dodaj feedback ▼"
- Expanded state:
  - Pytanie: "Czy plan spełnia Twoje oczekiwania?"
  - Przyciski: "Tak" / "Nie"
  - Jeśli "Nie", pokazują się checkboxy:
    - Za mało szczegółów
    - Nie pasuje do moich preferencji
    - Słaba kolejność zwiedzania
    - Inne (z opcjonalnym polem tekstowym)
  - Przycisk "Wyślij feedback"
  - Przycisk "Anuluj" (collapse form)

**Obsługiwane interakcje:**
- `toggleForm()` - Toggle expand/collapse formularza
- `submitFeedback()` - Wysłanie feedbacku do API
- `selectSatisfied(bool $satisfied)` - Wybór Tak/Nie
- `toggleIssue(string $issue)` - Toggle checkbox problemu

**Obsługiwana walidacja:**
- `satisfied` - required, boolean
- `issues` - required_if:satisfied,false, array, max:4
- `other_comment` - nullable, max:1000 characters
- Walidacja po stronie API (Laravel Validation), Livewire wyświetla błędy

**Typy:**
- `FeedbackDTO` - DTO dla feedbacku

**Propsy:**
```php
#[Prop]
public int $travelPlanId;

#[Prop]
public ?Feedback $existingFeedback = null; // Jeśli feedback już istnieje
```

**Livewire properties:**
```php
public bool $showForm = false;
public ?bool $satisfied = null;
public array $issues = [];
public ?string $otherComment = null;
public bool $isSubmitting = false;
```

**Validation rules:**
```php
protected function rules()
{
    return [
        'satisfied' => 'required|boolean',
        'issues' => 'required_if:satisfied,false|array|max:4',
        'issues.*' => 'in:za_malo_szczegolow,nie_pasuje_do_preferencji,slaba_kolejnosc,inne',
        'otherComment' => 'nullable|string|max:1000',
    ];
}
```

---

### 4.8 PlanActions

**Opis:**
Nested Livewire component zawierający akcje dla planu (Export PDF, Regeneruj plan, Usuń plan). Grupuje wszystkie primary/secondary actions w jednym miejscu.

**Główne elementy:**
- Przycisk "Export do PDF" (primary action)
- Przycisk "Regeneruj plan" (secondary action, z warningiem)
- Przycisk "Usuń plan" (destructive action)

**Obsługiwane interakcje:**
- `exportPdf()` - Deleguje do rodzica (PlansShow)
- `regeneratePlan()` - Deleguje do rodzica (PlansShow)
- `deletePlan()` - Deleguje do rodzica (PlansShow)

**Obsługiwana walidacja:**
- Export PDF: tylko dla planów z statusem "planned" lub "completed"
- Regeneracja: wymaga dostępnego limitu AI
- Usunięcie: brak dodatkowych warunków

**Typy:**
- Brak (tylko akcje)

**Propsy:**
```php
#[Prop]
public string $status; // Status planu (draft/planned/completed)

#[Prop]
public int $aiGenerationsRemaining;

#[Prop]
public bool $hasAiPlan; // Czy plan ma wygenerowaną treść AI
```

---

## 5. Typy

### TravelPlanDTO (istniejący)

```php
readonly class TravelPlanDTO
{
    public function __construct(
        public string $destination,
        public Carbon $startDate,
        public Carbon $endDate,
        public ?float $budget = null,
        public ?string $currency = null,
        public int $travelersCount = 1,
        public array $preferences = [],
        public ?string $notes = null,
        public string $status = 'draft',
    ) {}

    public static function fromArray(array $data): self { /* ... */ }
    public function toArray(): array { /* ... */ }
    public function getDurationInDays(): int { /* ... */ }
}
```

### PlanDayViewModel (nowy)

```php
readonly class PlanDayViewModel
{
    public function __construct(
        public int $dayNumber,
        public Carbon $date,
        public ?string $summary = null,
        public array $points = [], // Array of PlanPointViewModel
    ) {}

    /**
     * Grupowanie punktów po porze dnia
     */
    public function getPointsByDayPart(): array
    {
        return collect($this->points)
            ->groupBy('dayPart')
            ->toArray();
    }

    public static function fromArray(array $data): self
    {
        return new self(
            dayNumber: $data['day_number'],
            date: Carbon::parse($data['date']),
            summary: $data['summary'] ?? null,
            points: collect($data['points'] ?? [])
                ->map(fn($point) => PlanPointViewModel::fromArray($point))
                ->toArray(),
        );
    }
}
```

### PlanPointViewModel (nowy)

```php
readonly class PlanPointViewModel
{
    public function __construct(
        public int $id,
        public int $orderNumber,
        public string $dayPart, // rano, poludnie, popołudnie, wieczór
        public string $name,
        public string $description,
        public string $justification,
        public int $durationMinutes,
        public string $googleMapsUrl,
        public ?float $locationLat = null,
        public ?float $locationLng = null,
    ) {}

    /**
     * Format czasu trwania (np. "2h 30min")
     */
    public function getFormattedDuration(): string
    {
        $hours = floor($this->durationMinutes / 60);
        $minutes = $this->durationMinutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}min";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}min";
        }
    }

    /**
     * Ikona pory dnia
     */
    public function getDayPartIcon(): string
    {
        return match($this->dayPart) {
            'rano' => '🌅',
            'poludnie' => '☀️',
            'popołudnie' => '🌇',
            'wieczór' => '🌙',
            default => '📍',
        };
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            orderNumber: $data['order_number'],
            dayPart: $data['day_part'],
            name: $data['name'],
            description: $data['description'],
            justification: $data['justification'],
            durationMinutes: $data['duration_minutes'],
            googleMapsUrl: $data['google_maps_url'],
            locationLat: $data['location_lat'] ?? null,
            locationLng: $data['location_lng'] ?? null,
        );
    }
}
```

### FeedbackDTO (nowy)

```php
readonly class FeedbackDTO
{
    public function __construct(
        public bool $satisfied,
        public ?array $issues = null,
        public ?string $otherComment = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            satisfied: $data['satisfied'],
            issues: $data['issues'] ?? null,
            otherComment: $data['other_comment'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'satisfied' => $this->satisfied,
            'issues' => $this->issues,
            'other_comment' => $this->otherComment,
        ];
    }
}
```

### UserPreferencesDTO (nowy)

```php
readonly class UserPreferencesDTO
{
    public function __construct(
        public array $interestsCategories = [],
        public ?string $travelPace = null,
        public ?string $budgetLevel = null,
        public ?string $transportPreference = null,
        public ?string $restrictions = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            interestsCategories: $data['interests_categories'] ?? [],
            travelPace: $data['travel_pace'] ?? null,
            budgetLevel: $data['budget_level'] ?? null,
            transportPreference: $data['transport_preference'] ?? null,
            restrictions: $data['restrictions'] ?? null,
        );
    }

    /**
     * Mapa kategorii na czytelne nazwy
     */
    public function getReadableCategories(): array
    {
        return collect($this->interestsCategories)
            ->map(fn($cat) => match($cat) {
                'historia_kultura' => 'Historia i kultura',
                'przyroda_outdoor' => 'Przyroda i outdoor',
                'gastronomia' => 'Gastronomia',
                'nocne_zycie' => 'Nocne życie i rozrywka',
                'plaze_relaks' => 'Plaże i relaks',
                'sporty_aktywnosci' => 'Sporty i aktywności',
                'sztuka_muzea' => 'Sztuka i muzea',
                default => $cat,
            })
            ->toArray();
    }

    /**
     * Czytelna nazwa dla travel_pace
     */
    public function getReadableTravelPace(): ?string
    {
        return match($this->travelPace) {
            'spokojne' => 'Spokojne',
            'umiarkowane' => 'Umiarkowane',
            'intensywne' => 'Intensywne',
            default => null,
        };
    }

    // Podobne metody dla innych parametrów...
}
```

## 6. Zarządzanie stanem

### Strategia zarządzania stanem

**Hybrid Approach: Livewire Server-Side State + Alpine.js Client-Side State**

#### Livewire Server-Side State (Pessimistic Updates)

Wszystkie dane pochodzące z API oraz operacje modyfikujące stan są zarządzane przez Livewire:

- **Plan data** - pobierane z API podczas `mount()`, przechowywane w `public TravelPlan $plan`
- **Feedback data** - pobierane z API, przechowywane w `public ?Feedback $feedback`
- **AI generation status** - polling przez `refreshPlan()` co 3-5 sekund
- **Form state** (FeedbackForm) - zarządzane przez Livewire properties
- **Modal state** (delete, regenerate) - zarządzane przez Livewire boolean flags

**Strategia wire:model:**
- `wire:model.blur` - dla pól textarea (feedback comments)
- `wire:model.live` - dla checkboxów (issues selection)
- `wire:model.defer` - dla innych form inputs (optymalizacja)

#### Alpine.js Client-Side State (Optimistic UI)

Wszystkie lokalne interakcje UI (nie wymagające synchronizacji z serwerem):

- **Accordion expand/collapse** (PlanDay) - `x-data="{ expanded: false }"`
- **Card expand/collapse** (PlanPoint) - `x-data="{ expanded: false }"`
- **Assumptions section toggle** - `x-data="{ showAssumptions: false }"`
- **Smooth animations** - Alpine.js transitions (`x-transition`)

**Alpine.js patterns:**
```javascript
// PlanDay accordion
x-data="{
  expanded: {{ $expanded ? 'true' : 'false' }},
  toggle() {
    this.expanded = !this.expanded;
  }
}"

// PlanPoint expandable card
x-data="{
  expanded: false,
  toggle() {
    this.expanded = !this.expanded;
  }
}"

// Assumptions section
x-data="{
  showAssumptions: false,
  toggle() {
    this.showAssumptions = !this.showAssumptions;
  }
}"
```

### Livewire Lifecycle Hooks

```php
class Show extends Component
{
    // Inicjalizacja komponentu
    public function mount($id)
    {
        $this->loadPlan($id);
        $this->loadUserContext();
    }

    // Hydration po każdym request
    public function hydrate()
    {
        // Refresh user context (AI limits mogły się zmienić)
        $this->aiGenerationsRemaining = $this->getUserAiGenerationsRemaining();
    }

    // Updated hook (po każdej zmianie property)
    public function updated($propertyName)
    {
        // Real-time validation dla feedbacku
        if (str_starts_with($propertyName, 'feedback.')) {
            $this->validateOnly($propertyName);
        }
    }
}
```

### Computed Properties z Cache

```php
use Livewire\Attributes\Computed;

#[Computed]
public function daysGrouped()
{
    return collect($this->plan->days)
        ->groupBy(fn($day) => ceil($day->dayNumber / 5)) // Grupowanie po 5 dni (lazy loading)
        ->toArray();
}

#[Computed]
public function canRegenerate(): bool
{
    return $this->plan->status !== 'draft'
        && $this->aiGenerationsRemaining > 0;
}

#[Computed]
public function canExportPdf(): bool
{
    return $this->plan->status !== 'draft'
        && $this->plan->has_ai_plan === true;
}
```

### Lazy Loading Strategy

**Problem:** Plany z 20-30 dniami generują duży DOM (performance issue).

**Rozwiązanie:** Lazy loading dni planów:

```php
// Komponent główny
public int $loadedDaysCount = 3; // Początkowo ładujemy tylko 3 dni

public function loadMoreDays()
{
    $this->loadedDaysCount += 5; // Ładujemy kolejne 5 dni
}

// W blade template
@foreach($plan->days->take($loadedDaysCount) as $day)
    <livewire:components.plan-day :day="$day" :key="'day-'.$day->id" />
@endforeach

@if($plan->days->count() > $loadedDaysCount)
    <button wire:click="loadMoreDays" class="btn-secondary">
        Pokaż więcej dni ({{ $plan->days->count() - $loadedDaysCount }} pozostałych)
    </button>
@endif
```

## 7. Integracja API

### Endpointy API wykorzystywane w widoku

#### GET /api/travel-plans/{id}

**Typ żądania:**
`GET /api/travel-plans/{id}?include=days,days.points,feedback`

**Typ odpowiedzi:**
```typescript
{
  data: {
    id: number,
    title: string,
    destination: string,
    departure_date: string, // YYYY-MM-DD
    number_of_days: number,
    number_of_people: number,
    budget_per_person: number | null,
    budget_currency: string | null,
    user_notes: string | null,
    status: 'draft' | 'planned' | 'completed',
    created_at: string,
    updated_at: string,
    days: Array<{
      id: number,
      day_number: number,
      date: string,
      summary: string | null,
      points: Array<{
        id: number,
        order_number: number,
        day_part: 'rano' | 'poludnie' | 'popołudnie' | 'wieczór',
        name: string,
        description: string,
        justification: string,
        duration_minutes: number,
        google_maps_url: string,
        location_lat: number | null,
        location_lng: number | null
      }>
    }>,
    feedback: {
      id: number,
      satisfied: boolean,
      issues: string[] | null,
      other_comment: string | null,
      created_at: string
    } | null
  }
}
```

**Implementacja w Livewire:**
```php
use Illuminate\Support\Facades\Http;

protected function loadPlan(int $id): void
{
    $response = Http::get("/api/travel-plans/{$id}", [
        'include' => 'days,days.points,feedback'
    ]);

    if ($response->status() === 403) {
        abort(403, 'Ten plan nie należy do Ciebie.');
    }

    if ($response->status() === 404) {
        abort(404, 'Plan nie został znaleziony.');
    }

    if ($response->status() === 500) {
        session()->flash('error', 'Błąd podczas ładowania planu. Spróbuj ponownie.');
        return redirect()->route('dashboard');
    }

    $data = $response->json('data');

    // Hydrate Eloquent model (dla lepszej kompatybilności)
    $this->plan = TravelPlan::hydrate([$data])->first();
    $this->feedback = $data['feedback'] ? Feedback::make($data['feedback']) : null;
}
```

---

#### DELETE /api/travel-plans/{id}

**Typ żądania:**
`DELETE /api/travel-plans/{id}`

**Typ odpowiedzi:**
```typescript
{
  message: string
}
```

**Implementacja w Livewire:**
```php
public function confirmDelete(): void
{
    $response = Http::delete("/api/travel-plans/{$this->plan->id}");

    if ($response->successful()) {
        session()->flash('success', 'Plan został usunięty.');
        return redirect()->route('dashboard');
    } else {
        session()->flash('error', 'Nie udało się usunąć planu. Spróbuj ponownie.');
        $this->showDeleteModal = false;
    }
}
```

---

#### POST /api/travel-plans/{id}/generate

**Typ żądania:**
`POST /api/travel-plans/{id}/generate`

**Typ odpowiedzi (202 Accepted):**
```typescript
{
  message: string,
  data: {
    generation_id: number,
    travel_plan_id: number,
    status: 'pending',
    started_at: null,
    estimated_duration_seconds: 30
  }
}
```

**Typ odpowiedzi (429 Too Many Requests):**
```typescript
{
  message: string,
  data: {
    current_count: number,
    max_count: number,
    reset_at: string
  }
}
```

**Implementacja w Livewire:**
```php
public function confirmRegenerate(): void
{
    $response = Http::post("/api/travel-plans/{$this->plan->id}/generate");

    if ($response->status() === 429) {
        $resetDate = Carbon::parse($response->json('data.reset_at'));
        session()->flash('error', "Osiągnięto limit generowań ({$this->aiGenerationsLimit}/miesiąc). Reset: {$resetDate->format('d.m.Y')}");
        $this->showRegenerateModal = false;
        return;
    }

    if ($response->successful()) {
        $this->isGenerating = true;
        $this->generationId = $response->json('data.generation_id');
        $this->startPollingGeneration();
    } else {
        session()->flash('error', 'Nie udało się rozpocząć regeneracji. Spróbuj ponownie.');
    }

    $this->showRegenerateModal = false;
}

protected function startPollingGeneration(): void
{
    // Polling przez Alpine.js lub Livewire polling
    $this->dispatch('start-generation-polling', generationId: $this->generationId);
}
```

---

#### GET /api/travel-plans/{id}/generation-status

**Typ żądania:**
`GET /api/travel-plans/{id}/generation-status`

**Typ odpowiedzi (processing):**
```typescript
{
  data: {
    generation_id: number,
    travel_plan_id: number,
    status: 'processing',
    progress_percentage: number,
    started_at: string,
    estimated_time_remaining_seconds: number
  }
}
```

**Typ odpowiedzi (completed):**
```typescript
{
  data: {
    generation_id: number,
    status: 'completed',
    completed_at: string,
    duration_seconds: number
  }
}
```

**Implementacja w Livewire:**
```php
use Livewire\Attributes\On;

#[On('poll-generation-status')]
public function checkGenerationStatus(): void
{
    if (!$this->isGenerating || !$this->generationId) {
        return;
    }

    $response = Http::get("/api/travel-plans/{$this->plan->id}/generation-status");

    if ($response->successful()) {
        $status = $response->json('data.status');

        if ($status === 'completed') {
            $this->isGenerating = false;
            $this->generationProgress = 100;
            $this->loadPlan($this->plan->id); // Reload planu z nowymi danymi
            session()->flash('success', 'Plan został pomyślnie wygenerowany!');
        } elseif ($status === 'failed') {
            $this->isGenerating = false;
            $errorMessage = $response->json('data.error_message');
            session()->flash('error', "Generowanie nie powiodło się: {$errorMessage}");
        } else {
            // Processing
            $this->generationProgress = $response->json('data.progress_percentage', 0);
        }
    }
}
```

**Polling implementation (Blade template):**
```blade
@if($isGenerating)
    <div wire:poll.3s="checkGenerationStatus">
        <div class="loading-spinner">
            Generowanie planu... {{ $generationProgress }}%
        </div>
    </div>
@endif
```

---

#### GET /api/travel-plans/{id}/pdf

**Typ żądania:**
`GET /api/travel-plans/{id}/pdf`

**Typ odpowiedzi:**
Binary PDF file (Content-Type: application/pdf)

**Implementacja w Livewire:**
```php
public function exportPdf(): void
{
    if (!$this->canExportPdf) {
        session()->flash('error', 'Nie można eksportować szkicu planu.');
        return;
    }

    $this->isExportingPdf = true;

    // Przekierowanie do endpointu PDF (browser trigger download)
    return redirect()->to("/api/travel-plans/{$this->plan->id}/pdf");
}
```

**Alternatywnie (JavaScript download):**
```blade
<button
    wire:click="exportPdf"
    x-on:click="window.open('/api/travel-plans/{{ $plan->id }}/pdf', '_blank')"
    class="btn-primary">
    Export do PDF
</button>
```

---

#### POST /api/travel-plans/{id}/feedback

**Typ żądania:**
`POST /api/travel-plans/{id}/feedback`

**Request body:**
```typescript
{
  satisfied: boolean,
  issues?: string[],
  other_comment?: string
}
```

**Typ odpowiedzi (201 Created):**
```typescript
{
  message: string,
  data: {
    id: number,
    travel_plan_id: number,
    satisfied: boolean,
    issues: string[] | null,
    other_comment: string | null,
    created_at: string
  }
}
```

**Implementacja w Livewire (FeedbackForm):**
```php
public function submitFeedback(): void
{
    $this->validate();

    $this->isSubmitting = true;

    $response = Http::post("/api/travel-plans/{$this->travelPlanId}/feedback", [
        'satisfied' => $this->satisfied,
        'issues' => $this->satisfied ? null : $this->issues,
        'other_comment' => $this->otherComment,
    ]);

    $this->isSubmitting = false;

    if ($response->status() === 400) {
        // Feedback już istnieje
        session()->flash('error', 'Feedback dla tego planu został już przesłany.');
        $this->showForm = false;
        return;
    }

    if ($response->successful()) {
        session()->flash('success', 'Dziękujemy za feedback!');
        $this->dispatch('feedback-submitted', feedback: $response->json('data'));
        $this->resetForm();
    } else {
        session()->flash('error', 'Nie udało się przesłać feedbacku. Spróbuj ponownie.');
    }
}

protected function resetForm(): void
{
    $this->satisfied = null;
    $this->issues = [];
    $this->otherComment = null;
    $this->showForm = false;
}
```

## 8. Interakcje użytkownika

### 8.1 Wyświetlenie szczegółów planu (szkic)

**Flow:**
1. Użytkownik klika na szkic planu w Dashboard
2. Routing: `/plans/{id}`
3. Livewire `mount()` pobiera dane z API
4. Wyświetlany jest header planu + sekcja "Twoje założenia"
5. Brak treści AI (dni/punktów)
6. CTA "Generuj plan" - redirectuje do generowania
7. Przycisk "Usuń plan" dostępny

**Komponenty zaangażowane:**
- PlansShow (main)
- PlanHeader
- AssumptionsSection
- PlanActions (tylko "Generuj plan" i "Usuń plan")

---

### 8.2 Wyświetlenie wygenerowanego planu

**Flow:**
1. Użytkownik klika na wygenerowany plan w Dashboard
2. Routing: `/plans/{id}`
3. Livewire `mount()` pobiera dane z API (include days, points, feedback)
4. Wyświetlany jest pełny plan:
   - Header z metadanymi
   - Sekcja "Twoje założenia" (collapsed)
   - Lista dni (accordion)
   - Footer z feedback form i akcjami

**Komponenty zaangażowane:**
- PlansShow
- PlanHeader
- AssumptionsSection
- PlanDay (multiple, accordion)
- PlanPoint (multiple, nested)
- FeedbackForm
- PlanActions

**UX details:**
- **Desktop:** Pierwszy dzień expanded, reszta collapsed
- **Mobile:** Wszystkie dni collapsed by default
- **Lazy loading:** Pierwsze 3 dni załadowane, "Pokaż więcej" dla reszty

---

### 8.3 Rozwijanie/zwijanie sekcji "Twoje założenia"

**Flow:**
1. Użytkownik klika na "Zobacz Twoje założenia ▼"
2. Alpine.js toggle `showAssumptions` (local state)
3. Smooth transition expand/collapse
4. Ikona zmienia się na ▲

**Implementacja Alpine.js:**
```blade
<div x-data="{ showAssumptions: false }">
    <button @click="showAssumptions = !showAssumptions" class="assumptions-toggle">
        Zobacz Twoje założenia
        <span x-show="!showAssumptions">▼</span>
        <span x-show="showAssumptions">▲</span>
    </button>

    <div x-show="showAssumptions" x-transition class="assumptions-content">
        <!-- User notes -->
        <!-- Preference badges -->
        <!-- Practical parameters -->
    </div>
</div>
```

**Accessibility:**
- `aria-expanded` attribute dynamiczny
- `aria-controls` wskazuje na content ID
- Keyboard navigation: Enter/Space toggle

---

### 8.4 Rozwijanie/zwijanie dni (accordion)

**Flow:**
1. Użytkownik klika na header dnia lub ikonę expand/collapse
2. Alpine.js toggle `expanded` (local state, entangled z Livewire dla desktop default)
3. Smooth transition expand/collapse
4. Punkty dnia stają się widoczne (grouped by day_part)

**Implementacja Alpine.js:**
```blade
<div
    x-data="{ expanded: @js($expanded) }"
    class="plan-day-card"
>
    <button
        @click="expanded = !expanded"
        @keydown.enter="expanded = !expanded"
        class="day-header"
        :aria-expanded="expanded"
    >
        <h3>Dzień {{ $day['day_number'] }} - {{ $day['date'] }}</h3>
        <svg x-show="!expanded" class="icon-expand">▼</svg>
        <svg x-show="expanded" class="icon-collapse">▲</svg>
    </button>

    <div x-show="expanded" x-transition class="day-content">
        @foreach($dayPartsGrouped as $dayPart => $points)
            <div class="day-part-section">
                <h4>{{ $dayPartLabel }}</h4>
                @foreach($points as $point)
                    <livewire:components.plan-point :point="$point" :key="'point-'.$point['id']" />
                @endforeach
            </div>
        @endforeach
    </div>
</div>
```

**UX details:**
- Click anywhere na header (nie tylko ikona)
- Keyboard navigation (Enter/Space)
- Smooth animation (300ms transition)
- Focus management po expand/collapse

---

### 8.5 Rozwijanie/zwijanie punktów planu

**Flow:**
1. Użytkownik klika na kartę punktu
2. Alpine.js toggle `expanded` (local state)
3. Progressive disclosure: collapsed → expanded state
4. Wyświetla się pełny opis, uzasadnienie, link Google Maps

**Implementacja Alpine.js:**
```blade
<div
    x-data="{ expanded: false }"
    @click="expanded = !expanded"
    class="plan-point-card"
    :class="{ 'expanded': expanded }"
>
    <!-- Collapsed state (always visible) -->
    <div class="point-collapsed">
        <span class="day-part-icon">{{ $point->getDayPartIcon() }}</span>
        <h4>{{ $point->name }}</h4>
        <span class="duration">{{ $point->getFormattedDuration() }}</span>
    </div>

    <!-- Expanded state (progressive disclosure) -->
    <div x-show="expanded" x-transition class="point-expanded">
        <p class="description">{{ $point->description }}</p>
        <p class="justification">{{ $point->justification }}</p>
        <div class="metadata">
            <span class="time">⏱️ {{ $point->getFormattedDuration() }}</span>
            <a href="{{ $point->googleMapsUrl }}" target="_blank" rel="noopener" class="maps-link">
                📍 Zobacz na mapie
            </a>
        </div>
    </div>
</div>
```

**Accessibility:**
- Cała karta jest clickable (nie tylko nazwa)
- Keyboard navigation (Enter/Space)
- `aria-expanded` dynamiczny
- Focus trap w expanded state (dla keyboard users)

---

### 8.6 Udzielenie feedbacku

**Flow:**
1. Użytkownik klika "Oceń ten plan" (toggle form)
2. Formularz expand (Alpine.js transition)
3. Użytkownik wybiera "Tak" lub "Nie"
4. Jeśli "Nie", pokazują się checkboxy z problemami
5. Użytkownik zaznacza problemy (opcjonalnie dodaje komentarz)
6. Kliknięcie "Wyślij feedback"
7. Livewire walidacja + POST do API
8. Success: komunikat "Dziękujemy za feedback!", formularz collapse
9. Error: wyświetlenie błędów walidacji

**Implementacja Livewire + Alpine.js:**
```blade
<div
    x-data="{ showForm: @entangle('showForm') }"
    class="feedback-section"
>
    <button
        @click="showForm = !showForm"
        x-show="!showForm"
        class="feedback-toggle"
    >
        Oceń ten plan ▼
    </button>

    <div x-show="showForm" x-transition class="feedback-form">
        <h3>Czy plan spełnia Twoje oczekiwania?</h3>

        <div class="satisfaction-buttons">
            <button
                wire:click="$set('satisfied', true)"
                :class="{ 'active': @js($satisfied === true) }"
            >
                👍 Tak
            </button>
            <button
                wire:click="$set('satisfied', false)"
                :class="{ 'active': @js($satisfied === false) }"
            >
                👎 Nie
            </button>
        </div>

        @if($satisfied === false)
            <div class="issues-checkboxes" x-transition>
                <label>
                    <input type="checkbox" wire:model.live="issues" value="za_malo_szczegolow">
                    Za mało szczegółów
                </label>
                <label>
                    <input type="checkbox" wire:model.live="issues" value="nie_pasuje_do_preferencji">
                    Nie pasuje do moich preferencji
                </label>
                <label>
                    <input type="checkbox" wire:model.live="issues" value="slaba_kolejnosc">
                    Słaba kolejność zwiedzania
                </label>
                <label>
                    <input type="checkbox" wire:model.live="issues" value="inne">
                    Inne
                </label>

                @if(in_array('inne', $issues))
                    <textarea
                        wire:model.blur="otherComment"
                        placeholder="Opisz problem..."
                        maxlength="1000"
                    ></textarea>
                @endif
            </div>
        @endif

        <div class="form-actions">
            <button
                wire:click="submitFeedback"
                wire:loading.attr="disabled"
                class="btn-primary"
            >
                <span wire:loading.remove>Wyślij feedback</span>
                <span wire:loading>Wysyłanie...</span>
            </button>
            <button
                @click="showForm = false"
                class="btn-secondary"
            >
                Anuluj
            </button>
        </div>

        @error('satisfied') <span class="error">{{ $message }}</span> @enderror
        @error('issues') <span class="error">{{ $message }}</span> @enderror
    </div>
</div>
```

---

### 8.7 Eksport planu do PDF

**Flow:**
1. Użytkownik klika "Export do PDF"
2. Livewire sprawdza `canExportPdf` (computed property)
3. Jeśli true: redirect do `/api/travel-plans/{id}/pdf`
4. Browser trigger download PDF
5. Tracking eksportu w bazie danych (API side)

**Implementacja:**
```blade
<button
    wire:click="exportPdf"
    @if(!$this->canExportPdf) disabled @endif
    class="btn-primary"
>
    <span wire:loading.remove wire:target="exportPdf">📄 Export do PDF</span>
    <span wire:loading wire:target="exportPdf">Generowanie...</span>
</button>
```

**Edge cases:**
- Draft plan: przycisk disabled z tooltipem "Wygeneruj plan, aby eksportować"
- Generation pending: przycisk disabled z tooltipem "Poczekaj na zakończenie generowania"

---

### 8.8 Regeneracja planu

**Flow:**
1. Użytkownik klika "Regeneruj plan"
2. Livewire sprawdza `canRegenerate` (limit AI dostępny?)
3. Wyświetla się modal z warningiem: "Spowoduje to wygenerowanie nowego planu (X/10 w tym miesiącu). Kontynuować?"
4. Użytkownik potwierdza → POST do API `/generate`
5. API zwraca 202 Accepted z `generation_id`
6. Livewire rozpoczyna polling `generation-status` (co 3-5s)
7. Loading state: "Generowanie planu... X%"
8. Po completion: reload planu, success notification
9. Po failure: error notification z możliwością retry

**Implementacja:**
```blade
<!-- Przycisk regeneracji -->
<button
    wire:click="regeneratePlan"
    @if(!$this->canRegenerate) disabled @endif
    class="btn-secondary"
>
    🔄 Regeneruj plan
</button>

<!-- Modal potwierdzenia -->
<x-modal wire:model="showRegenerateModal">
    <h2>Regeneracja planu</h2>
    <p>
        Spowoduje to wygenerowanie nowego planu ({{ $aiGenerationsRemaining }}/{{ $aiGenerationsLimit }} w tym miesiącu).
        <strong>Poprzedni plan zostanie nadpisany.</strong>
    </p>
    <p>Czy chcesz kontynuować?</p>

    <div class="modal-actions">
        <button wire:click="confirmRegenerate" class="btn-primary">
            Tak, regeneruj
        </button>
        <button wire:click="$set('showRegenerateModal', false)" class="btn-secondary">
            Anuluj
        </button>
    </div>
</x-modal>

<!-- Loading state podczas generowania -->
@if($isGenerating)
    <div wire:poll.3s="checkGenerationStatus" class="generation-progress">
        <div class="spinner"></div>
        <p>Generowanie planu... {{ $generationProgress }}%</p>
        <div class="progress-bar">
            <div class="progress-fill" style="width: {{ $generationProgress }}%"></div>
        </div>
    </div>
@endif
```

---

### 8.9 Usunięcie planu

**Flow:**
1. Użytkownik klika "Usuń plan"
2. Wyświetla się modal potwierdzenia (destructive action)
3. Użytkownik potwierdza → DELETE do API
4. Success: redirect do Dashboard z notyfikacją "Plan został usunięty"
5. Error: wyświetlenie błędu, pozostanie w widoku

**Implementacja:**
```blade
<!-- Przycisk usunięcia (destructive) -->
<button
    wire:click="deletePlan"
    class="btn-destructive"
>
    🗑️ Usuń plan
</button>

<!-- Modal potwierdzenia -->
<x-modal wire:model="showDeleteModal">
    <h2>Usunąć plan?</h2>
    <p>
        Czy na pewno chcesz usunąć plan <strong>{{ $plan->title }}</strong>?
        <br>
        <strong>Ta operacja jest nieodwracalna.</strong>
    </p>

    <div class="modal-actions">
        <button wire:click="confirmDelete" class="btn-destructive">
            Tak, usuń plan
        </button>
        <button wire:click="$set('showDeleteModal', false)" class="btn-secondary">
            Anuluj
        </button>
    </div>
</x-modal>
```

## 9. Warunki i walidacja

### 9.1 Warunki weryfikowane przez interfejs

#### Własność planu (Row-Level Security)

**Komponent:** PlansShow (mount)

**Warunek:**
Plan musi należeć do zalogowanego użytkownika.

**Weryfikacja:**
API zwraca 403 Forbidden jeśli `plan.user_id !== auth.user.id`

**Wpływ na UI:**
```php
protected function loadPlan(int $id): void
{
    $response = Http::get("/api/travel-plans/{$id}", [
        'include' => 'days,days.points,feedback'
    ]);

    if ($response->status() === 403) {
        abort(403, 'Ten plan nie należy do Ciebie.');
    }

    // ...
}
```

---

#### Dostępność akcji "Export PDF"

**Komponent:** PlanActions

**Warunek:**
- Plan musi mieć status `planned` lub `completed`
- Plan musi mieć wygenerowaną treść AI (`has_ai_plan === true`)

**Weryfikacja:**
```php
#[Computed]
public function canExportPdf(): bool
{
    return in_array($this->plan->status, ['planned', 'completed'])
        && $this->plan->has_ai_plan === true;
}
```

**Wpływ na UI:**
- Przycisk "Export do PDF" disabled jeśli warunek false
- Tooltip: "Wygeneruj plan, aby eksportować"

---

#### Dostępność akcji "Regeneruj plan"

**Komponent:** PlanActions

**Warunek:**
- Plan nie może być draftem (status !== 'draft')
- Użytkownik musi mieć dostępny limit AI (`aiGenerationsRemaining > 0`)

**Weryfikacja:**
```php
#[Computed]
public function canRegenerate(): bool
{
    return $this->plan->status !== 'draft'
        && $this->aiGenerationsRemaining > 0;
}
```

**Wpływ na UI:**
- Przycisk "Regeneruj plan" disabled jeśli limit wyczerpany
- Tooltip: "Osiągnięto limit generowań (10/10). Reset: 01.11.2025"
- Warning modal przed regeneracją: "X/10 w tym miesiącu"

---

#### Dostępność formularza feedbacku

**Komponent:** FeedbackForm

**Warunek:**
- Plan musi mieć wygenerowaną treść AI (status !== 'draft')
- Użytkownik nie może już mieć feedbacku dla tego planu

**Weryfikacja:**
```php
public bool $canSubmitFeedback;

public function mount()
{
    $this->canSubmitFeedback = $this->plan->status !== 'draft'
        && $this->existingFeedback === null;
}
```

**Wpływ na UI:**
- Jeśli feedback już istnieje: pokaż istniejący feedback (read-only)
- Jeśli draft: nie pokazuj formularza w ogóle
- Jeśli można: pokaż formularz collapsed

---

### 9.2 Walidacja formularza feedbacku

**Komponent:** FeedbackForm

**Reguły walidacji:**

```php
protected function rules()
{
    return [
        'satisfied' => 'required|boolean',
        'issues' => 'required_if:satisfied,false|array|max:4',
        'issues.*' => 'in:za_malo_szczegolow,nie_pasuje_do_preferencji,slaba_kolejnosc,inne',
        'otherComment' => 'nullable|string|max:1000',
    ];
}

protected $messages = [
    'satisfied.required' => 'Wybierz odpowiedź Tak lub Nie.',
    'issues.required_if' => 'Zaznacz przynajmniej jeden problem.',
    'issues.max' => 'Możesz wybrać maksymalnie 4 problemy.',
    'otherComment.max' => 'Komentarz może mieć maksymalnie 1000 znaków.',
];
```

**Real-time walidacja:**
```php
public function updated($propertyName)
{
    $this->validateOnly($propertyName);
}
```

**Wpływ na UI:**
- Błędy wyświetlane pod polami w czasie rzeczywistym
- Przycisk "Wyślij feedback" disabled podczas submitting
- Walidacja po stronie API (double-check)

---

### 9.3 Edge Cases

#### Draft plan (no AI content)

**Warunek:**
`plan.status === 'draft'`

**Wpływ na UI:**
- Pokazać tylko: Header + Assumptions Section
- Brak dni/punktów
- CTA "Generuj plan" (primary button)
- Przycisk "Usuń plan" dostępny
- Brak formularza feedbacku
- Brak przycisku "Export PDF"
- Brak przycisku "Regeneruj plan"

---

#### Generation pending

**Warunek:**
`isGenerating === true`

**Wpływ na UI:**
- Pokazać loading spinner z progress bar
- Disable wszystkie akcje (Delete, Regenerate, Export)
- Polling `generation-status` co 3-5 sekund
- Po completion: reload planu, success notification

---

#### Regeneration z limitem 10/10

**Warunek:**
`aiGenerationsRemaining === 0`

**Wpływ na UI:**
- Przycisk "Regeneruj plan" disabled
- Tooltip: "Osiągnięto limit generowań (10/10). Reset: 01.11.2025"
- Modal nie pokazuje się

---

#### Feedback już przesłany

**Warunek:**
`existingFeedback !== null`

**Wpływ na UI:**
- Zamiast formularza pokaż istniejący feedback (read-only):
  - "Twoja ocena: 👍 Pozytywna" lub "👎 Negatywna"
  - Problemy (jeśli były)
  - Komentarz (jeśli był)
- Brak możliwości edycji (MVP limitation)

---

#### Lazy loading dla długich planów (20-30 dni)

**Warunek:**
`plan.number_of_days > 3`

**Wpływ na UI:**
- Początkowo załadowane tylko 3 dni
- Przycisk "Pokaż więcej dni (X pozostałych)"
- Po kliknięciu: załadowanie kolejnych 5 dni
- Repeat aż wszystkie dni załadowane

**Implementacja:**
```php
public int $loadedDaysCount = 3;

public function loadMoreDays()
{
    $this->loadedDaysCount = min(
        $this->loadedDaysCount + 5,
        $this->plan->days->count()
    );
}
```

## 10. Obsługa błędów

### 10.1 Błędy API - 403 Forbidden

**Scenariusz:**
Użytkownik próbuje dostać się do planu, który nie należy do niego (np. zgadując ID w URL).

**Obsługa:**
```php
protected function loadPlan(int $id): void
{
    $response = Http::get("/api/travel-plans/{$id}");

    if ($response->status() === 403) {
        abort(403, 'Ten plan nie należy do Ciebie.');
    }
}
```

**UX:**
- Laravel wyświetla stronę 403 error
- Alternatywnie: redirect do Dashboard z flash message "Brak dostępu do tego planu"

---

### 10.2 Błędy API - 404 Not Found

**Scenariusz:**
Plan o podanym ID nie istnieje lub został soft-deleted.

**Obsługa:**
```php
if ($response->status() === 404) {
    abort(404, 'Plan nie został znaleziony.');
}
```

**UX:**
- Laravel wyświetla stronę 404 error
- Alternatywnie: redirect do Dashboard z flash message "Plan nie istnieje"

---

### 10.3 Błędy API - 500 Internal Server Error

**Scenariusz:**
Nieoczekiwany błąd serwera podczas pobierania planu.

**Obsługa:**
```php
if ($response->status() >= 500) {
    session()->flash('error', 'Wystąpił błąd serwera. Spróbuj ponownie później.');
    return redirect()->route('dashboard');
}
```

**UX:**
- Redirect do Dashboard
- Flash notification z błędem
- Logowanie błędu (Laravel log)

---

### 10.4 Timeout podczas generowania AI

**Scenariusz:**
API timeout po 120 sekundach, generowanie nie zostało ukończone.

**Obsługa:**
```php
#[On('poll-generation-status')]
public function checkGenerationStatus(): void
{
    $response = Http::get("/api/travel-plans/{$this->plan->id}/generation-status");

    if ($response->successful()) {
        $status = $response->json('data.status');

        if ($status === 'failed') {
            $this->isGenerating = false;
            $errorMessage = $response->json('data.error_message');
            session()->flash('error', "Generowanie nie powiodło się: {$errorMessage}");
        }
    }
}
```

**UX:**
- Stop polling
- Wyświetlenie error notification: "Generowanie trwa zbyt długo. Spróbuj ponownie."
- Przycisk "Spróbuj ponownie" (retry regeneration)
- Nieudane generowanie NIE zużywa limitu

---

### 10.5 Walidacja formularza feedbacku

**Scenariusz:**
Użytkownik nie zaznaczył "Tak/Nie" lub nie wybrał problemów przy "Nie".

**Obsługa:**
```php
public function submitFeedback(): void
{
    $this->validate(); // Throws ValidationException jeśli błąd

    // ...
}
```

**UX:**
- Błędy wyświetlane pod polami w real-time (`updated()` hook)
- Komunikaty walidacji po polsku (customowe `$messages`)
- Przycisk "Wyślij feedback" disabled podczas submitting

---

### 10.6 Feedback już przesłany (400 Bad Request)

**Scenariusz:**
Użytkownik próbuje przesłać drugi feedback dla tego samego planu.

**Obsługa:**
```php
public function submitFeedback(): void
{
    // ...

    if ($response->status() === 400) {
        session()->flash('error', 'Feedback dla tego planu został już przesłany.');
        $this->showForm = false;
        return;
    }
}
```

**UX:**
- Flash notification z błędem
- Formularz collapse
- Jeśli możliwe: reload istniejącego feedbacku i wyświetlenie read-only

---

### 10.7 Wyczerpany limit AI (429 Too Many Requests)

**Scenariusz:**
Użytkownik próbuje regenerować plan, ale osiągnął limit 10/10.

**Obsługa:**
```php
public function confirmRegenerate(): void
{
    $response = Http::post("/api/travel-plans/{$this->plan->id}/generate");

    if ($response->status() === 429) {
        $resetDate = Carbon::parse($response->json('data.reset_at'));
        session()->flash('error', "Osiągnięto limit generowań ({$this->aiGenerationsLimit}/miesiąc). Reset: {$resetDate->format('d.m.Y')}");
        $this->showRegenerateModal = false;
        return;
    }
}
```

**UX:**
- Modal zamyka się
- Flash notification z datą resetu limitu
- Przycisk "Regeneruj plan" disabled (aktualizacja `aiGenerationsRemaining`)

---

### 10.8 PDF generation failed (500 Internal Server Error)

**Scenariusz:**
Błąd serwera podczas generowania PDF (np. Chromium timeout).

**Obsługa:**
```php
public function exportPdf(): void
{
    try {
        return redirect()->to("/api/travel-plans/{$this->plan->id}/pdf");
    } catch (\Exception $e) {
        session()->flash('error', 'Nie udało się wygenerować PDF. Spróbuj ponownie.');
        $this->isExportingPdf = false;
    }
}
```

**UX:**
- Flash notification z błędem
- Użytkownik może spróbować ponownie
- Logowanie błędu po stronie API

---

### 10.9 Network errors (connection timeout)

**Scenariusz:**
Użytkownik stracił połączenie z internetem podczas operacji.

**Obsługa:**
```php
protected function loadPlan(int $id): void
{
    try {
        $response = Http::timeout(10)->get("/api/travel-plans/{$id}");
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        session()->flash('error', 'Brak połączenia z internetem. Sprawdź połączenie i spróbuj ponownie.');
        return redirect()->route('dashboard');
    }
}
```

**UX:**
- Flash notification: "Brak połączenia z internetem"
- Redirect do Dashboard (fallback)
- Możliwość retry (refresh page)

## 11. Kroki implementacji

### Krok 1: Utworzenie struktury katalogów

```bash
mkdir -p app/Livewire/Plans
mkdir -p app/Livewire/Components
mkdir -p resources/views/livewire/plans
mkdir -p resources/views/livewire/components
```

---

### Krok 2: Utworzenie DTOs i ViewModels

**Pliki do utworzenia:**
1. `app/DataTransferObjects/PlanDayViewModel.php`
2. `app/DataTransferObjects/PlanPointViewModel.php`
3. `app/DataTransferObjects/FeedbackDTO.php`
4. `app/DataTransferObjects/UserPreferencesDTO.php`

**Implementacja zgodnie z sekcją 5. Typy**

---

### Krok 3: Utworzenie modeli Eloquent (jeśli nie istnieją)

**Pliki do utworzenia/zweryfikowania:**
1. `app/Models/TravelPlan.php`
2. `app/Models/PlanDay.php` (jeśli używamy Eloquent zamiast czystych DTO)
3. `app/Models/PlanPoint.php`
4. `app/Models/Feedback.php`
5. `app/Models/UserPreference.php`

**Relacje:**
```php
// TravelPlan.php
public function days()
{
    return $this->hasMany(PlanDay::class)->orderBy('day_number');
}

public function feedback()
{
    return $this->hasOne(Feedback::class);
}

// PlanDay.php
public function plan()
{
    return $this->belongsTo(TravelPlan::class);
}

public function points()
{
    return $this->hasMany(PlanPoint::class)->orderBy('order_number');
}
```

---

### Krok 4: Utworzenie komponentu głównego PlansShow

**Komenda:**
```bash
php artisan make:livewire Plans/Show
```

**Implementacja:**
```php
<?php

namespace App\Livewire\Plans;

use App\Models\TravelPlan;
use App\Models\Feedback;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Szczegóły planu')]
class Show extends Component
{
    // Properties
    public TravelPlan $plan;
    public ?Feedback $feedback = null;

    // UI State
    public bool $showDeleteModal = false;
    public bool $showRegenerateModal = false;
    public bool $isExportingPdf = false;
    public bool $isGenerating = false;
    public int $generationProgress = 0;
    public ?int $generationId = null;

    // User context
    public int $aiGenerationsRemaining;
    public int $aiGenerationsLimit = 10;

    // Lazy loading
    public int $loadedDaysCount = 3;

    public function mount($id)
    {
        $this->loadPlan($id);
        $this->loadUserContext();
    }

    public function hydrate()
    {
        $this->aiGenerationsRemaining = $this->getUserAiGenerationsRemaining();
    }

    protected function loadPlan(int $id): void
    {
        $response = Http::get("/api/travel-plans/{$id}", [
            'include' => 'days,days.points,feedback'
        ]);

        if ($response->status() === 403) {
            abort(403, 'Ten plan nie należy do Ciebie.');
        }

        if ($response->status() === 404) {
            abort(404, 'Plan nie został znaleziony.');
        }

        if ($response->status() >= 500) {
            session()->flash('error', 'Błąd podczas ładowania planu. Spróbuj ponownie.');
            return redirect()->route('dashboard');
        }

        $data = $response->json('data');

        // Hydrate Eloquent model
        $this->plan = TravelPlan::hydrate([$data])->first();
        $this->feedback = $data['feedback'] ? Feedback::make($data['feedback']) : null;
    }

    protected function loadUserContext(): void
    {
        $this->aiGenerationsRemaining = $this->getUserAiGenerationsRemaining();
    }

    protected function getUserAiGenerationsRemaining(): int
    {
        $user = auth()->user();
        return max(0, $this->aiGenerationsLimit - $user->ai_generations_count_current_month);
    }

    #[Computed]
    public function canRegenerate(): bool
    {
        return $this->plan->status !== 'draft'
            && $this->aiGenerationsRemaining > 0;
    }

    #[Computed]
    public function canExportPdf(): bool
    {
        return in_array($this->plan->status, ['planned', 'completed'])
            && $this->plan->has_ai_plan === true;
    }

    public function deletePlan()
    {
        $this->showDeleteModal = true;
    }

    public function confirmDelete()
    {
        $response = Http::delete("/api/travel-plans/{$this->plan->id}");

        if ($response->successful()) {
            session()->flash('success', 'Plan został usunięty.');
            return redirect()->route('dashboard');
        } else {
            session()->flash('error', 'Nie udało się usunąć planu. Spróbuj ponownie.');
            $this->showDeleteModal = false;
        }
    }

    public function regeneratePlan()
    {
        if (!$this->canRegenerate) {
            session()->flash('error', 'Nie można regenerować planu.');
            return;
        }

        $this->showRegenerateModal = true;
    }

    public function confirmRegenerate()
    {
        $response = Http::post("/api/travel-plans/{$this->plan->id}/generate");

        if ($response->status() === 429) {
            $resetDate = \Carbon\Carbon::parse($response->json('data.reset_at'));
            session()->flash('error', "Osiągnięto limit generowań ({$this->aiGenerationsLimit}/miesiąc). Reset: {$resetDate->format('d.m.Y')}");
            $this->showRegenerateModal = false;
            return;
        }

        if ($response->successful()) {
            $this->isGenerating = true;
            $this->generationId = $response->json('data.generation_id');
            $this->generationProgress = 0;
        } else {
            session()->flash('error', 'Nie udało się rozpocząć regeneracji. Spróbuj ponownie.');
        }

        $this->showRegenerateModal = false;
    }

    #[On('poll-generation-status')]
    public function checkGenerationStatus(): void
    {
        if (!$this->isGenerating || !$this->generationId) {
            return;
        }

        $response = Http::get("/api/travel-plans/{$this->plan->id}/generation-status");

        if ($response->successful()) {
            $status = $response->json('data.status');

            if ($status === 'completed') {
                $this->isGenerating = false;
                $this->generationProgress = 100;
                $this->loadPlan($this->plan->id);
                session()->flash('success', 'Plan został pomyślnie wygenerowany!');
            } elseif ($status === 'failed') {
                $this->isGenerating = false;
                $errorMessage = $response->json('data.error_message');
                session()->flash('error', "Generowanie nie powiodło się: {$errorMessage}");
            } else {
                $this->generationProgress = $response->json('data.progress_percentage', 0);
            }
        }
    }

    public function exportPdf()
    {
        if (!$this->canExportPdf) {
            session()->flash('error', 'Nie można eksportować szkicu planu.');
            return;
        }

        $this->isExportingPdf = true;

        // Redirect to PDF endpoint (browser will trigger download)
        return redirect()->to("/api/travel-plans/{$this->plan->id}/pdf");
    }

    public function loadMoreDays()
    {
        $this->loadedDaysCount = min(
            $this->loadedDaysCount + 5,
            $this->plan->days->count()
        );
    }

    public function render()
    {
        return view('livewire.plans.show');
    }
}
```

---

### Krok 5: Utworzenie nested komponentów

**Komendy:**
```bash
php artisan make:livewire Components/PlanHeader
php artisan make:livewire Components/AssumptionsSection
php artisan make:livewire Components/PreferenceBadge
php artisan make:livewire Components/PlanDay
php artisan make:livewire Components/PlanPoint
php artisan make:livewire Components/FeedbackForm
php artisan make:livewire Components/PlanActions
```

**Implementacja każdego komponentu zgodnie z sekcją 4. Szczegóły komponentów**

---

### Krok 6: Utworzenie Blade templates

**Główny template (`resources/views/livewire/plans/show.blade.php`):**

```blade
<div class="plan-details-container">
    {{-- Header planu --}}
    <livewire:components.plan-header :plan="$plan" />

    {{-- Sekcja założeń --}}
    <livewire:components.assumptions-section
        :userNotes="$plan->user_notes"
        :preferences="auth()->user()->preferences"
    />

    {{-- Dni planu (tylko dla generated plans) --}}
    @if($plan->status !== 'draft')
        <div class="plan-days-section">
            <h2>Plan dnia po dniu</h2>

            @foreach($plan->days->take($loadedDaysCount) as $index => $day)
                <livewire:components.plan-day
                    :day="$day->toArray()"
                    :expanded="$index === 0 && !request()->header('X-Mobile')"
                    :key="'day-'.$day->id"
                />
            @endforeach

            @if($plan->days->count() > $loadedDaysCount)
                <button wire:click="loadMoreDays" class="btn-secondary load-more">
                    Pokaż więcej dni ({{ $plan->days->count() - $loadedDaysCount }} pozostałych)
                </button>
            @endif
        </div>
    @endif

    {{-- Footer z akcjami --}}
    <div class="plan-footer">
        @if($plan->status !== 'draft')
            <livewire:components.feedback-form
                :travelPlanId="$plan->id"
                :existingFeedback="$feedback"
            />
        @endif

        <livewire:components.plan-actions
            :status="$plan->status"
            :aiGenerationsRemaining="$aiGenerationsRemaining"
            :hasAiPlan="$plan->has_ai_plan"
        />
    </div>

    {{-- Modals --}}
    <x-modal wire:model="showDeleteModal">
        <h2>Usunąć plan?</h2>
        <p>
            Czy na pewno chcesz usunąć plan <strong>{{ $plan->title }}</strong>?
            <br>
            <strong>Ta operacja jest nieodwracalna.</strong>
        </p>

        <div class="modal-actions">
            <button wire:click="confirmDelete" class="btn-destructive">
                Tak, usuń plan
            </button>
            <button wire:click="$set('showDeleteModal', false)" class="btn-secondary">
                Anuluj
            </button>
        </div>
    </x-modal>

    <x-modal wire:model="showRegenerateModal">
        <h2>Regeneracja planu</h2>
        <p>
            Spowoduje to wygenerowanie nowego planu ({{ $aiGenerationsRemaining }}/{{ $aiGenerationsLimit }} w tym miesiącu).
            <strong>Poprzedni plan zostanie nadpisany.</strong>
        </p>
        <p>Czy chcesz kontynuować?</p>

        <div class="modal-actions">
            <button wire:click="confirmRegenerate" class="btn-primary">
                Tak, regeneruj
            </button>
            <button wire:click="$set('showRegenerateModal', false)" class="btn-secondary">
                Anuluj
            </button>
        </div>
    </x-modal>

    {{-- Generation progress overlay --}}
    @if($isGenerating)
        <div wire:poll.3s="checkGenerationStatus" class="generation-overlay">
            <div class="generation-modal">
                <div class="spinner"></div>
                <h3>Generowanie planu...</h3>
                <p>{{ $generationProgress }}%</p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ $generationProgress }}%"></div>
                </div>
                <p class="hint">To może potrwać 30-60 sekund. Nie zamykaj tej strony.</p>
            </div>
        </div>
    @endif

    {{-- Flash messages --}}
    @if(session()->has('success'))
        <x-notification type="success">
            {{ session('success') }}
        </x-notification>
    @endif

    @if(session()->has('error'))
        <x-notification type="error">
            {{ session('error') }}
        </x-notification>
    @endif
</div>
```

**Nested component templates zgodnie z sekcją 4**

---

### Krok 7: Styling z Tailwind CSS

**Utworzenie komponentów Tailwind:**

```css
/* resources/css/components/plan-details.css */

/* Plan Header */
.plan-header {
    @apply bg-white rounded-lg shadow-md p-6 mb-6;
}

.plan-header h1 {
    @apply text-3xl font-bold text-gray-900 mb-4;
}

.plan-header .metadata {
    @apply grid grid-cols-2 md:grid-cols-4 gap-4;
}

.plan-header .metadata-item {
    @apply flex items-center gap-2 text-gray-700;
}

.status-badge {
    @apply inline-flex items-center px-3 py-1 rounded-full text-sm font-medium;
}

.status-badge.draft {
    @apply bg-gray-100 text-gray-800;
}

.status-badge.planned {
    @apply bg-blue-100 text-blue-800;
}

.status-badge.completed {
    @apply bg-green-100 text-green-800;
}

/* Assumptions Section */
.assumptions-section {
    @apply bg-gray-50 rounded-lg p-6 mb-6;
}

.assumptions-toggle {
    @apply flex items-center justify-between w-full text-left font-medium text-gray-900 hover:text-blue-600 transition;
}

.assumptions-content {
    @apply mt-4 space-y-4;
}

.preference-badges {
    @apply flex flex-wrap gap-2;
}

.preference-badge {
    @apply inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-sm;
}

/* Plan Days */
.plan-day-card {
    @apply bg-white rounded-lg shadow-md mb-4 overflow-hidden;
}

.day-header {
    @apply flex items-center justify-between w-full p-4 text-left hover:bg-gray-50 transition cursor-pointer;
}

.day-header h3 {
    @apply text-xl font-semibold text-gray-900;
}

.day-content {
    @apply p-4 bg-gray-50 border-t border-gray-200;
}

.day-part-section {
    @apply mb-6 last:mb-0;
}

.day-part-section h4 {
    @apply text-lg font-medium text-gray-700 mb-3;
}

/* Plan Points */
.plan-point-card {
    @apply bg-white rounded-lg shadow-sm p-4 mb-3 cursor-pointer hover:shadow-md transition;
}

.plan-point-card.expanded {
    @apply shadow-md;
}

.point-collapsed {
    @apply flex items-center justify-between;
}

.point-collapsed h4 {
    @apply text-base font-medium text-gray-900;
}

.day-part-icon {
    @apply text-2xl mr-3;
}

.duration {
    @apply text-sm text-gray-500;
}

.point-expanded {
    @apply mt-4 space-y-3;
}

.point-expanded .description {
    @apply text-gray-700;
}

.point-expanded .justification {
    @apply text-sm italic text-gray-600;
}

.point-expanded .metadata {
    @apply flex items-center gap-4 text-sm text-gray-600;
}

.maps-link {
    @apply text-blue-600 hover:text-blue-800 underline;
}

/* Feedback Form */
.feedback-section {
    @apply bg-white rounded-lg shadow-md p-6 mb-6;
}

.feedback-toggle {
    @apply w-full text-left font-medium text-blue-600 hover:text-blue-800 transition;
}

.feedback-form {
    @apply space-y-4;
}

.satisfaction-buttons {
    @apply flex gap-4;
}

.satisfaction-buttons button {
    @apply flex-1 py-3 px-4 rounded-lg border-2 border-gray-300 hover:border-blue-500 transition;
}

.satisfaction-buttons button.active {
    @apply border-blue-500 bg-blue-50;
}

.issues-checkboxes {
    @apply space-y-2;
}

.issues-checkboxes label {
    @apply flex items-center gap-2 text-gray-700;
}

.issues-checkboxes textarea {
    @apply w-full mt-2 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500;
}

/* Plan Actions */
.plan-actions {
    @apply flex flex-wrap gap-4;
}

.btn-primary {
    @apply px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition;
}

.btn-secondary {
    @apply px-6 py-3 bg-gray-200 text-gray-800 font-medium rounded-lg hover:bg-gray-300 disabled:bg-gray-100 disabled:cursor-not-allowed transition;
}

.btn-destructive {
    @apply px-6 py-3 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition;
}

/* Modals */
.modal-actions {
    @apply flex gap-4 mt-6;
}

/* Generation Overlay */
.generation-overlay {
    @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50;
}

.generation-modal {
    @apply bg-white rounded-lg p-8 max-w-md w-full text-center;
}

.spinner {
    @apply w-16 h-16 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-4;
}

.progress-bar {
    @apply w-full h-2 bg-gray-200 rounded-full overflow-hidden;
}

.progress-fill {
    @apply h-full bg-blue-600 transition-all duration-300;
}

/* Responsive */
@media (max-width: 768px) {
    .plan-header .metadata {
        @apply grid-cols-1;
    }

    .plan-actions {
        @apply flex-col;
    }

    .plan-actions button {
        @apply w-full;
    }
}
```

---

### Krok 8: Dodanie routingu

**`routes/web.php`:**
```php
use App\Livewire\Plans\Show as PlansShow;

Route::middleware(['auth', 'verified', 'onboarding.completed'])->group(function () {
    Route::get('/plans/{id}', PlansShow::class)->name('plans.show');
});
```

---

### Krok 9: Utworzenie middleware onboarding.completed

**Komenda:**
```bash
php artisan make:middleware EnsureOnboardingCompleted
```

**Implementacja:**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureOnboardingCompleted
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && !auth()->user()->onboarding_completed_at) {
            return redirect()->route('onboarding.index')
                ->with('error', 'Ukończ onboarding, aby uzyskać dostęp do tej strony.');
        }

        return $next($request);
    }
}
```

**Rejestracja w `app/Http/Kernel.php`:**
```php
protected $middlewareAliases = [
    // ...
    'onboarding.completed' => \App\Http\Middleware\EnsureOnboardingCompleted::class,
];
```

---

### Krok 10: Testowanie podstawowej funkcjonalności

**Testy manualne:**
1. Zaloguj się jako użytkownik z ukończonym onboardingiem
2. Utwórz draft plan w Dashboard
3. Przejdź do `/plans/{id}` → powinna wyświetlić się strona szkicu
4. Kliknij "Generuj plan" → should start generation
5. Poczekaj na completion → plan details powinien się załadować
6. Testuj expand/collapse dni
7. Testuj expand/collapse punktów
8. Wypełnij i prześlij feedback
9. Eksportuj do PDF
10. Regeneruj plan (sprawdź warning modal)
11. Usuń plan (sprawdź confirmation modal)

**Unit tests (przykład):**
```php
// tests/Feature/Livewire/Plans/ShowTest.php

use App\Livewire\Plans\Show;
use App\Models\User;
use App\Models\TravelPlan;
use Livewire\Livewire;

it('displays plan details for owner', function () {
    $user = User::factory()->create(['onboarding_completed_at' => now()]);
    $plan = TravelPlan::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(Show::class, ['id' => $plan->id])
        ->assertSee($plan->title)
        ->assertSee($plan->destination);
});

it('throws 403 for non-owner', function () {
    $owner = User::factory()->create();
    $plan = TravelPlan::factory()->create(['user_id' => $owner->id]);

    $otherUser = User::factory()->create(['onboarding_completed_at' => now()]);

    $this->actingAs($otherUser)
        ->get(route('plans.show', $plan->id))
        ->assertForbidden();
});

it('can delete plan', function () {
    $user = User::factory()->create(['onboarding_completed_at' => now()]);
    $plan = TravelPlan::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(Show::class, ['id' => $plan->id])
        ->call('deletePlan')
        ->assertSet('showDeleteModal', true)
        ->call('confirmDelete')
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseMissing('travel_plans', ['id' => $plan->id]);
});
```

---

### Krok 11: Dodanie accessibility features

**ARIA attributes:**
- `aria-expanded` dla accordion days
- `aria-controls` dla toggles
- `aria-label` dla icon buttons
- `role="region"` dla głównych sekcji
- `role="status"` dla loading states

**Keyboard navigation:**
- Tab index dla wszystkich interaktywnych elementów
- Enter/Space dla toggle actions
- Esc dla zamykania modali
- Focus trap w modalach

**Screen reader support:**
- Meaningful alt texts
- Hidden labels dla icon-only buttons
- Status announcements dla async operations (ARIA live regions)

**Implementacja przykład:**
```blade
<button
    @click="expanded = !expanded"
    @keydown.enter="expanded = !expanded"
    @keydown.space.prevent="expanded = !expanded"
    :aria-expanded="expanded"
    aria-controls="day-{{ $day['id'] }}-content"
    class="day-header"
>
    <h3 id="day-{{ $day['id'] }}-heading">
        Dzień {{ $day['day_number'] }} - {{ $day['date'] }}
    </h3>
    <span aria-hidden="true">
        <svg x-show="!expanded">▼</svg>
        <svg x-show="expanded">▲</svg>
    </span>
    <span class="sr-only">
        <span x-show="!expanded">Rozwiń dzień {{ $day['day_number'] }}</span>
        <span x-show="expanded">Zwiń dzień {{ $day['day_number'] }}</span>
    </span>
</button>

<div
    x-show="expanded"
    x-transition
    id="day-{{ $day['id'] }}-content"
    aria-labelledby="day-{{ $day['id'] }}-heading"
    role="region"
>
    <!-- Content -->
</div>
```

---

### Krok 12: Responsywność (mobile-first)

**Breakpoints:**
- Mobile: < 640px
- Tablet: 640px - 1024px
- Desktop: > 1024px

**Mobile optimizations:**
- Wszystkie dni collapsed by default
- Stack layout dla actions (full-width buttons)
- Simplified header metadata (2 columns → 1 column)
- Touch-friendly tap targets (min 44x44px)
- Reduced padding/margins

**Implementacja:**
```blade
<div class="plan-header">
    <h1 class="text-2xl md:text-3xl">{{ $plan->title }}</h1>

    <div class="metadata grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Metadata items -->
    </div>
</div>

<div class="plan-actions flex flex-col md:flex-row gap-4">
    <button class="btn-primary w-full md:w-auto">Export PDF</button>
    <button class="btn-secondary w-full md:w-auto">Regeneruj</button>
</div>
```

---

### Krok 13: Performance optimizations

**Lazy loading:**
- Implementacja w PlansShow (sekcja 6)
- Load first 3 days, "Load more" button

**Image optimization (jeśli używane):**
- Lazy loading images z `loading="lazy"`
- Responsive images z `srcset`

**Livewire optimizations:**
- `wire:model.defer` dla non-critical inputs
- `wire:model.blur` dla textarea
- `wire:dirty` class dla unsaved changes indicator
- Debounce dla search/filter inputs (if added)

**Caching:**
- Redis cache dla user preferences (API side)
- Browser cache dla static assets

---

### Krok 14: Error handling i edge cases

**Implementacja zgodnie z sekcją 10. Obsługa błędów**

**Dodatkowe edge cases:**
- Plan w trakcie generowania → disable all actions, show progress
- Plan soft-deleted → 404 error
- User nie ma uprawnień → 403 error
- Network timeout → retry mechanism

---

### Krok 15: Dokumentacja

**Utworzenie dokumentacji:**
1. **README.md** dla komponentu
2. **Komentarze PHPDoc** w kodzie
3. **Storybook** dla Blade components (opcjonalnie)

**Przykład README.md:**
```markdown
# Plan Details View

## Overview
Widok szczegółów planu podróży (PlansShow) jest głównym widokiem do przeglądania wygenerowanych planów AI oraz szkiców.

## Components
- PlansShow (full-page)
- PlanHeader (nested)
- AssumptionsSection (nested)
- PlanDay (nested, accordion)
- PlanPoint (nested, expandable)
- FeedbackForm (nested)
- PlanActions (nested)

## Usage
```blade
<livewire:plans.show :id="$planId" />
```

## Props
- `id` (int, required) - Travel plan ID

## Events
- `feedback-submitted` - Dispatched when feedback is successfully submitted
- `plan-deleted` - Dispatched when plan is deleted
- `plan-regenerated` - Dispatched when plan regeneration starts

## API Integration
See section 7 for detailed API integration documentation.

## Testing
```bash
php artisan test --filter=PlansShowTest
```
```

---

### Krok 16: Code review i refactoring

**Checklist:**
- [ ] Kod zgodny z PSR-12 (Laravel Pint)
- [ ] Brak duplikacji kodu (DRY principle)
- [ ] Wszystkie metody mają PHPDoc
- [ ] Properties mają type hints
- [ ] Validation rules są wydzielone
- [ ] Error handling jest kompletny
- [ ] Accessibility jest zaimplementowana
- [ ] Responsywność działa na wszystkich breakpoints
- [ ] Performance jest optymalizowana (lazy loading)

**Komendy:**
```bash
# Laravel Pint (code style)
./vendor/bin/pint app/Livewire/Plans

# PHPStan (static analysis)
./vendor/bin/phpstan analyse app/Livewire/Plans

# Tests
php artisan test --filter=Plans
```

---

### Krok 17: Deployment

**Pre-deployment checklist:**
- [ ] Wszystkie testy przechodzą
- [ ] Code review zaakceptowany
- [ ] Dokumentacja zaktualizowana
- [ ] Migracje bazy danych gotowe (jeśli potrzebne)
- [ ] Environment variables skonfigurowane
- [ ] Error tracking włączony (Sentry, if used)

**Deployment steps:**
1. Merge do branch `main`
2. GitHub Actions trigger CI/CD pipeline
3. Run tests
4. Build Docker image
5. Deploy to DigitalOcean
6. Run migrations (if any)
7. Clear cache (`php artisan cache:clear`)
8. Verify deployment (smoke tests)

---

### Krok 18: Monitoring i analytics

**Metryki do śledzenia:**
- Page load time (< 2 seconds goal)
- Time to Interactive (< 3 seconds goal)
- API response time (< 500ms goal)
- Generation success rate (> 95% goal)
- Error rate (< 1% goal)
- User engagement (expand/collapse interactions)
- Feedback submission rate
- PDF export rate

**Narzędzia:**
- Google Analytics (page views, user flow)
- Laravel Telescope (dev) - request monitoring
- Sentry (production, opcjonalnie) - error tracking
- Custom analytics events (Livewire dispatched events)

**Implementacja analytics events:**
```php
// Po successful action
$this->dispatch('analytics-event', [
    'category' => 'Plan Details',
    'action' => 'Feedback Submitted',
    'label' => 'Satisfied: ' . ($this->satisfied ? 'Yes' : 'No'),
]);

// W Blade template
<script>
window.addEventListener('analytics-event', event => {
    gtag('event', event.detail.action, {
        'event_category': event.detail.category,
        'event_label': event.detail.label,
    });
});
</script>
```

---

## Podsumowanie

Ten plan implementacji zapewnia kompleksowy przewodnik do stworzenia widoku Szczegółów Planu w aplikacji VibeTravels. Kluczowe aspekty:

1. **Architektura komponentowa** - Livewire 3 full-page + nested components
2. **Hybrid state management** - Livewire server-side + Alpine.js client-side
3. **Progressive disclosure** - Accordion days, expandable points, lazy loading
4. **Robust error handling** - API errors, validation, edge cases
5. **Accessibility** - WCAG 2.1 Level AA compliance
6. **Mobile-first responsive** - Tailwind CSS breakpoints
7. **Performance optimization** - Lazy loading, caching, debouncing
8. **Comprehensive testing** - Unit, feature, manual tests

**Szacowany czas implementacji:** 5-7 dni roboczych dla doświadczonego developera Laravel/Livewire.

**Zależności:**
- Działające API endpoints (zgodnie z api-plan.md)
- Skonfigurowane modele Eloquent
- Istniejące DTOs (TravelPlanDTO już istnieje)
- Wire UI library zainstalowana
- Tailwind CSS skonfigurowany

**Następne kroki po implementacji:**
1. User Acceptance Testing (UAT)
2. Performance testing (load testing dla 100+ users)
3. Accessibility audit (WCAG compliance)
4. Security audit (XSS, CSRF, authorization)
5. Production deployment
