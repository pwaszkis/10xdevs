<?php

use App\Livewire\Actions\Logout;
use App\Services\AuthService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     * Uses AuthService for GDPR-compliant hard delete with cascade.
     */
    public function deleteUser(Logout $logout, AuthService $authService): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $user = Auth::user();

        // Use AuthService for proper GDPR-compliant deletion (hard delete with cascade)
        $authService->deleteAccount($user);

        $this->redirect('/');
    }
}; ?>

<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            Usuń konto
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Po usunięciu konta wszystkie Twoje dane i plany podróży zostaną trwale usunięte. Przed usunięciem konta pobierz wszystkie dane, które chcesz zachować.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Usuń konto</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6">

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Czy na pewno chcesz usunąć swoje konto?
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Po usunięciu konta wszystkie Twoje dane zostaną trwale usunięte. Wprowadź hasło, aby potwierdzić, że chcesz trwale usunąć swoje konto.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Hasło" class="sr-only" />

                <x-text-input
                    wire:model="password"
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Hasło"
                />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Anuluj
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Usuń konto
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
