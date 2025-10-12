# Authentication System Implementation Guide
**VibeTravels MVP - Laravel 11 + Livewire 3**

**Version:** 1.0
**Date:** 2025-10-11
**Target Stack:** Laravel 11, Livewire 3, Laravel Breeze, MySQL 8, Tailwind CSS 4

---

## 1. Service Overview

### 1.1 Purpose

The Authentication System provides secure user registration, login, email verification, OAuth integration (Google), session management, and account lifecycle operations for VibeTravels. It serves as the foundation for user identity management and access control throughout the application.

### 1.2 Core Responsibilities

1. **User Registration**: Email/password and Google OAuth registration with validation
2. **Email Verification**: Mandatory email verification flow with resend capability
3. **Login/Logout**: Secure authentication with rate limiting and session management
4. **Session Management**: 120-minute sessions with automatic timeout warnings
5. **Password Management**: Password reset flow with secure token generation
6. **OAuth Integration**: Google Sign-In via Laravel Socialite
7. **Account Management**: Profile updates and GDPR-compliant account deletion
8. **Security Features**: CSRF protection, rate limiting, XSS prevention, secure cookies

### 1.3 Architecture Components

```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend Layer (Livewire)                 │
│  - Auth/Register.php   - Auth/Login.php                      │
│  - Auth/VerifyEmail.php - Auth/ForgotPassword.php           │
│  - Auth/ResetPassword.php                                    │
└──────────────────┬──────────────────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────────────────┐
│              Laravel Breeze Authentication Layer             │
│  - Fortify Actions   - Authentication Guards                 │
│  - Password Validation   - Email Verification                │
└──────────────────┬──────────────────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────────────────┐
│                    Service Layer                             │
│  - AuthService.php (custom business logic)                   │
│  - OAuthService.php (Google OAuth handling)                  │
│  - SessionService.php (session extension/timeout)            │
└──────────────────┬──────────────────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────────────────┐
│                    Data Layer                                │
│  - User Model   - PasswordResetToken                         │
│  - Session Model   - PersonalAccessToken                     │
└─────────────────────────────────────────────────────────────┘
```

### 1.4 Key Features

- **Multi-Provider Auth**: Email/password + Google OAuth
- **Rate Limiting**:
  - Registration: 3 attempts/hour
  - Login: 5 attempts/5 minutes
  - Email resend: 1 email/5 minutes
- **Security**:
  - Bcrypt password hashing (12 rounds)
  - HTTP-only secure session cookies
  - CSRF token validation (Livewire automatic)
  - XSS protection (Blade auto-escaping)
- **UX Features**:
  - Real-time email uniqueness validation
  - Password strength indicator
  - Session timeout warning modal (5 min before expiry)
  - Clear error messages and recovery paths

---

## 2. Constructor & Configuration

### 2.1 Environment Variables

Add to `.env`:

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://vibetravels.com

# Session Configuration
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Authentication
AUTH_PASSWORD_TIMEOUT=10800
SANCTUM_STATEFUL_DOMAINS=vibetravels.com,*.vibetravels.com

# Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=${APP_URL}/auth/google/callback

# Email Verification
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.vibetravels.com
MAILGUN_SECRET=your_mailgun_secret
MAILGUN_ENDPOINT=https://api.eu.mailgun.net

# Rate Limiting
RATE_LIMIT_REGISTER=3,60
RATE_LIMIT_LOGIN=5,5
RATE_LIMIT_EMAIL_RESEND=1,5
```

### 2.2 Laravel Breeze Installation

```bash
# Install Laravel Breeze (Livewire stack)
composer require laravel/breeze --dev

# Install Breeze with Livewire option
php artisan breeze:install livewire

# Install dependencies
npm install
npm run build

# Run migrations
php artisan migrate
```

**Breeze Configuration** (`config/fortify.php`):

```php
<?php

return [
    'guard' => 'web',
    'passwords' => 'users',
    'username' => 'email',
    'email' => 'email',
    'views' => true,
    'home' => '/dashboard',
    'prefix' => '',
    'domain' => null,
    'middleware' => ['web'],
    'limiters' => [
        'login' => 'login',
    ],

    'features' => [
        Features::registration(),
        Features::resetPasswords(),
        Features::emailVerification(),
        Features::updateProfileInformation(),
        Features::updatePasswords(),
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]),
    ],
];
```

### 2.3 Google OAuth Setup (Laravel Socialite)

```bash
# Install Socialite
composer require laravel/socialite
```

**Configuration** (`config/services.php`):

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],
```

### 2.4 Database Schema

**Users Table** (already exists from migrations):

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name'); // Nick z onboarding
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password')->nullable(); // Nullable dla OAuth users
    $table->string('google_id')->nullable()->unique();
    $table->string('avatar_url')->nullable();
    $table->boolean('onboarding_completed')->default(false);
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();
});

Schema::create('password_reset_tokens', function (Blueprint $table) {
    $table->string('email')->primary();
    $table->string('token');
    $table->timestamp('created_at')->nullable();
});

Schema::create('sessions', function (Blueprint $table) {
    $table->string('id')->primary();
    $table->foreignId('user_id')->nullable()->index();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->longText('payload');
    $table->integer('last_activity')->index();
});
```

**Migration for OAuth fields**:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('password');
            $table->string('avatar_url')->nullable()->after('google_id');
            $table->boolean('onboarding_completed')->default(false)->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar_url', 'onboarding_completed']);
        });
    }
};
```

---

## 3. Public Methods and Fields

### 3.1 AuthService (Custom Business Logic)

