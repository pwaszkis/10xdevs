<div>
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Cześć {{ $this->userNickname }}! 👋
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Zaplanuj swoją kolejną przygodę
                </p>
            </div>
            <div class="flex-shrink-0">
                <a href="{{ route('plans.create') }}"
                   wire:navigate
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Stwórz nowy plan
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if($this->hasPlans)
            {{-- Quick Filters --}}
            <div class="px-4 sm:px-0 mb-6">
                <div class="flex flex-wrap gap-2">
                    <button wire:click="setFilter('all')" type="button"
                            class="inline-flex items-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150 {{ $statusFilter === 'all' ? 'bg-gray-800 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                        Wszystkie
                        @if($this->planCounts['all'] > 0)
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full {{ $statusFilter === 'all' ? 'bg-gray-700' : 'bg-gray-200' }}">
                                {{ $this->planCounts['all'] }}
                            </span>
                        @endif
                    </button>

                    <button wire:click="setFilter('draft')" type="button"
                            class="inline-flex items-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150 {{ $statusFilter === 'draft' ? 'bg-gray-800 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                        Szkice
                        @if($this->planCounts['draft'] > 0)
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full {{ $statusFilter === 'draft' ? 'bg-gray-700' : 'bg-gray-200' }}">
                                {{ $this->planCounts['draft'] }}
                            </span>
                        @endif
                    </button>

                    <button wire:click="setFilter('planned')" type="button"
                            class="inline-flex items-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150 {{ $statusFilter === 'planned' ? 'bg-gray-800 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                        Zaplanowane
                        @if($this->planCounts['planned'] > 0)
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full {{ $statusFilter === 'planned' ? 'bg-gray-700' : 'bg-gray-200' }}">
                                {{ $this->planCounts['planned'] }}
                            </span>
                        @endif
                    </button>

                    <button wire:click="setFilter('completed')" type="button"
                            class="inline-flex items-center px-4 py-2 rounded-md font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150 {{ $statusFilter === 'completed' ? 'bg-gray-800 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}">
                        Zrealizowane
                        @if($this->planCounts['completed'] > 0)
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full {{ $statusFilter === 'completed' ? 'bg-gray-700' : 'bg-gray-200' }}">
                                {{ $this->planCounts['completed'] }}
                            </span>
                        @endif
                    </button>
                </div>
            </div>

            {{-- Plans Grid --}}
            @if($this->plans->count() > 0)
                <div class="px-4 sm:px-0">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-8">
                        @foreach($this->plans as $plan)
                            <livewire:components.travel-plan-card :plan="$plan" wire:key="plan-{{ $plan->id }}" />
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-6">
                        {{ $this->plans->links() }}
                    </div>
                </div>
            @else
                {{-- Empty State for Filter --}}
                <div class="px-4 sm:px-0">
                <div class="bg-white rounded-lg shadow-sm p-12 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">
                        Brak planów w tej kategorii
                    </h3>
                    <p class="mt-2 text-gray-600">
                        Nie znaleziono planów podróży pasujących do wybranego filtru.
                    </p>
                    <button wire:click="clearFilters" type="button"
                            class="mt-6 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Wyczyść filtry
                    </button>
                </div>
                </div>
            @endif
        @else
            {{-- Empty State - No Plans --}}
            <div class="px-4 sm:px-0">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                    <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-indigo-100">
                        <svg class="h-12 w-12 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="mt-6 text-xl font-semibold text-gray-900">
                        Witaj w VibeTravels!
                    </h3>
                    <p class="mt-3 text-gray-600 max-w-md mx-auto">
                        Nie masz jeszcze żadnych planów podróży. Zacznij planować swoją pierwszą przygodę już teraz!
                    </p>
                    <div class="mt-8">
                        <a href="{{ route('plans.create') }}"
                           wire:navigate
                           class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Stwórz pierwszy plan
                        </a>
                    </div>
                </div>
            </div>
        @endif
        </div>
    </div>
</x-app-layout>
</div>
