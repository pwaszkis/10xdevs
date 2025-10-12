{{-- Example of how to use FeedbackForm component in plan details view --}}
@extends('layouts.app')

@section('title', $plan->title)

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Plan Header --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $plan->title }}</h1>
                <p class="text-lg text-gray-600 mt-1">{{ $plan->destination }}</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                {{ ucfirst($plan->status) }}
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6">
            <div class="flex items-center text-gray-700">
                <svg class="h-5 w-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="text-sm">
                    {{ $plan->start_date->format('d.m.Y') }} - {{ $plan->end_date->format('d.m.Y') }}
                </span>
            </div>
            <div class="flex items-center text-gray-700">
                <svg class="h-5 w-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm">{{ $plan->number_of_days }} dni</span>
            </div>
            <div class="flex items-center text-gray-700">
                <svg class="h-5 w-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span class="text-sm">{{ $plan->number_of_people }} {{ $plan->number_of_people === 1 ? 'osoba' : 'osób' }}</span>
            </div>
        </div>
    </div>

    {{-- Plan Content --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        {{-- Your plan days content here --}}
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Plan zwiedzania</h2>

        @if($plan->has_ai_plan)
            {{-- Display plan days here --}}
            <div class="space-y-4">
                @foreach($plan->days as $day)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900">
                            Dzień {{ $day->day_number }} - {{ $day->date->format('d.m.Y') }}
                        </h3>
                        {{-- Day content --}}
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <p>Brak wygenerowanego planu</p>
            </div>
        @endif
    </div>

    {{-- Feedback Section (Footer) --}}
    @if($plan->has_ai_plan)
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Feedback</h2>

            {{-- Livewire FeedbackForm Component --}}
            <livewire:plans.feedback-form :plan="$plan" :key="'feedback-'.$plan->id" />
        </div>
    @endif

    {{-- Actions Footer --}}
    <div class="flex flex-col sm:flex-row items-center justify-between space-y-3 sm:space-y-0 sm:space-x-4">
        <a
            href="{{ route('dashboard') }}"
            class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900"
        >
            <svg class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Wróć do Dashboard
        </a>

        <div class="flex space-x-3">
            @if($plan->has_ai_plan)
                <button
                    type="button"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <svg class="h-5 w-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                    </svg>
                    Export do PDF
                </button>

                <button
                    type="button"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Regeneruj plan
                </button>
            @endif

            <button
                type="button"
                class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
            >
                <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Usuń plan
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Listen for feedback events
    document.addEventListener('livewire:init', () => {
        Livewire.on('feedback-submitted', (event) => {
            console.log('Feedback submitted:', event);

            // Optional: Show additional UI feedback
            // Could trigger confetti, update analytics, etc.
        });
    });
</script>
@endpush
@endsection