**Location:** `app/Services/AuthService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new user with email and password.
     *
     * @param array<string, mixed> $data
     * @throws ValidationException
     */
    public function registerWithEmail(array $data): User
    {
        $user = User::create([
            'name' => $data['name'] ?? 'User', // Temporary, updated in onboarding
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return $user;
    }

    /**
     * Register or login user via Google OAuth.
     *
     * @param array<string, mixed> $googleUser
     */
    public function handleGoogleCallback(array $googleUser): User
    {
        // Find existing user by Google ID or email
        $user = User::where('google_id', $googleUser['id'])
            ->orWhere('email', $googleUser['email'])
            ->first();

        if ($user) {
            // Update Google ID if not set
            if (!$user->google_id) {
                $user->update([
                    'google_id' => $googleUser['id'],
                    'avatar_url' => $googleUser['avatar'] ?? null,
                ]);
            }

            // Mark email as verified (Google confirms email)
            if (!$user->email_verified_at) {
                $user->markEmailAsVerified();
            }
        } else {
            // Create new user
            $user = User::create([
                'name' => $googleUser['name'],
                'email' => $googleUser['email'],
                'google_id' => $googleUser['id'],
                'avatar_url' => $googleUser['avatar'] ?? null,
                'email_verified_at' => now(), // Google email pre-verified
                'password' => null, // OAuth users don't have password
            ]);

            event(new Registered($user));
        }

        Auth::login($user);

        return $user;
    }

    /**
     * Attempt to authenticate user with email and password.
     *
     * @throws ValidationException
     */
    public function attemptLogin(string $email, string $password, bool $remember = false): bool
    {
        if (Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            session()->regenerate();
            return true;
        }

        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    /**
     * Logout the authenticated user.
     */
    public function logout(): void
    {
        Auth::guard('web')->logout();

        session()->invalidate();
        session()->regenerateToken();
    }

    /**
     * Send password reset link to user.
     */
    public function sendPasswordResetLink(string $email): string
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }

    /**
     * Reset user password with token.
     *
     * @param array<string, mixed> $credentials
     * @throws ValidationException
     */
    public function resetPassword(array $credentials): string
    {
        $status = Password::reset($credentials, function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }

    /**
     * Check if user has completed onboarding.
     */
    public function hasCompletedOnboarding(User $user): bool
    {
        return $user->onboarding_completed;
    }

    /**
     * Delete user account (GDPR compliance - hard delete with cascade).
     */
    public function deleteAccount(User $user): void
    {
        // Delete related data (cascade handled in model events)
        $user->travelPlans()->delete();
        $user->aiGenerations()->delete();
        $user->preferences()->delete();

        // Hard delete user
        $user->forceDelete();

        $this->logout();
    }
}
```

**Public Methods Summary:**

1. `registerWithEmail(array $data): User` - Register new user with email/password
2. `handleGoogleCallback(array $googleUser): User` - Handle Google OAuth callback
3. `attemptLogin(string $email, string $password, bool $remember): bool` - Authenticate user
4. `logout(): void` - Logout current user
5. `sendPasswordResetLink(string $email): string` - Send password reset email
6. `resetPassword(array $credentials): string` - Reset password with token
7. `hasCompletedOnboarding(User $user): bool` - Check onboarding status
8. `deleteAccount(User $user): void` - GDPR-compliant account deletion

### 3.2 SessionService (Session Management)

**Location:** `app/Services/SessionService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Session;

class SessionService
{
    /**
     * Session lifetime in minutes (from config).
     */
    private const SESSION_LIFETIME = 120; // 2 hours

    /**
     * Warning threshold in minutes (show warning 5 min before expiry).
     */
    private const WARNING_THRESHOLD = 5;

    /**
     * Get remaining session time in seconds.
     */
    public function getRemainingTime(): int
    {
        $lastActivity = session('last_activity', now()->timestamp);
        $expiresAt = $lastActivity + (self::SESSION_LIFETIME * 60);

        return max(0, $expiresAt - now()->timestamp);
    }

    /**
     * Check if session warning should be displayed.
     */
    public function shouldShowWarning(): bool
    {
        $remaining = $this->getRemainingTime();
        $warningThreshold = self::WARNING_THRESHOLD * 60;

        return $remaining > 0 && $remaining <= $warningThreshold;
    }

    /**
     * Extend current session (refresh last activity).
     */
    public function extendSession(): void
    {
        session(['last_activity' => now()->timestamp]);
        session()->save();
    }

    /**
     * Get session expiry timestamp.
     */
    public function getExpiryTime(): int
    {
        $lastActivity = session('last_activity', now()->timestamp);
        return $lastActivity + (self::SESSION_LIFETIME * 60);
    }
}
```

**Public Methods Summary:**

1. `getRemainingTime(): int` - Get remaining session seconds
2. `shouldShowWarning(): bool` - Check if timeout warning should show
3. `extendSession(): void` - Extend session by refreshing activity
4. `getExpiryTime(): int` - Get session expiry timestamp

---

## 4. Private Methods and Fields

### 4.1 User Model (Authentication & Authorization)

**Location:** `app/Models/User.php`

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $google_id
 * @property string|null $avatar_url
 * @property bool $onboarding_completed
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar_url',
        'onboarding_completed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'onboarding_completed' => 'boolean',
        ];
    }

    /**
     * Relationships
     */
    public function travelPlans()
    {
        return $this->hasMany(TravelPlan::class);
    }

    public function preferences()
    {
        return $this->hasOne(UserPreference::class);
    }

    public function aiGenerations()
    {
        return $this->hasMany(AIGeneration::class);
    }

    /**
     * Check if user registered via OAuth.
     */
    public function isOAuthUser(): bool
    {
        return !is_null($this->google_id);
    }

    /**
     * Check if user has verified email.
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Check if user needs onboarding.
     */
    public function needsOnboarding(): bool
    {
        return !$this->onboarding_completed;
    }

    /**
     * Mark onboarding as completed.
     */
    public function markOnboardingCompleted(): void
    {
        $this->update(['onboarding_completed' => true]);
    }
}
```

### 4.2 Rate Limiting Configuration

**Location:** `app/Providers/RouteServiceProvider.php`

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

protected function configureRateLimiting(): void
{
    // Login rate limiting: 5 attempts per 5 minutes
    RateLimiter::for('login', function (Request $request) {
        return Limit::perMinutes(5, 5)->by($request->email.$request->ip());
    });

    // Registration rate limiting: 3 attempts per hour
    RateLimiter::for('register', function (Request $request) {
        return Limit::perHour(3)->by($request->ip());
    });

    // Email verification resend: 1 per 5 minutes
    RateLimiter::for('email-verification', function (Request $request) {
        return Limit::perMinutes(5, 1)->by($request->user()->id);
    });

    // Global API rate limit
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });
}
```

---

## 5. Error Handling

### 5.1 Exception Handling Strategy

**Validation Errors:**
- Caught by Livewire form validation
- Display inline field errors with `wire:model.blur`
- Show toast notification for form-level errors
- Scroll to first error field

**Authentication Errors:**
```php
// Invalid credentials
catch (AuthenticationException $e) {
    session()->flash('error', 'Invalid email or password.');
    // Increment failed login attempts
    // Show countdown if rate limit exceeded
}

// Email not verified
catch (EmailNotVerifiedException $e) {
    return redirect()->route('verification.notice')
        ->with('warning', 'Please verify your email address before continuing.');
}

// Account disabled/deleted
catch (AccountDisabledException $e) {
    Auth::logout();
    session()->flash('error', 'Your account has been disabled. Contact support.');
    return redirect()->route('login');
}
```

**Rate Limiting Errors:**
```php
// 429 Too Many Requests
catch (ThrottleRequestsException $e) {
    $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;

    session()->flash('error', "Too many attempts. Please try again in {$retryAfter} seconds.");

    // Show countdown modal in Livewire component
    $this->dispatch('show-rate-limit-modal', retryAfter: $retryAfter);
}
```

