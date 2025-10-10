# VibeTravels - Coding Standards & Best Practices

> **Purpose**: This document defines coding standards, quality requirements, and best practices for the VibeTravels project. All code contributions must adhere to these standards.

---

## Table of Contents

1. [Backend Standards (PHP/Laravel)](#backend-standards-phplarave l)
2. [Frontend Standards (Livewire/Alpine.js/Tailwind)](#frontend-standards-livewirealpinejs tailwind)
3. [Database Standards](#database-standards)
4. [Testing Standards](#testing-standards)
5. [Git & Version Control](#git--version-control)
6. [Security Standards](#security-standards)
7. [Performance Guidelines](#performance-guidelines)

---

## Backend Standards (PHP/Laravel)

### 1. PHP Standards Compliance

#### PSR Standards
All PHP code MUST follow:
- **PSR-1**: Basic Coding Standard
- **PSR-12**: Extended Coding Style Guide
- **PSR-4**: Autoloading Standard

**Enforcement**: Laravel Pint (automatic formatting)

```bash
# Check code style
make cs-check

# Fix code style automatically
make cs-fix
```

#### PHPStan Static Analysis
- **Level**: 6 (configured in `phpstan.neon`)
- **Coverage**: app, config, database, routes, tests
- **Zero errors policy**: All code must pass PHPStan level 6

```bash
# Run static analysis
make phpstan
```

### 2. Type Safety

#### Type Declarations
Always use strict types and explicit type declarations:

```php
<?php

declare(strict_types=1);

namespace App\Services;

class ExampleService
{
    public function __construct(
        private readonly Repository $repository,
        private readonly Logger $logger
    ) {}

    public function process(string $input): array
    {
        // Implementation
    }
}
```

#### PHPDoc Comments
Use PHPDoc for complex types and arrays:

```php
/**
 * @param  array<string, mixed>  $data
 * @return list<User>
 */
public function findUsers(array $data): array
{
    // Implementation
}

/**
 * @var array<int, array<string, mixed>>
 */
private array $cache = [];
```

### 3. Laravel Conventions

#### Class Organization
```php
class ExampleController extends Controller
{
    // 1. Constants
    private const MAX_ITEMS = 100;

    // 2. Properties
    private array $items = [];

    // 3. Constructor
    public function __construct(
        private readonly Service $service
    ) {}

    // 4. Public methods (routes first)
    public function index() {}
    public function store() {}

    // 5. Protected methods
    protected function validateData() {}

    // 6. Private methods
    private function processItem() {}
}
```

#### Service Pattern
Extract business logic from controllers into services:

```php
// ❌ Bad: Business logic in controller
class PlanController extends Controller
{
    public function generate(Request $request)
    {
        // 50 lines of business logic...
    }
}

// ✅ Good: Clean controller, logic in service
class PlanController extends Controller
{
    public function __construct(
        private readonly PlanGenerationService $service
    ) {}

    public function generate(GeneratePlanRequest $request)
    {
        $plan = $this->service->generate($request->validated());
        return view('plans.show', compact('plan'));
    }
}
```

#### Eloquent Best Practices

**Use Query Scopes**:
```php
class User extends Model
{
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    public function scopeWithPlans(Builder $query): void
    {
        $query->with('plans');
    }
}

// Usage
$users = User::active()->withPlans()->get();
```

**Use Accessors/Mutators**:
```php
class Plan extends Model
{
    protected function budget(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value / 100,
            set: fn ($value) => $value * 100,
        );
    }
}
```

**Always specify fillable or guarded**:
```php
class Plan extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'destination',
        'days',
        'budget',
    ];

    protected $guarded = ['id'];
}
```

### 4. Error Handling

#### Custom Exceptions
Create specific exceptions for domain errors:

```php
namespace App\Exceptions;

class OpenAIException extends Exception
{
    /** @var array<string, mixed> */
    protected array $context = [];

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function report(): void
    {
        Log::error($this->getMessage(), $this->context);
    }
}
```

#### Graceful Degradation
Always provide fallbacks for external services:

```php
try {
    return new RealOpenAIService($apiKey, $model);
} catch (Exception $e) {
    Log::critical('Failed to initialize OpenAI', ['error' => $e->getMessage()]);
    return new MockOpenAIService($model); // Fallback
}
```

### 5. Dependency Injection

Always use constructor injection:

```php
// ✅ Good
class PlanService
{
    public function __construct(
        private readonly OpenAIService $ai,
        private readonly PlanRepository $repository
    ) {}
}

// ❌ Bad
class PlanService
{
    public function generate()
    {
        $ai = app(OpenAIService::class); // Don't do this
    }
}
```

### 6. Validation

#### Form Requests
Always use Form Request classes:

```php
class GeneratePlanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'destination' => 'required|string|max:200',
            'days' => 'required|integer|min:1|max:30',
            'budget' => 'required|numeric|min:0',
            'preferences' => 'nullable|array',
            'preferences.*.interests' => 'array',
            'preferences.*.interests.*' => 'string|max:50',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sanitized(): array
    {
        return [
            'destination' => strip_tags($this->destination),
            'days' => (int) $this->days,
            'budget' => (float) $this->budget,
            'preferences' => $this->preferences ?? [],
        ];
    }
}
```

### 7. Jobs & Queues

```php
class GenerateTravelPlanJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;
    public int $tries = 2;
    public int $maxExceptions = 3;

    /**
     * @param  array<string, mixed>  $planData
     */
    public function __construct(
        public int $userId,
        public array $planData
    ) {}

    public function handle(OpenAIService $openAI): void
    {
        // Implementation with proper error handling
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Job failed', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

---

## Frontend Standards (Livewire/Alpine.js/Tailwind)

### 1. Livewire Components

#### Component Structure
```php
<?php

namespace App\Livewire\Plans;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class PlanList extends Component
{
    // 1. Public properties (state)
    public string $search = '';
    public int $perPage = 10;

    // 2. Computed properties
    #[Computed]
    public function plans()
    {
        return Plan::query()
            ->where('title', 'like', "%{$this->search}%")
            ->paginate($this->perPage);
    }

    // 3. Actions
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $planId): void
    {
        $this->authorize('delete', Plan::find($planId));
        Plan::destroy($planId);
        $this->dispatch('plan-deleted');
    }

    // 4. Event listeners
    #[On('plan-updated')]
    public function refreshPlans(): void
    {
        $this->reset();
    }

    // 5. Render
    public function render()
    {
        return view('livewire.plans.plan-list');
    }
}
```

#### Property Types
Always declare types for Livewire properties:

```php
// ✅ Good
public string $destination = '';
public int $days = 3;
public ?User $user = null;

// ❌ Bad
public $destination;
public $days;
```

#### Computed Properties
Use computed properties for expensive operations:

```php
#[Computed]
public function totalCost(): float
{
    return $this->plan->activities->sum('cost');
}

// In view: $this->totalCost
```

### 2. Blade Templates

#### Template Organization
```blade
{{-- 1. Component/Section name --}}
{{-- plans/show.blade.php --}}

{{-- 2. Extends/Layouts --}}
@extends('layouts.app')

{{-- 3. Section definitions --}}
@section('title', 'View Plan')

@section('content')
    {{-- 4. Main content --}}
    <div class="container">
        @include('plans.partials.header')

        <livewire:plans.plan-details :plan="$plan" />

        @include('plans.partials.actions')
    </div>
@endsection

{{-- 5. Scripts (if needed) --}}
@push('scripts')
    <script>
        // Component-specific JS
    </script>
@endpush
```

#### Blade Directives
Prefer Blade directives over PHP:

```blade
{{-- ✅ Good --}}
@if($plan->isPublished())
    <span>Published</span>
@endif

@foreach($activities as $activity)
    <li>{{ $activity->name }}</li>
@endforeach

{{-- ❌ Bad --}}
<?php if ($plan->isPublished()): ?>
    <span>Published</span>
<?php endif; ?>
```

#### XSS Prevention
Always escape output unless intentionally rendering HTML:

```blade
{{-- Escaped (default) --}}
{{ $userInput }}

{{-- Raw HTML (be careful!) --}}
{!! $trustedHtml !!}

{{-- Safe HTML --}}
{!! clean($userGeneratedHtml) !!}
```

### 3. Alpine.js Usage

Use Alpine.js for simple client-side interactions:

```blade
{{-- Dropdown --}}
<div x-data="{ open: false }">
    <button @click="open = !open">
        Menu
    </button>
    <div x-show="open" @click.away="open = false">
        {{-- Menu items --}}
    </div>
</div>

{{-- Modal --}}
<div
    x-data="{ show: false }"
    @open-modal.window="show = true"
    @close-modal.window="show = false"
>
    <div x-show="show" x-transition>
        {{-- Modal content --}}
    </div>
</div>

{{-- Form submission --}}
<form
    x-data="{ submitting: false }"
    @submit="submitting = true"
>
    <button
        type="submit"
        :disabled="submitting"
        x-text="submitting ? 'Saving...' : 'Save'"
    ></button>
</form>
```

#### Alpine.js Best Practices
- Keep logic simple - complex logic belongs in Livewire
- Use descriptive variable names
- Prefer `@click` over `x-on:click`
- Use `x-cloak` to prevent FOUC (Flash of Unstyled Content)

### 4. Tailwind CSS

#### Utility-First Approach
```blade
{{-- ✅ Good: Utility classes --}}
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900">
        Travel Plans
    </h1>
</div>

{{-- ❌ Bad: Custom CSS --}}
<div class="custom-container">
    <h1 class="page-title">Travel Plans</h1>
</div>
```

#### Responsive Design
Mobile-first approach:

```blade
<div class="
    grid
    grid-cols-1
    sm:grid-cols-2
    lg:grid-cols-3
    gap-4
">
    {{-- Cards --}}
</div>
```

#### Component Extraction
Extract repeated patterns to Blade components:

```blade
{{-- resources/views/components/card.blade.php --}}
@props([
    'title' => null,
    'footer' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg shadow p-6']) }}>
    @if($title)
        <h3 class="text-lg font-semibold mb-4">{{ $title }}</h3>
    @endif

    {{ $slot }}

    @if($footer)
        <div class="mt-4 pt-4 border-t">
            {{ $footer }}
        </div>
    @endif
</div>

{{-- Usage --}}
<x-card title="Plan Details" class="mb-4">
    <p>Content here</p>

    <x-slot:footer>
        <button>Action</button>
    </x-slot:footer>
</x-card>
```

#### Color Consistency
Use Tailwind's semantic colors:

```blade
{{-- Primary actions --}}
<button class="bg-blue-600 hover:bg-blue-700 text-white">

{{-- Success --}}
<div class="bg-green-50 text-green-800 border border-green-200">

{{-- Error --}}
<div class="bg-red-50 text-red-800 border border-red-200">

{{-- Warning --}}
<div class="bg-yellow-50 text-yellow-800 border border-yellow-200">
```

### 5. JavaScript (Minimal)

Only use JavaScript when necessary:

```javascript
// resources/js/app.js
import './bootstrap';

// Simple utilities
window.copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
};

// Livewire event listener
document.addEventListener('livewire:init', () => {
    Livewire.on('plan-saved', (event) => {
        // Show toast notification
    });
});
```

---

## Database Standards

### 1. Migrations

#### Migration Naming
```php
// Descriptive, follows Laravel conventions
2025_01_10_create_plans_table.php
2025_01_10_add_status_to_plans_table.php
2025_01_10_create_plan_activities_pivot_table.php
```

#### Migration Structure
```php
public function up(): void
{
    Schema::create('plans', function (Blueprint $table) {
        // Primary key
        $table->id();

        // Foreign keys first
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        // Required fields
        $table->string('destination');
        $table->integer('days');
        $table->decimal('budget', 10, 2);

        // Optional fields
        $table->text('description')->nullable();
        $table->json('preferences')->nullable();

        // Status/state fields
        $table->enum('status', ['draft', 'published', 'archived'])
            ->default('draft');

        // Timestamps
        $table->timestamps();
        $table->softDeletes();

        // Indexes
        $table->index(['user_id', 'status']);
        $table->index('created_at');
    });
}

public function down(): void
{
    Schema::dropIfExists('plans');
}
```

### 2. Eloquent Models

#### Model Structure
```php
class Plan extends Model
{
    use HasFactory, SoftDeletes;

    // 1. Table configuration
    protected $table = 'plans';
    protected $primaryKey = 'id';

    // 2. Mass assignment
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'destination',
        'days',
        'budget',
    ];

    protected $guarded = ['id'];

    // 3. Casting
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preferences' => 'array',
            'budget' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    // 4. Relationships
    /**
     * @return BelongsTo<User, Plan>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Activity>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    // 5. Scopes
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at');
    }

    // 6. Accessors/Mutators
    protected function totalCost(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->activities->sum('cost')
        );
    }

    // 7. Business logic methods
    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}
```

### 3. Query Optimization

#### Eager Loading
```php
// ❌ Bad: N+1 queries
$plans = Plan::all();
foreach ($plans as $plan) {
    echo $plan->user->name; // N+1 problem
}

// ✅ Good: Eager loading
$plans = Plan::with('user')->get();
foreach ($plans as $plan) {
    echo $plan->user->name;
}

// ✅ Better: Constrained eager loading
$plans = Plan::with([
    'activities' => fn ($q) => $q->orderBy('time'),
    'user:id,name,email'
])->get();
```

#### Select Only Needed Columns
```php
// ❌ Bad
$plans = Plan::all();

// ✅ Good
$plans = Plan::select(['id', 'destination', 'days', 'budget'])->get();
```

---

## Testing Standards

### 1. Test Structure

```php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OpenAIServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_generates_structured_response(): void
    {
        // Arrange
        $service = app(OpenAIService::class);
        $schema = TravelItinerarySchema::get();

        // Act
        $response = $service->chat()
            ->withSystemMessage('You are a travel planner')
            ->withUserMessage('Plan a trip to Paris')
            ->withResponseFormat($schema)
            ->send();

        // Assert
        $this->assertTrue($response->isStructured());
        $this->assertArrayHasKey('destination', $response->getParsedContent());
    }
}
```

### 2. Test Naming

Use descriptive test names:
```php
// ✅ Good
test_user_can_create_plan_with_valid_data()
test_plan_generation_fails_with_invalid_api_key()
test_mock_service_is_used_in_testing_environment()

// ❌ Bad
testPlan()
testCreate()
test1()
```

### 3. Test Coverage

- **Minimum**: 70% code coverage
- **Target**: 80%+ code coverage
- Run tests with coverage:

```bash
make test-coverage
```

### 4. Feature vs Unit Tests

**Feature Tests**: Test full user workflows
```php
public function test_user_can_generate_travel_plan(): void
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post('/plans/generate', [
            'destination' => 'Paris',
            'days' => 3,
            'budget' => 1000,
        ]);

    $response->assertOk();
    $this->assertDatabaseHas('plans', [
        'user_id' => $user->id,
        'destination' => 'Paris',
    ]);
}
```

**Unit Tests**: Test isolated components
```php
public function test_request_builder_validates_temperature_range(): void
{
    $this->expectException(OpenAIValidationException::class);

    $service = app(OpenAIService::class);
    $service->chat()->withTemperature(3.0); // Invalid
}
```

---

## Git & Version Control

### 1. Commit Messages

Follow Conventional Commits:

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types**:
- `feat`: New feature
- `fix`: Bug fix
- `refactor`: Code refactoring
- `docs`: Documentation
- `test`: Tests
- `chore`: Build/tooling
- `style`: Formatting

**Examples**:
```bash
feat(openai): add retry logic with exponential backoff

Implements retry mechanism for rate limiting and server errors.
- Max 3 retries with exponential backoff
- Delays: 2s, 4s, 8s (capped at 60s)

Closes #123

fix(plans): prevent N+1 queries in plan list

Added eager loading for user and activities relationships
to reduce database queries from 100+ to 3.

test(openai): add tests for structured responses

Added 14 feature tests covering:
- Mock service initialization
- Structured JSON generation
- Parameter validation
- Error handling
```

### 2. Branch Naming

```bash
# Features
feature/plan-generation
feature/pdf-export

# Bug fixes
fix/authentication-redirect
fix/n-plus-one-queries

# Refactoring
refactor/service-pattern
refactor/livewire-components
```

### 3. Pull Request Guidelines

- Keep PRs small (< 400 lines changed)
- One feature/fix per PR
- All tests must pass
- PHPStan level 6 must pass
- Code style must pass

**PR Template**:
```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Tests added/updated
- [ ] All tests passing
- [ ] PHPStan passing
- [ ] Code style passing

## Screenshots (if applicable)
```

---

## Security Standards

### 1. Input Validation

**Always validate and sanitize**:
```php
class GeneratePlanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'destination' => 'required|string|max:200',
            'days' => 'required|integer|min:1|max:30',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sanitized(): array
    {
        return [
            'destination' => strip_tags($this->destination),
            'days' => (int) $this->days,
        ];
    }
}
```

### 2. Output Escaping

```blade
{{-- Auto-escaped --}}
{{ $userInput }}

{{-- Raw HTML (only for trusted sources) --}}
{!! $adminContent !!}

{{-- Cleaned HTML --}}
{!! clean($userGeneratedHtml) !!}
```

### 3. Authentication & Authorization

```php
// Use policies
Gate::define('update-plan', function (User $user, Plan $plan) {
    return $user->id === $plan->user_id;
});

// In controller
$this->authorize('update', $plan);

// In Livewire
public function delete(int $planId): void
{
    $plan = Plan::findOrFail($planId);
    $this->authorize('delete', $plan);
    $plan->delete();
}
```

### 4. API Keys & Secrets

- **Never** commit secrets to Git
- Use `.env` for all secrets
- Rotate API keys every 90 days
- Use different keys for dev/staging/production

```php
// ✅ Good
$apiKey = config('services.openai.api_key');

// ❌ Bad
$apiKey = 'sk-proj-xxxxx'; // Hardcoded!
```

---

## Performance Guidelines

### 1. Database Optimization

```php
// Use chunks for large datasets
Plan::chunk(100, function ($plans) {
    foreach ($plans as $plan) {
        // Process
    }
});

// Use lazy() for memory efficiency
Plan::lazy()->each(function ($plan) {
    // Process
});

// Index frequently queried columns
$table->index(['user_id', 'status']);
```

### 2. Caching

```php
// Cache expensive operations
$stats = Cache::remember('dashboard-stats', 3600, function () {
    return [
        'total_plans' => Plan::count(),
        'active_users' => User::active()->count(),
    ];
});

// Clear cache when data changes
Plan::created(function () {
    Cache::forget('dashboard-stats');
});
```

### 3. Queue Jobs

Move slow operations to queues:

```php
// ❌ Bad: Blocks HTTP request
public function generate(Request $request)
{
    $result = $this->openAI->generate(...); // 30s request
    return view('result', compact('result'));
}

// ✅ Good: Async processing
public function generate(Request $request)
{
    GeneratePlanJob::dispatch($userId, $planData);
    return redirect()->route('plans.index')
        ->with('message', 'Plan is being generated...');
}
```

### 4. Asset Optimization

```bash
# Production build
npm run build

# Minified CSS/JS
# Versioned assets (cache busting)
```

---

## Quality Checklist

Before committing code, verify:

- [ ] PHPStan passes: `make phpstan`
- [ ] Code style passes: `make cs-check`
- [ ] All tests pass: `make test`
- [ ] No N+1 queries (check Laravel Telescope in dev)
- [ ] Input validated and sanitized
- [ ] Output properly escaped
- [ ] Type hints on all methods
- [ ] PHPDoc for arrays/collections
- [ ] Meaningful variable/method names
- [ ] No sensitive data in code
- [ ] Commits follow conventional commits
- [ ] PR description is clear

**Run all quality checks**:
```bash
make quality
```

---

## Tools & Commands

### Backend Quality
```bash
# Static analysis
make phpstan

# Code style check
make cs-check

# Code style fix
make cs-fix

# Run tests
make test

# Run all checks
make quality
```

### Frontend Build
```bash
# Development
npm run dev

# Production build
npm run build
```

### Database
```bash
# Run migrations
make migrate

# Fresh database with seeds
make fresh
```

---

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs/11.x)
- [Livewire Documentation](https://livewire.laravel.com/docs)
- [Alpine.js Documentation](https://alpinejs.dev/)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [PSR Standards](https://www.php-fig.org/psr/)
- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)

---

**Last Updated**: 2025-01-10
**Version**: 1.0.0
