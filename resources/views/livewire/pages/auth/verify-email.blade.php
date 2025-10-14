<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        // Rate limiting: 1 email per 5 minutes (300 seconds)
        $cacheKey = 'email-verification-sent-' . Auth::id();
        $lastSentAt = cache()->get($cacheKey);

        if ($lastSentAt) {
            $secondsRemaining = 300 - (now()->timestamp - $lastSentAt);
            if ($secondsRemaining > 0) {
                Session::flash('status', 'verification-throttled');
                Session::flash('retry_after', $secondsRemaining);
                return;
            }
        }

        Auth::user()->sendEmailVerificationNotification();

        // Store timestamp in cache for 5 minutes
        cache()->put($cacheKey, now()->timestamp, 300);

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/');
    }
}; ?>

<div>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        Dziękujemy za rejestrację! Zanim zaczniesz, zweryfikuj swój adres email klikając w link, który właśnie wysłaliśmy na Twoją skrzynkę. Jeśli nie otrzymałeś emaila, chętnie wyślemy kolejny.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            Nowy link weryfikacyjny został wysłany na podany adres email.
        </div>
    @endif

    @if (session('status') == 'verification-throttled')
        <div class="mb-4 font-medium text-sm text-yellow-600 dark:text-yellow-400">
            Możesz wysłać ponownie email weryfikacyjny za {{ session('retry_after') }} sekund.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <x-primary-button wire:click="sendVerification">
            Wyślij ponownie email weryfikacyjny
        </x-primary-button>

        <button wire:click="logout" type="submit" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
            Wyloguj
        </button>
    </div>
</div>