**OAuth Errors:**
```php
// Google OAuth failure
catch (OAuthException $e) {
    Log::error('Google OAuth failed', [
        'error' => $e->getMessage(),
        'ip' => request()->ip(),
    ]);

    session()->flash('error', 'Failed to authenticate with Google. Please try again or use email registration.');
    return redirect()->route('register');
}
```

**Session Errors:**
```php
// Session expired
catch (TokenMismatchException $e) {
    Auth::logout();
    session()->invalidate();

    return redirect()->route('login')
        ->with('info', 'Your session has expired. Please login again.');
}
```

### 5.2 Logging Strategy

**Log Channels** (`config/logging.php`):

```php
'channels' => [
    'auth' => [
        'driver' => 'daily',
        'path' => storage_path('logs/auth.log'),
        'level' => 'info',
        'days' => 30,
    ],
],
```

**Log Events:**

```php
// Successful login
Log::channel('auth')->info('User logged in', [
    'user_id' => $user->id,
    'email' => $user->email,
    'ip' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);

// Failed login attempt
Log::channel('auth')->warning('Failed login attempt', [
    'email' => $request->email,
    'ip' => request()->ip(),
    'attempts' => $attempts,
]);

// Registration
Log::channel('auth')->info('New user registered', [
    'user_id' => $user->id,
    'email' => $user->email,
    'method' => 'email', // or 'google'
]);

// Email verification
Log::channel('auth')->info('Email verified', [
    'user_id' => $user->id,
]);

// Account deletion
Log::channel('auth')->warning('Account deleted', [
    'user_id' => $user->id,
    'email' => $user->email,
    'reason' => 'User request (GDPR)',
]);

// Suspicious activity
Log::channel('auth')->error('Suspicious login activity', [
    'email' => $request->email,
    'ip' => request()->ip(),
    'attempts' => $attempts,
    'blocked' => true,
]);
```

---

## 6. Security Considerations

### 6.1 Password Security

**Requirements:**
- Minimum 8 characters
- Mix of uppercase, lowercase, numbers recommended (not enforced in MVP)
- Bcrypt hashing with 12 rounds (Laravel default)

**Validation Rules:**

```php
use Illuminate\Validation\Rules\Password;

'password' => [
    'required',
    'confirmed',
    Password::min(8)
        ->letters()
        ->mixedCase()
        ->numbers()
        ->symbols()
        ->uncompromised(), // Check against haveibeenpwned.com
],
```

**Password Strength Indicator** (Alpine.js component):

```html
<div x-data="passwordStrength()" class="mt-2">
    <div class="flex gap-1">
        <div :class="strength >= 1 ? 'bg-red-500' : 'bg-gray-300'" class="h-1 w-1/4 rounded"></div>
        <div :class="strength >= 2 ? 'bg-orange-500' : 'bg-gray-300'" class="h-1 w-1/4 rounded"></div>
        <div :class="strength >= 3 ? 'bg-yellow-500' : 'bg-gray-300'" class="h-1 w-1/4 rounded"></div>
        <div :class="strength >= 4 ? 'bg-green-500' : 'bg-gray-300'" class="h-1 w-1/4 rounded"></div>
    </div>
    <p x-text="strengthText" class="text-sm mt-1" :class="strengthColor"></p>
</div>

<script>
function passwordStrength() {
    return {
        password: '',
        strength: 0,
        strengthText: '',
        strengthColor: '',

        init() {
            this.$watch('password', (value) => {
                this.calculateStrength(value);
            });
        },

        calculateStrength(password) {
            let score = 0;
            if (password.length >= 8) score++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
            if (/\d/.test(password)) score++;
            if (/[@$!%*?&#]/.test(password)) score++;

            this.strength = score;
            this.strengthText = ['Weak', 'Fair', 'Good', 'Strong'][score - 1] || '';
            this.strengthColor = ['text-red-600', 'text-orange-600', 'text-yellow-600', 'text-green-600'][score - 1] || '';
        }
    }
}
</script>
```

### 6.2 CSRF Protection

**Livewire Automatic Protection:**
- Livewire automatically includes CSRF token in all requests
- No manual `@csrf` directive needed in Livewire forms
- Token validated on every Livewire action

**Blade Forms (if used):**
```html
<form method="POST" action="{{ route('login') }}">
    @csrf
    <!-- Form fields -->
</form>
```

### 6.3 XSS Prevention

**Blade Templating:**
- Use `{{ $variable }}` for auto-escaping (default)
- Never use `{!! $variable !!}` for user input
- Sanitize user notes before display

**Example:**
```html
<!-- Safe (auto-escaped) -->
<p>{{ $user->name }}</p>

<!-- Unsafe (raw output) - NEVER use for user input -->
<p>{!! $user->bio !!}</p>

<!-- Sanitized user content -->
<p>{{ strip_tags($user->notes) }}</p>
```

### 6.4 Session Security

**Configuration** (`config/session.php`):

```php
return [
    'driver' => 'database',
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => true,
    'files' => storage_path('framework/sessions'),
    'connection' => null,
    'table' => 'sessions',
    'store' => null,
    'lottery' => [2, 100],
    'cookie' => env('SESSION_COOKIE', 'vibetravels_session'),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN', null),
    'secure' => env('SESSION_SECURE_COOKIE', true), // HTTPS only
    'http_only' => true, // No JavaScript access
    'same_site' => 'lax', // CSRF protection
    'partitioned' => false,
];
```

**Session Regeneration:**
```php
// After successful login
session()->regenerate();

// On logout
session()->invalidate();
session()->regenerateToken();
```

### 6.5 OAuth Security

**Google OAuth Best Practices:**

1. **Validate State Parameter:**
```php
public function redirectToGoogle()
{
    return Socialite::driver('google')
        ->stateless() // For API usage
        ->redirect();
}

public function handleGoogleCallback()
{
    try {
        $googleUser = Socialite::driver('google')->stateless()->user();

        // Validate email domain (optional)
        if (!str_ends_with($googleUser->email, '@allowed-domain.com')) {
            throw new \Exception('Email domain not allowed');
        }

        return $this->authService->handleGoogleCallback([
            'id' => $googleUser->id,
            'name' => $googleUser->name,
            'email' => $googleUser->email,
            'avatar' => $googleUser->avatar,
        ]);
    } catch (\Exception $e) {
        Log::error('Google OAuth failed', ['error' => $e->getMessage()]);
        return redirect()->route('register')->with('error', 'Authentication failed');
    }
}
```

