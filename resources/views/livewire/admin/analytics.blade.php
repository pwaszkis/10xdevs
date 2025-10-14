<div>
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Analytics Dashboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Onboarding Metrics -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold mb-4">Onboarding</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Users</div>
                        <div class="text-3xl font-bold">{{ $onboarding['total_users'] }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Completed</div>
                        <div class="text-3xl font-bold">{{ $onboarding['completed_users'] }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Completion Rate</div>
                        <div class="text-3xl font-bold">{{ $onboarding['completion_rate'] }}%</div>
                    </div>
                </div>
            </div>

            <!-- Engagement Metrics -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold mb-4">User Engagement (30 days)</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                        <div class="text-sm text-gray-600 dark:text-gray-400">MAU</div>
                        <div class="text-3xl font-bold">{{ $engagement['monthly_active_users'] }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Plans Created</div>
                        <div class="text-3xl font-bold">{{ $engagement['plans_created'] }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                        <div class="text-sm text-gray-600 dark:text-gray-400">AI Generations</div>
                        <div class="text-3xl font-bold">{{ $engagement['ai_generations'] }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                        <div class="text-sm text-gray-600 dark:text-gray-400">PDF Exports</div>
                        <div class="text-3xl font-bold">{{ $engagement['pdf_exports'] }}</div>
                    </div>
                </div>
            </div>

            <!-- Events Distribution -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Event Distribution (30 days)</h3>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th class="text-left text-sm font-semibold text-gray-600 dark:text-gray-400 pb-2">Event Type</th>
                                <th class="text-right text-sm font-semibold text-gray-600 dark:text-gray-400 pb-2">Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($events as $event => $count)
                                <tr>
                                    <td class="py-2 text-gray-900 dark:text-gray-100">{{ $event }}</td>
                                    <td class="py-2 text-right text-gray-900 dark:text-gray-100">{{ $count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="py-4 text-center text-gray-500">No events yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
</div>
