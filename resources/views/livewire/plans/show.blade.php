<div class="plan-details-container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    {{-- Breadcrumbs --}}
    <x-breadcrumbs :items="[
        ['label' => 'Plans', 'url' => route('dashboard')],
        ['label' => $plan->title, 'url' => '']
    ]" />

    {{-- Draft CTA - tylko dla szkiców --}}
    @if($plan->isDraft())
        {{-- Header planu (prosty HTML dla szkicu) --}}
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $plan->title }}</h1>
            <p class="text-gray-600">{{ $plan->destination }} • {{ $plan->number_of_days }} dni • {{ $plan->number_of_people }} {{ $plan->number_of_people == 1 ? 'osoba' : 'osoby' }}</p>
            <span class="inline-block mt-3 px-3 py-1 bg-gray-100 text-gray-800 text-sm rounded-full">Szkic</span>
        </div>
        <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-6 mb-6 text-center">
            <h2 class="text-2xl font-bold text-gray-900 mb-3">
                Gotowy do wygenerowania planu?
            </h2>
            <p class="text-gray-700 mb-6">
                Wykorzystaj AI do stworzenia szczegółowego harmonogramu Twojej podróży.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button
                    wire:click="regeneratePlan"
                    class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition"
                >
                    🤖 Generuj plan
                </button>
                <button
                    wire:click="deletePlan"
                    class="px-6 py-3 bg-gray-200 text-gray-800 font-medium rounded-lg hover:bg-gray-300 transition"
                >
                    🗑️ Usuń szkic
                </button>
            </div>
        </div>
    @else
        {{-- Header planu i sekcja założeń (z komponentami Livewire dla wygenerowanych planów) --}}
        <livewire:components.plan-header :plan="$plan" wire:key="plan-header-{{ $plan->id }}" />

        <livewire:components.assumptions-section
            :userNotes="$plan->user_notes"
            :preferences="auth()->check() ? (auth()->user()->preferences?->toArray() ?? []) : []"
            wire:key="assumptions-{{ $plan->id }}"
        />
    @endif

    {{-- Dni planu (tylko dla generated plans) --}}
    @if($plan->status !== 'draft' && $plan->days->count() > 0)
        <livewire:components.plan-days-list
            :days="$plan->days"
            wire:key="plan-days-list-{{ $plan->id }}"
        />
    @endif

    {{-- Footer z akcjami --}}
    <div class="plan-footer space-y-6">
        @if($plan->status !== 'draft')
            <livewire:components.feedback-form
                :travelPlanId="$plan->id"
                :existingFeedback="$feedback"
                wire:key="feedback-{{ $plan->id }}-{{ $feedback ? $feedback->id : 'new' }}"
            />
        @endif

        <livewire:components.plan-actions
            :status="$plan->status"
            :aiGenerationsRemaining="$aiGenerationsRemaining"
            :hasAiPlan="$plan->has_ai_plan"
            :travelPlanId="$plan->id"
            wire:key="actions-{{ $plan->id }}-{{ $aiGenerationsRemaining }}"
        />
    </div>

    {{-- Modal - Delete Confirmation --}}
    @if($showDeleteModal)
        <div
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            x-data="{ show: @entangle('showDeleteModal') }"
            x-show="show"
            x-transition
            @click.self="show = false"
        >
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">
                    Usunąć plan?
                </h2>
                <p class="text-gray-700 mb-2">
                    Czy na pewno chcesz usunąć plan <strong>{{ $plan->title }}</strong>?
                </p>
                <p class="text-red-600 font-medium mb-6">
                    Ta operacja jest nieodwracalna.
                </p>

                <div class="flex gap-4">
                    <button
                        wire:click="confirmDelete"
                        class="flex-1 px-6 py-3 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition"
                    >
                        Tak, usuń plan
                    </button>
                    <button
                        wire:click="$set('showDeleteModal', false)"
                        class="flex-1 px-6 py-3 bg-gray-200 text-gray-800 font-medium rounded-lg hover:bg-gray-300 transition"
                    >
                        Anuluj
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal - Regenerate Confirmation --}}
    @if($showRegenerateModal)
        <div
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            x-data="{ show: @entangle('showRegenerateModal') }"
            x-show="show"
            x-transition
            @click.self="show = false"
        >
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">
                    Regeneracja planu
                </h2>
                <p class="text-gray-700 mb-2">
                    Spowoduje to wygenerowanie nowego planu
                    ({{ $aiGenerationsRemaining }}/{{ $aiGenerationsLimit }} w tym miesiącu).
                </p>
                <p class="text-orange-600 font-medium mb-6">
                    Poprzedni plan zostanie nadpisany.
                </p>
                <p class="text-gray-600 mb-6">
                    Czy chcesz kontynuować?
                </p>

                <div class="flex gap-4">
                    <button
                        wire:click="confirmRegenerate"
                        class="flex-1 px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition"
                    >
                        Tak, regeneruj
                    </button>
                    <button
                        wire:click="$set('showRegenerateModal', false)"
                        class="flex-1 px-6 py-3 bg-gray-200 text-gray-800 font-medium rounded-lg hover:bg-gray-300 transition"
                    >
                        Anuluj
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Generation Progress Overlay --}}
    @if($isGenerating)
        <div
            wire:poll.3s="checkGenerationStatus"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
            <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4 text-center">
                <div class="w-16 h-16 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">
                    Generowanie planu...
                </h3>
                <p class="text-3xl font-bold text-blue-600 mb-4">
                    {{ $generationProgress }}%
                </p>
                <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden mb-4">
                    <div
                        class="h-full bg-blue-600 transition-all duration-300"
                        style="width: {{ $generationProgress }}%"
                    ></div>
                </div>
                <p class="text-sm text-gray-600">
                    To może potrwać 30-60 sekund. Nie zamykaj tej strony.
                </p>
            </div>
        </div>
    @endif

    {{-- Flash Messages --}}
    @if(session()->has('success'))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-transition
            x-init="setTimeout(() => show = false, 5000)"
            class="fixed bottom-4 right-4 bg-green-100 border-2 border-green-500 text-green-800 px-6 py-4 rounded-lg shadow-lg z-50 max-w-md"
        >
            <div class="flex items-center gap-3">
                <span class="text-2xl">✅</span>
                <p class="font-medium">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session()->has('error'))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-transition
            x-init="setTimeout(() => show = false, 5000)"
            class="fixed bottom-4 right-4 bg-red-100 border-2 border-red-500 text-red-800 px-6 py-4 rounded-lg shadow-lg z-50 max-w-md"
        >
            <div class="flex items-center gap-3">
                <span class="text-2xl">❌</span>
                <p class="font-medium">{{ session('error') }}</p>
            </div>
        </div>
    @endif
</div>