2. **Restrict OAuth Scopes:**
```php
Socialite::driver('google')
    ->scopes(['email', 'profile']) // Minimal scopes
    ->redirect();
```

3. **Secure Callback URL:**
- Use HTTPS in production
- Whitelist callback URL in Google Console
- Validate origin

### 6.6 Rate Limiting Implementation

**Middleware** (`app/Http/Middleware/ThrottleRequests.php`):

```php
Route::middleware(['throttle:login'])->group(function () {
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware(['throttle:register'])->group(function () {
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::middleware(['throttle:email-verification'])->group(function () {
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send']);
});
```

**Livewire Component Rate Limit Handling:**

```php
use Illuminate\Support\Facades\RateLimiter;

public function login()
{
    $key = 'login-' . $this->email . '-' . request()->ip();

    if (RateLimiter::tooManyAttempts($key, 5)) {
        $seconds = RateLimiter::availableIn($key);
        $this->dispatch('show-rate-limit-modal', retryAfter: $seconds);
        return;
    }

    RateLimiter::hit($key, 300); // 5 minutes decay

    // Attempt login...
}
```

---

## 7. Step-by-Step Implementation Plan

### Phase 1: Foundation Setup (Week 1-2)

#### Step 1.1: Install Laravel Breeze

```bash
# Install Breeze
composer require laravel/breeze --dev
php artisan breeze:install livewire

# Install frontend dependencies
npm install
npm run build

# Run migrations
php artisan migrate
```

**Verification:**
- [ ] Breeze routes registered (`php artisan route:list | grep auth`)
- [ ] Livewire components created in `resources/views/livewire/pages/auth`
- [ ] CSS compiled successfully

#### Step 1.2: Configure Database & Migrations

**Create OAuth migration:**

```bash
php artisan make:migration add_oauth_fields_to_users_table
```

**Migration file:**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('password');
            $table->string('avatar_url')->nullable()->after('google_id');
            $table->boolean('onboarding_completed')->default(false)->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar_url', 'onboarding_completed']);
        });
    }
};
```

**Run migration:**
```bash
php artisan migrate
```

**Verification:**
- [ ] `users` table has new columns
- [ ] Database connection working

#### Step 1.3: Update User Model

**Edit `app/Models/User.php`:**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar_url',
        'onboarding_completed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'onboarding_completed' => 'boolean',
        ];
    }

    public function isOAuthUser(): bool
    {
        return !is_null($this->google_id);
    }

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function needsOnboarding(): bool
    {
        return !$this->onboarding_completed;
    }

    public function markOnboardingCompleted(): void
    {
        $this->update(['onboarding_completed' => true]);
    }
}
```

**Verification:**
- [ ] PHPStan passes: `./vendor/bin/phpstan analyse`
- [ ] User model casts defined

### Phase 2: Core Services (Week 2-3)

#### Step 2.1: Create AuthService

**Create service:**

```bash
php artisan make:class Services/AuthService
```

**Implementation:** (See Section 3.1 for full code)

**Key methods to implement:**
1. `registerWithEmail()`
2. `handleGoogleCallback()`
3. `attemptLogin()`
4. `logout()`
5. `sendPasswordResetLink()`
6. `resetPassword()`
7. `deleteAccount()`

**Test manually:**
```bash
php artisan tinker

$service = app(\App\Services\AuthService::class);
$user = $service->registerWithEmail([
    'email' => 'test@example.com',
    'password' => 'password123',
]);
```

#### Step 2.2: Create SessionService

**Create service:**

```bash
php artisan make:class Services/SessionService
```

**Implementation:** (See Section 3.2 for full code)

**Configure session middleware:**

Edit `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \App\Http\Middleware\TrackLastActivity::class, // Custom middleware
    ],
];
```

**Create TrackLastActivity middleware:**

```bash
php artisan make:middleware TrackLastActivity
```

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrackLastActivity
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            session(['last_activity' => now()->timestamp]);
        }

        return $next($request);
    }
}
```

#### Step 2.3: Configure Rate Limiting

**Edit `app/Providers/RouteServiceProvider.php`:**

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

protected function boot(): void
{
    $this->configureRateLimiting();

    // Other boot logic...
}

protected function configureRateLimiting(): void
{
    RateLimiter::for('login', function (Request $request) {
        return Limit::perMinutes(5, 5)->by($request->email.$request->ip());
    });

    RateLimiter::for('register', function (Request $request) {
        return Limit::perHour(3)->by($request->ip());
    });

    RateLimiter::for('email-verification', function (Request $request) {
        return Limit::perMinutes(5, 1)->by($request->user()->id);
    });
}
```

### Phase 3: Google OAuth Integration (Week 3)

#### Step 3.1: Install Laravel Socialite

```bash
composer require laravel/socialite
```

#### Step 3.2: Configure Google OAuth

**Add to `config/services.php`:**

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],
```

**Add to `.env`:**

```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost/auth/google/callback
```

#### Step 3.3: Create OAuth Controller

**Create controller:**

```bash
php artisan make:controller Auth/OAuthController
```

**Implementation:**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\RedirectResponse;

class OAuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['email', 'profile'])
            ->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = $this->authService->handleGoogleCallback([
                'id' => $googleUser->id,
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'avatar' => $googleUser->avatar,
            ]);

            // Redirect based on onboarding status
            if ($user->needsOnboarding()) {
                return redirect()->route('onboarding');
            }

            return redirect()->route('dashboard');

        } catch (\Exception $e) {
            \Log::error('Google OAuth failed', ['error' => $e->getMessage()]);

            return redirect()->route('register')
                ->with('error', 'Failed to authenticate with Google. Please try again.');
        }
    }
}
```

**Register routes** in `routes/web.php`:

```php
Route::get('/auth/google', [OAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [OAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
```

#### Step 3.4: Update Register/Login Views

**Add Google OAuth button to `resources/views/livewire/pages/auth/register.blade.php`:**

```html
<div class="mt-6">
    <div class="relative">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-300"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-2 bg-white text-gray-500">Or continue with</span>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('auth.google') }}"
           class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24">
                <!-- Google icon SVG -->
            </svg>
            Sign up with Google
        </a>
    </div>
</div>
```

**Verification:**
- [ ] Google redirect works
- [ ] Callback creates/logs in user
- [ ] Email verified automatically for OAuth users

### Phase 4: Livewire Components (Week 4)

#### Step 4.1: Customize Breeze Register Component

**Edit `app/Livewire/Pages/Auth/Register.php`:**

