<div class="feedback-section bg-white rounded-lg shadow-md p-6 mb-6">
    @if($existingFeedback)
        {{-- Existing Feedback (Read-only) --}}
        <div class="existing-feedback">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Twoja ocena</h3>

            <div class="flex items-center gap-2 mb-3">
                @if($existingFeedback->satisfied)
                    <span class="text-3xl">👍</span>
                    <span class="text-green-700 font-medium">Pozytywna</span>
                @else
                    <span class="text-3xl">👎</span>
                    <span class="text-red-700 font-medium">Negatywna</span>
                @endif
            </div>

            @if($existingFeedback->issues && count($existingFeedback->issues) > 0)
                <div class="mb-3">
                    <p class="text-sm font-medium text-gray-700 mb-2">Zgłoszone problemy:</p>
                    <ul class="list-disc list-inside text-gray-600 space-y-1">
                        @foreach($existingFeedback->issues as $issue)
                            <li>{{ $this->getIssueLabel($issue) }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($existingFeedback->other_comment)
                <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">Komentarz:</p>
                    <p class="text-gray-600 bg-gray-50 rounded p-3">{{ $existingFeedback->other_comment }}</p>
                </div>
            @endif
        </div>
    @elseif($this->canSubmitFeedback())
        {{-- Feedback Form --}}
        <div x-data="{ showForm: @entangle('showForm') }">
            {{-- Toggle Button --}}
            <button
                @click="showForm = !showForm"
                x-show="!showForm"
                class="feedback-toggle w-full text-left font-medium text-blue-600 hover:text-blue-800 transition flex items-center justify-between"
            >
                <span class="text-lg">Oceń ten plan</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            {{-- Form Content --}}
            <div
                x-show="showForm"
                x-transition
                class="feedback-form space-y-4"
                style="display: none;"
            >
                <h3 class="text-lg font-semibold text-gray-900">
                    Czy plan spełnia Twoje oczekiwania?
                </h3>

                {{-- Satisfaction Buttons --}}
                <div class="satisfaction-buttons flex gap-4">
                    <button
                        wire:click="$set('satisfied', true)"
                        class="flex-1 py-3 px-4 rounded-lg border-2 transition"
                        :class="@js($satisfied === true) ? 'border-green-500 bg-green-50' : 'border-gray-300 hover:border-green-500'"
                    >
                        <span class="text-2xl">👍</span>
                        <span class="block mt-1 font-medium">Tak</span>
                    </button>
                    <button
                        wire:click="$set('satisfied', false)"
                        class="flex-1 py-3 px-4 rounded-lg border-2 transition"
                        :class="@js($satisfied === false) ? 'border-red-500 bg-red-50' : 'border-gray-300 hover:border-red-500'"
                    >
                        <span class="text-2xl">👎</span>
                        <span class="block mt-1 font-medium">Nie</span>
                    </button>
                </div>

                @error('satisfied')
                    <p class="text-red-600 text-sm">{{ $message }}</p>
                @enderror

                {{-- Issues Checkboxes (conditional) --}}
                @if($satisfied === false)
                    <div
                        class="issues-checkboxes space-y-2"
                        x-transition
                    >
                        <p class="text-sm font-medium text-gray-700 mb-2">
                            Co było nie tak? (wybierz wszystkie które pasują)
                        </p>

                        @foreach(['za_malo_szczegolow', 'nie_pasuje_do_preferencji', 'slaba_kolejnosc', 'inne'] as $issue)
                            <label class="flex items-center gap-2 text-gray-700 cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model.live="issues"
                                    value="{{ $issue }}"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                >
                                <span>{{ $this->getIssueLabel($issue) }}</span>
                            </label>
                        @endforeach

                        @error('issues')
                            <p class="text-red-600 text-sm">{{ $message }}</p>
                        @enderror

                        {{-- Other Comment Textarea --}}
                        @if(in_array('inne', $issues))
                            <div class="mt-3" x-transition>
                                <textarea
                                    wire:model.blur="otherComment"
                                    placeholder="Opisz problem..."
                                    maxlength="1000"
                                    rows="3"
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                ></textarea>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ strlen($otherComment ?? '') }}/1000 znaków
                                </p>
                                @error('otherComment')
                                    <p class="text-red-600 text-sm">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Form Actions --}}
                <div class="form-actions flex gap-4 pt-4">
                    <button
                        wire:click="submitFeedback"
                        wire:loading.attr="disabled"
                        class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition"
                    >
                        <span wire:loading.remove wire:target="submitFeedback">Wyślij feedback</span>
                        <span wire:loading wire:target="submitFeedback">Wysyłanie...</span>
                    </button>
                    <button
                        @click="showForm = false"
                        class="px-6 py-3 bg-gray-200 text-gray-800 font-medium rounded-lg hover:bg-gray-300 transition"
                    >
                        Anuluj
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
