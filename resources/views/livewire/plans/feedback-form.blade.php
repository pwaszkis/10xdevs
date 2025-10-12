<div class="feedback-form" x-data="{ expanded: @entangle('isExpanded') }">
    {{-- Show submitted feedback if exists --}}
    @if($this->hasFeedback && !$isExpanded)
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h4 class="text-sm font-semibold text-gray-900 mb-2">
                        Twój feedback
                    </h4>
                    <div class="flex items-center space-x-2">
                        @if($this->existingFeedback->satisfied)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="mr-1.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Plan spełnia oczekiwania
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <svg class="mr-1.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                Plan nie spełnia oczekiwań
                            </span>
                        @endif
                    </div>

                    @if($this->existingFeedback->issues)
                        <div class="mt-3">
                            <p class="text-xs text-gray-500 mb-1">Zgłoszone problemy:</p>
                            <ul class="text-xs text-gray-700 space-y-1">
                                @foreach($this->existingFeedback->getFormattedIssues() as $issue)
                                    <li class="flex items-start">
                                        <span class="text-gray-400 mr-1">•</span>
                                        <span>{{ $issue }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <button
                    type="button"
                    @click="expanded = true"
                    class="ml-4 text-sm text-blue-600 hover:text-blue-800 font-medium"
                    aria-label="Edytuj feedback"
                >
                    Edytuj
                </button>
            </div>
        </div>
    @endif

    {{-- Collapsible form --}}
    @if(!$this->hasFeedback || $isExpanded)
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            {{-- Header --}}
            <button
                type="button"
                wire:click="toggle"
                @click="expanded = !expanded"
                class="w-full px-4 py-3 flex items-center justify-between text-left hover:bg-gray-50 transition-colors"
                aria-expanded="expanded"
                aria-controls="feedback-form-content"
            >
                <span class="text-sm font-medium text-gray-900">
                    {{ $this->hasFeedback ? 'Edytuj ocenę planu' : 'Oceń ten plan' }}
                </span>
                <svg
                    class="h-5 w-5 text-gray-400 transform transition-transform"
                    :class="{ 'rotate-180': expanded }"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            {{-- Form content --}}
            <div
                x-show="expanded"
                x-collapse
                id="feedback-form-content"
                class="border-t border-gray-200"
            >
                <form wire:submit.prevent="submit" class="p-4 space-y-4">
                    {{-- Success message --}}
                    @if(session('feedback-success'))
                        <div class="rounded-md bg-green-50 p-3">
                            <div class="flex">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <p class="ml-3 text-sm text-green-800">
                                    {{ session('feedback-success') }}
                                </p>
                            </div>
                        </div>
                    @endif

                    {{-- Error message --}}
                    @if(session('feedback-error'))
                        <div class="rounded-md bg-red-50 p-3">
                            <div class="flex">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <p class="ml-3 text-sm text-red-800">
                                    {{ session('feedback-error') }}
                                </p>
                            </div>
                        </div>
                    @endif

                    {{-- Question --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-3">
                            Czy plan spełnia Twoje oczekiwania?
                        </label>
                        <div class="flex space-x-3">
                            <button
                                type="button"
                                wire:click="setSatisfied(true)"
                                class="flex-1 py-2 px-4 rounded-md border text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                :class="{
                                    'bg-green-50 border-green-500 text-green-700': $wire.satisfied === true,
                                    'border-gray-300 text-gray-700 hover:bg-gray-50': $wire.satisfied !== true
                                }"
                            >
                                Tak
                            </button>
                            <button
                                type="button"
                                wire:click="setSatisfied(false)"
                                class="flex-1 py-2 px-4 rounded-md border text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                :class="{
                                    'bg-red-50 border-red-500 text-red-700': $wire.satisfied === false,
                                    'border-gray-300 text-gray-700 hover:bg-gray-50': $wire.satisfied !== false
                                }"
                            >
                                Nie
                            </button>
                        </div>
                        @error('satisfied')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Issues (conditional) --}}
                    <div x-show="$wire.satisfied === false" x-collapse>
                        <label class="block text-sm font-medium text-gray-900 mb-3">
                            Co było nie tak?
                        </label>
                        <div class="space-y-2">
                            @foreach($this->availableIssues as $issueKey => $issueLabel)
                                <label class="flex items-start cursor-pointer group">
                                    <input
                                        type="checkbox"
                                        wire:model="issues"
                                        value="{{ $issueKey }}"
                                        class="h-4 w-4 mt-0.5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    >
                                    <span class="ml-2 text-sm text-gray-700 group-hover:text-gray-900">
                                        {{ $issueLabel }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('issues')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror

                        {{-- Other comment (conditional) --}}
                        <div x-show="$wire.issues.includes('other')" x-collapse class="mt-3">
                            <label for="other-comment" class="block text-sm font-medium text-gray-700 mb-1">
                                Opisz problem (opcjonalnie)
                            </label>
                            <textarea
                                id="other-comment"
                                wire:model="otherComment"
                                rows="3"
                                maxlength="1000"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="Twoje uwagi..."
                            ></textarea>
                            @error('otherComment')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Submit button --}}
                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            :disabled="$wire.satisfied === null"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <span wire:loading.remove>Wyślij feedback</span>
                            <span wire:loading>
                                <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Wysyłanie...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