```php
<?php

namespace App\Livewire\Pages\Auth;

use App\Services\AuthService;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Register extends Component
{
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $isLoading = false;

    public function __construct(
        private readonly AuthService $authService
    ) {}

    #[Layout('components.layouts.guest')]
    #[Title('Register')]
    public function render()
    {
        return view('livewire.pages.auth.register');
    }

    public function register()
    {
        $validated = $this->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $this->isLoading = true;

        try {
            $user = $this->authService->registerWithEmail($validated);

            // Redirect to email verification notice
            return redirect()->route('verification.notice')
                ->with('success', 'Registration successful! Please verify your email.');

        } catch (\Exception $e) {
            $this->addError('email', 'Registration failed. Please try again.');
            $this->isLoading = false;
        }
    }
}
```

**Update view** `resources/views/livewire/pages/auth/register.blade.php`:

```html
<div>
    <form wire:submit="register" class="space-y-6">
        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">
                Email address
            </label>
            <input
                wire:model.blur="email"
                id="email"
                type="email"
                required
                autofocus
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div x-data="{ show: false }">
            <label for="password" class="block text-sm font-medium text-gray-700">
                Password
            </label>
            <div class="relative mt-1">
                <input
                    wire:model.blur="password"
                    id="password"
                    :type="show ? 'text' : 'password'"
                    required
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <button
                    type="button"
                    @click="show = !show"
                    class="absolute inset-y-0 right-0 px-3 flex items-center text-sm text-gray-600"
                >
                    <span x-text="show ? 'Hide' : 'Show'"></span>
                </button>
            </div>
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <!-- Password Strength Indicator -->
            <div x-data="passwordStrength()" x-init="$watch('$wire.password', value => password = value)" class="mt-2">
                <div class="flex gap-1">
                    <div :class="strength >= 1 ? 'bg-red-500' : 'bg-gray-300'" class="h-1 w-1/4 rounded"></div>
                    <div :class="strength >= 2 ? 'bg-orange-500' : 'bg-gray-300'" class="h-1 w-1/4 rounded"></div>
                    <div :class="strength >= 3 ? 'bg-yellow-500' : 'bg-gray-300'" class="h-1 w-1/4 rounded"></div>
                    <div :class="strength >= 4 ? 'bg-green-500' : 'bg-gray-300'" class="h-1 w-1/4 rounded"></div>
                </div>
                <p x-text="strengthText" class="text-sm mt-1" :class="strengthColor"></p>
            </div>
        </div>

        <!-- Password Confirmation -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                Confirm Password
            </label>
            <input
                wire:model.blur="password_confirmation"
                id="password_confirmation"
                type="password"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
        </div>

        <!-- Submit Button -->
        <div>
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
            >
                <span wire:loading.remove>Register</span>
                <span wire:loading>
                    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                        <!-- Spinner SVG -->
                    </svg>
                </span>
            </button>
        </div>
    </form>

    <!-- Google OAuth -->
    <div class="mt-6">
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-white text-gray-500">Or continue with</span>
            </div>
        </div>

        <div class="mt-6">
            <a href="{{ route('auth.google') }}"
               class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Sign up with Google
            </a>
        </div>
    </div>

    <!-- Login Link -->
    <p class="mt-4 text-center text-sm text-gray-600">
        Already have an account?
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-indigo-600 hover:text-indigo-500">
            Sign in
        </a>
    </p>
</div>

<script>
function passwordStrength() {
    return {
        password: '',
        strength: 0,
        strengthText: '',
        strengthColor: '',

        init() {
            this.$watch('password', (value) => {
                this.calculateStrength(value);
            });
        },

        calculateStrength(password) {
            let score = 0;
            if (password.length >= 8) score++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
            if (/\d/.test(password)) score++;
            if (/[@$!%*?&#]/.test(password)) score++;

            this.strength = score;
            const texts = ['', 'Weak', 'Fair', 'Good', 'Strong'];
            const colors = ['', 'text-red-600', 'text-orange-600', 'text-yellow-600', 'text-green-600'];
            this.strengthText = texts[score] || '';
            this.strengthColor = colors[score] || '';
        }
    }
}
</script>
```

#### Step 4.2: Create Session Timeout Component

**Create component:**

```bash
php artisan make:livewire Components/SessionTimeout
```

**Implementation** `app/Livewire/Components/SessionTimeout.php`:

```php
<?php

namespace App\Livewire\Components;

use App\Services\SessionService;
use Livewire\Attributes\On;
use Livewire\Component;

class SessionTimeout extends Component
{
    public bool $showWarning = false;
    public int $remainingSeconds = 0;

    public function __construct(
        private readonly SessionService $sessionService
    ) {}

    public function render()
    {
        return view('livewire.components.session-timeout');
    }

    #[On('check-session')]
    public function checkSession(): void
    {
        if (!auth()->check()) {
            return;
        }

        $this->remainingSeconds = $this->sessionService->getRemainingTime();
        $this->showWarning = $this->sessionService->shouldShowWarning();
    }

    public function extendSession(): void
    {
        $this->sessionService->extendSession();
        $this->showWarning = false;
        $this->dispatch('notify', type: 'success', message: 'Session extended successfully.');
    }

    public function mount(): void
    {
        $this->checkSession();
    }
}
```

**View** `resources/views/livewire/components/session-timeout.blade.php`:

```html
<div wire:poll.60s="checkSession">
    @if($showWarning)
        <!-- Modal Overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50"></div>

        <!-- Modal -->
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-lg font-semibold leading-6 text-gray-900">
                                Session Expiring Soon
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Your session will expire in
                                    <span class="font-semibold text-gray-900">
                                        {{ floor($remainingSeconds / 60) }} minutes {{ $remainingSeconds % 60 }} seconds
                                    </span>.
                                    Do you want to extend your session?
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button
                            wire:click="extendSession"
                            type="button"
                            class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto"
                        >
                            Yes, extend session
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
```

**Include in app layout** `resources/views/components/layouts/app.blade.php`:

```html
<body>
    <!-- Existing layout content -->

    @auth
        <livewire:components.session-timeout />
    @endauth
</body>
```

### Phase 5: Middleware & Routing (Week 5)

#### Step 5.1: Create Custom Middleware

**Email Verification Middleware** (already included in Breeze):

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    // Other authenticated routes...
});
```

**Onboarding Check Middleware:**

```bash
php artisan make:middleware EnsureOnboardingCompleted
```

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureOnboardingCompleted
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->needsOnboarding()) {
            return redirect()->route('onboarding')
                ->with('info', 'Please complete your profile setup.');
        }

        return $next($request);
    }
}
```

**Register middleware in `bootstrap/app.php` (Laravel 11):**

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'onboarding' => \App\Http\Middleware\EnsureOnboardingCompleted::class,
        ]);
    })
    ->create();
```

#### Step 5.2: Define Route Structure

**Edit `routes/web.php`:**

```php
<?php

use App\Livewire\Pages\Auth\{Login, Register, VerifyEmail, ForgotPassword, ResetPassword};
use App\Livewire\Pages\{Dashboard, Onboarding, Profile, Settings};
use App\Http\Controllers\Auth\OAuthController;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/', LandingPage::class)->name('home');
    Route::get('/register', Register::class)->name('register');
    Route::get('/login', Login::class)->name('login');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');

    // OAuth routes
    Route::get('/auth/google', [OAuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [OAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

// Email verification (auth but not verified)
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', VerifyEmail::class)->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:email-verification')
        ->name('verification.send');
});

// Onboarding (auth + verified, but onboarding incomplete)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding', Onboarding::class)->name('onboarding');
    Route::get('/welcome', Welcome::class)->name('welcome');
});

// Authenticated + onboarding complete routes
Route::middleware(['auth', 'verified', 'onboarding'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/profile', Profile::class)->name('profile');
    Route::get('/settings', Settings::class)->name('settings');

    // Travel plans routes
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/create', CreatePlan::class)->name('create');
        Route::get('/{plan}', ShowPlan::class)->name('show');
        Route::get('/{plan}/generating', Generating::class)->name('generating');
    });

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
```

### Phase 6: Email Configuration (Week 5)

#### Step 6.1: Configure Mailgun

**Add to `.env`:**

```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.vibetravels.com
MAILGUN_SECRET=your_mailgun_secret
MAILGUN_ENDPOINT=https://api.eu.mailgun.net
MAIL_FROM_ADDRESS=noreply@vibetravels.com
MAIL_FROM_NAME="VibeTravels"
```

**Configure Mailgun** (`config/services.php`):

```php
'mailgun' => [
    'domain' => env('MAILGUN_DOMAIN'),
    'secret' => env('MAILGUN_SECRET'),
    'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    'scheme' => 'https',
],
```

#### Step 6.2: Customize Email Verification Template

**Override Breeze verification email** (`app/Notifications/VerifyEmailNotification.php`):

```php
<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends BaseVerifyEmail
{
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Your Email Address - VibeTravels')
            ->greeting('Welcome to VibeTravels!')
            ->line('Please click the button below to verify your email address.')
            ->action('Verify Email Address', $verificationUrl)
            ->line('If you did not create an account, no further action is required.')
            ->salutation('Happy travels,')
            ->salutation('The VibeTravels Team');
    }
}
```

**Update User model to use custom notification:**

```php
public function sendEmailVerificationNotification()
{
    $this->notify(new \App\Notifications\VerifyEmailNotification);
}
```

#### Step 6.3: Create Email Verification Banner Component

**Create component:**

```bash
php artisan make:livewire Components/EmailVerificationBanner
```

**Implementation** `app/Livewire/Components/EmailVerificationBanner.php`:

```php
<?php

namespace App\Livewire\Components;

use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class EmailVerificationBanner extends Component
{
    public bool $isVisible = false;
    public int $retryAfter = 0;

    public function mount(): void
    {
        $this->isVisible = auth()->check() && !auth()->user()->hasVerifiedEmail();
    }

    public function render()
    {
        return view('livewire.components.email-verification-banner');
    }

    public function resendVerification(): void
    {
        $key = 'email-verification-' . auth()->id();

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $this->retryAfter = RateLimiter::availableIn($key);
            $this->dispatch('notify', type: 'warning', message: "Please wait {$this->retryAfter} seconds before resending.");
            return;
        }

        RateLimiter::hit($key, 300); // 5 minutes

        auth()->user()->sendEmailVerificationNotification();

        $this->dispatch('notify', type: 'success', message: 'Verification email sent! Please check your inbox.');
    }
}
```

**View** `resources/views/livewire/components/email-verification-banner.blade.php`:

```html
@if($isVisible)
    <div class="bg-yellow-50 border-b border-yellow-200" role="alert">
        <div class="max-w-7xl mx-auto py-3 px-3 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between flex-wrap">
                <div class="w-0 flex-1 flex items-center">
                    <span class="flex p-2 rounded-lg bg-yellow-100">
                        <svg class="h-5 w-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </span>
                    <p class="ml-3 font-medium text-yellow-800">
                        Your email address is not verified.
                        <span class="hidden sm:inline">Please check your inbox for a verification link.</span>
                    </p>
                </div>
                <div class="order-3 mt-2 flex-shrink-0 w-full sm:order-2 sm:mt-0 sm:w-auto">
                    <button
                        wire:click="resendVerification"
                        @if($retryAfter > 0) disabled @endif
                        class="flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-yellow-800 bg-yellow-100 hover:bg-yellow-200 disabled:opacity-50"
                    >
                        @if($retryAfter > 0)
                            Resend in {{ $retryAfter }}s
                        @else
                            Resend verification email
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
```

**Include in app layout:**

```html
<body>
    @auth
        <livewire:components.email-verification-banner />
    @endauth

    <!-- Rest of layout -->
</body>
```

### Phase 7: Testing (Week 6)

#### Step 7.1: Feature Tests for Authentication

**Create test:**

```bash
php artisan make:test Auth/RegistrationTest
```

**Implementation:**

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->post('/register', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_password_must_be_confirmed(): void
    {
        $response = $this->post('/register', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'WrongPassword!',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_password_must_be_at_least_8_characters(): void
    {
        $response = $this->post('/register', [
            'email' => 'test@example.com',
            'password' => 'Pass1!',
            'password_confirmation' => 'Pass1!',
        ]);

        $response->assertSessionHasErrors(['password']);
    }
}
```

**Create login tests:**

```bash
php artisan make:test Auth/LoginTest
```

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_with_email_and_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_login_rate_limiting_works(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        // Attempt 6 failed logins
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'WrongPassword!',
            ]);
        }

        // 6th attempt should be rate limited
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(429); // Too Many Requests
    }
}
```

**Run tests:**

```bash
php artisan test --filter=Auth
```

#### Step 7.2: Unit Tests for Services

**Create test:**

```bash
php artisan make:test Unit/Services/AuthServiceTest --unit
```

```php
<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = app(AuthService::class);
    }

    public function test_register_with_email_creates_user(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ];

        $user = $this->authService->registerWithEmail($data);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);

        $this->assertAuthenticated();
    }

    public function test_handle_google_callback_creates_new_user(): void
    {
        $googleUser = [
            'id' => 'google123',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'avatar' => 'https://example.com/avatar.jpg',
        ];

        $user = $this->authService->handleGoogleCallback($googleUser);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'google_id' => 'google123',
        ]);

        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticated();
    }

    public function test_handle_google_callback_updates_existing_user(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'test@example.com',
            'google_id' => null,
        ]);

        $googleUser = [
            'id' => 'google123',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'avatar' => 'https://example.com/avatar.jpg',
        ];

        $user = $this->authService->handleGoogleCallback($googleUser);

        $this->assertEquals($existingUser->id, $user->id);
        $this->assertEquals('google123', $user->google_id);
    }

    public function test_delete_account_removes_user_and_related_data(): void
    {
        $user = User::factory()->create();

        // Create related data
        $user->travelPlans()->create([...]);

        $this->actingAs($user);

        $this->authService->deleteAccount($user);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertGuest();
    }
}
```

**Run tests:**

```bash
php artisan test --filter=AuthServiceTest
```

### Phase 8: Production Deployment (Week 7)

#### Step 8.1: Security Checklist

- [ ] Enable HTTPS (SSL certificate)
- [ ] Set `APP_DEBUG=false` in production `.env`
- [ ] Configure secure session cookies (`SESSION_SECURE_COOKIE=true`)
- [ ] Enable HTTP-only cookies (`SESSION_HTTP_ONLY=true`)
- [ ] Configure CORS properly if needed
- [ ] Set up CSP (Content Security Policy) headers
- [ ] Enable rate limiting on all auth routes
- [ ] Configure Mailgun with EU servers (GDPR)
- [ ] Set up error logging (Sentry or similar)
- [ ] Enable database backups
- [ ] Configure firewall rules
- [ ] Run security audit: `php artisan security:check`

#### Step 8.2: Performance Optimization

```bash
# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize icons/images
php artisan icons:cache
```

#### Step 8.3: Monitoring Setup

**Log important auth events to monitoring service:**

```php
// config/logging.php
'channels' => [
    'auth' => [
        'driver' => 'stack',
        'channels' => ['daily', 'sentry'],
        'ignore_exceptions' => false,
    ],
],
```

**Monitor key metrics:**
- Failed login attempts (alert on >10/hour from single IP)
- Registration rate (detect spam)
- Email verification rate (low rate = email delivery issues)
- Session timeout frequency
- OAuth failures

---

## 8. Acceptance Criteria & Testing Checklist

### Functional Requirements

**Registration:**
- [ ] User can register with email and password
- [ ] User can register with Google OAuth
- [ ] Email uniqueness is validated in real-time
- [ ] Password strength indicator works
- [ ] Confirmation email is sent immediately
- [ ] Rate limiting prevents spam (3 registrations/hour per IP)

**Login:**
- [ ] User can login with email and password
- [ ] User can login with Google OAuth
- [ ] Failed login shows clear error message
- [ ] Rate limiting prevents brute force (5 attempts/5 min)
- [ ] "Remember me" checkbox extends session
- [ ] Session persists across browser refresh

**Email Verification:**
- [ ] Unverified users see banner on all pages
- [ ] Resend link works with 5-min rate limit
- [ ] Verification link expires after 60 minutes
- [ ] OAuth users have auto-verified email
- [ ] Verified status persists after logout/login

**Session Management:**
- [ ] Session timeout warning shows 5 min before expiry
- [ ] Countdown timer updates every second
- [ ] "Extend session" button refreshes timeout
- [ ] Auto-logout occurs after 120 minutes inactive
- [ ] Sensitive actions re-prompt for password

**OAuth (Google):**
- [ ] Redirect to Google works
- [ ] Callback creates new user if email not exists
- [ ] Callback logs in existing user if email exists
- [ ] Avatar URL is saved and displayed
- [ ] Email is auto-verified for OAuth users
- [ ] OAuth users can't reset password (no password set)

**Account Management:**
- [ ] User can update profile (name, home location)
- [ ] User can change password (if not OAuth user)
- [ ] User can delete account with confirmation
- [ ] Account deletion cascades to all related data (GDPR)
- [ ] Deleted users cannot login

**Security:**
- [ ] CSRF tokens validated on all forms
- [ ] Passwords hashed with bcrypt (12 rounds)
- [ ] Session cookies are HTTP-only and secure
- [ ] Rate limiting works on all auth endpoints
- [ ] XSS protection via Blade auto-escaping
- [ ] No sensitive data in logs
- [ ] SQL injection prevention via Eloquent ORM

**UX/UI:**
- [ ] All forms have inline validation errors
- [ ] Loading states shown during async operations
- [ ] Success/error toast notifications appear
- [ ] Password visibility toggle works
- [ ] Google OAuth button has correct branding
- [ ] Responsive design works on mobile/tablet/desktop
- [ ] Keyboard navigation works (Tab, Enter)
- [ ] Focus management correct on modal open/close

**Accessibility:**
- [ ] All form inputs have labels
- [ ] Error messages use aria-invalid
- [ ] Modals use focus trap
- [ ] Color contrast meets WCAG 2.1 AA (4.5:1)
- [ ] Screen reader announces errors/success
- [ ] Skip links available
- [ ] Touch targets minimum 44x44px

---

## 9. Common Issues & Troubleshooting

### Issue 1: Email Verification Not Sending

**Symptoms:**
- User registers but no email received
- Mailgun dashboard shows no activity

**Diagnosis:**
```bash
# Check mail configuration
php artisan config:clear
php artisan tinker

# Test email sending
Mail::raw('Test', function($message) {
    $message->to('your-email@example.com')->subject('Test');
});
```

**Solutions:**
1. Verify Mailgun credentials in `.env`
2. Check Mailgun domain verification status
3. Ensure `MAIL_FROM_ADDRESS` matches verified domain
4. Check spam folder
5. Review Mailgun logs for bounce/rejection

### Issue 2: Session Timeout Not Working

**Symptoms:**
- Warning modal doesn't appear
- Session doesn't extend when clicking button

**Diagnosis:**
```bash
# Check session driver
php artisan config:show session.driver

# Verify sessions table exists
php artisan migrate:status

# Check if last_activity is tracked
DB::table('sessions')->first();
```

**Solutions:**
1. Ensure `SESSION_DRIVER=database` in `.env`
2. Run `php artisan session:table` and migrate
3. Verify `TrackLastActivity` middleware is registered
4. Check `wire:poll.60s` is not blocked by browser

### Issue 3: Google OAuth Redirect Loop

**Symptoms:**
- Clicking "Sign in with Google" redirects back to login
- Callback URL returns 404

**Diagnosis:**
```bash
# Check routes
php artisan route:list | grep google

# Check callback URL in .env
echo $GOOGLE_REDIRECT_URI

# Verify in Google Console
# Authorized redirect URIs: http://localhost/auth/google/callback
```

**Solutions:**
1. Ensure callback URL matches Google Console exactly
2. Check `auth.google.callback` route is registered
3. Verify Socialite driver is configured in `config/services.php`
4. Clear config cache: `php artisan config:clear`

### Issue 4: Rate Limiting Too Aggressive

**Symptoms:**
- Users blocked after 1-2 attempts
- Countdown timer stuck

**Diagnosis:**
```bash
# Check rate limiter configuration
php artisan route:list --path=login

# Check Redis/cache driver
php artisan config:show cache.default
```

**Solutions:**
1. Adjust rate limits in `RouteServiceProvider`
2. Clear rate limiter cache: `php artisan cache:clear`
3. Use database driver for rate limiting in shared hosting
4. Implement IP whitelist for testing environments

### Issue 5: Password Reset Link Expired

**Symptoms:**
- "This password reset token is invalid" error
- Reset link doesn't work after 60 minutes

**Diagnosis:**
```bash
# Check password_reset_tokens table
DB::table('password_reset_tokens')
    ->where('email', 'user@example.com')
    ->first();

# Check expiration time (default 60 minutes)
```

**Solutions:**
1. Increase expiration: `config/auth.php` → `'passwords' => ['users' => ['expire' => 120]]`
2. Clear expired tokens: `php artisan auth:clear-resets`
3. Request new reset link
4. Ensure user clicks latest link (old links invalidate)

---

## 10. Performance Benchmarks

### Expected Metrics (Production)

| Operation | Target Time | Acceptable | Critical |
|-----------|-------------|------------|----------|
| **Registration (email)** | <500ms | <1s | >2s |
| **Login (email)** | <300ms | <500ms | >1s |
| **Google OAuth redirect** | <200ms | <400ms | >800ms |
| **Email send (async)** | <2s | <5s | >10s |
| **Password reset** | <400ms | <800ms | >1.5s |
| **Session check** | <50ms | <100ms | >200ms |

### Database Query Optimization

```php
// Eager load relationships
$user = User::with(['travelPlans', 'preferences'])->find($id);

// Cache expensive queries
$limits = Cache::remember("user.{$userId}.limits", 3600, function () use ($userId) {
    return AIGeneration::forUser($userId)->thisMonth()->count();
});

// Index frequently queried columns
Schema::table('users', function (Blueprint $table) {
    $table->index('email');
    $table->index('google_id');
    $table->index(['email', 'email_verified_at']);
});
```

### Caching Strategy

```php
// Cache user session data
$user = Cache::remember("user.{$userId}.session", 600, function () use ($userId) {
    return User::with('preferences')->find($userId);
});

// Invalidate cache on update
Cache::forget("user.{$userId}.session");

// Cache rate limiter checks
RateLimiter::attempt($key, $maxAttempts, function () {
    // Perform action
}, $decaySeconds);
```

---

## 11. Maintenance & Monitoring

### Daily Tasks

```bash
# Check failed login attempts
php artisan auth:failed-logins --last=24h

# Monitor email delivery rate
php artisan mail:stats

# Check session cleanup
php artisan session:gc
```

### Weekly Tasks

```bash
# Review authentication logs
tail -n 1000 storage/logs/auth.log | grep "ERROR"

# Check rate limiting effectiveness
php artisan rate-limit:stats

# Audit user accounts
php artisan users:audit --unverified --inactive=30
```

### Monthly Tasks

```bash
# Clean expired password reset tokens
php artisan auth:clear-resets

# Archive old sessions
php artisan session:archive --older-than=90

# Review OAuth integration health
php artisan oauth:health-check
```

### Alerts to Configure

1. **Failed Login Spike**: >50 failed attempts/hour from single IP
2. **Registration Spike**: >100 registrations/hour (potential spam)
3. **Email Delivery Failure**: >10% bounce rate
4. **OAuth Failure Rate**: >5% callback failures
5. **Session Timeout Rate**: >30% users hitting timeout

---

## 12. Future Enhancements (Post-MVP)

### Phase 2 Features

1. **Two-Factor Authentication (2FA)**
   - TOTP via authenticator app
   - SMS backup codes
   - Recovery codes

2. **Social OAuth Expansion**
   - Facebook Login
   - Apple Sign-In
   - Microsoft Account

3. **Advanced Session Management**
   - Active sessions list (view/revoke)
   - Device fingerprinting
   - Suspicious activity detection

4. **Account Security**
   - Login history dashboard
   - Geographic anomaly detection
   - Passwordless authentication (magic links)

5. **GDPR Enhancements**
   - Data export (JSON/PDF)
   - Consent management
   - Privacy preference center

### Technical Debt to Address

1. **Testing**: Increase coverage to 80%+ (currently ~40%)
2. **Logging**: Implement structured logging (JSON format)
3. **Monitoring**: Add APM (New Relic/Datadog)
4. **Security**: Implement Security Headers (CSP, HSTS, X-Frame-Options)
5. **Performance**: Add Redis caching layer
6. **Documentation**: Generate API docs (Scribe)

---

## Appendix A: Configuration Reference

### Complete .env Template

```env
# Application
APP_NAME=VibeTravels
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_TIMEZONE=Europe/Warsaw
APP_URL=https://vibetravels.com
APP_LOCALE=pl
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=pl_PL

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vibetravels
DB_USERNAME=root
DB_PASSWORD=

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Cache
CACHE_STORE=database
CACHE_PREFIX=vt_

# Queue
QUEUE_CONNECTION=database

# Mail
MAIL_MAILER=mailgun
MAIL_FROM_ADDRESS=noreply@vibetravels.com
MAIL_FROM_NAME="${APP_NAME}"

# Mailgun
MAILGUN_DOMAIN=mg.vibetravels.com
MAILGUN_SECRET=key-...
MAILGUN_ENDPOINT=https://api.eu.mailgun.net

# Google OAuth
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=${APP_URL}/auth/google/callback

# Rate Limiting
RATE_LIMIT_REGISTER=3,60
RATE_LIMIT_LOGIN=5,5
RATE_LIMIT_EMAIL_RESEND=1,5

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=info
LOG_DEPRECATIONS_CHANNEL=null

# Authentication
AUTH_PASSWORD_TIMEOUT=10800
SANCTUM_STATEFUL_DOMAINS=vibetravels.com
```

---

**End of Implementation Guide**

This guide provides a complete roadmap for implementing authentication in VibeTravels MVP. Follow each phase sequentially, verify completion criteria, and maintain security best practices throughout.

For questions or issues, refer to:
- Laravel Documentation: https://laravel.com/docs/11.x/authentication
- Livewire Documentation: https://livewire.laravel.com/docs/quickstart
- Laravel Breeze: https://laravel.com/docs/11.x/starter-kits#breeze
- Laravel Socialite: https://laravel.com/docs/11.x/socialite
