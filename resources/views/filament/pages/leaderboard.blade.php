<x-filament-panels::page>
    <div class="space-y-6">
        @if ($this->previewResult)
            <x-filament::section
                heading="Prize Assignment Preview"
                description="Preview mode only. No assignments are written until the confirm action runs."
                icon="heroicon-o-eye"
            >
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-xl border border-warning-200/70 bg-warning-50/70 p-4 dark:border-warning-500/20 dark:bg-warning-500/10">
                        <p class="text-xs font-medium uppercase tracking-wide text-warning-700 dark:text-warning-300">Processed</p>
                        <p class="mt-2 text-2xl font-semibold text-warning-950 dark:text-white">{{ $this->previewResult['processed_count'] }}</p>
                    </div>

                    <div class="rounded-xl border border-success-200/70 bg-success-50/70 p-4 dark:border-success-500/20 dark:bg-success-500/10">
                        <p class="text-xs font-medium uppercase tracking-wide text-success-700 dark:text-success-300">Ready</p>
                        <p class="mt-2 text-2xl font-semibold text-success-950 dark:text-white">{{ $this->previewResult['ready_count'] }}</p>
                    </div>

                    <div class="rounded-xl border border-gray-200/70 bg-gray-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-600 dark:text-gray-400">Warnings</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $this->previewResult['skipped_count'] }}</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                        <thead class="bg-gray-50/80 dark:bg-white/5">
                            <tr class="text-left text-gray-700 dark:text-gray-300">
                                <th class="px-4 py-3 font-medium">User</th>
                                <th class="px-4 py-3 font-medium">Rank</th>
                                <th class="px-4 py-3 font-medium">Prize</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 font-medium">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @foreach ($this->previewResult['entries'] as $entry)
                                <tr class="align-top">
                                    <td class="px-4 py-3 text-gray-950 dark:text-white">
                                        <span class="font-medium">#{{ $entry['user_id'] }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-filament::badge
                                            :color="$entry['rank'] <= 3 ? 'warning' : 'primary'"
                                            :icon="$entry['rank'] <= 3 ? 'heroicon-o-trophy' : null"
                                        >
                                            {{ $entry['rank'] }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                        {{ $entry['prize_title'] ?? 'No matching prize' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-filament::badge
                                            :color="$this->getPreviewEntryStatusColor($entry['status'])"
                                        >
                                            {{ \Illuminate\Support\Str::headline($entry['status']) }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                        {{ $entry['reason'] ?? $entry['warning'] ?? 'Ready for assignment' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        @if ($this->assignmentResult)
            <x-filament::section
                heading="Last Auto-Assignment Run"
                description="Summary of the most recent confirmed assignment action on the current leaderboard snapshot."
                icon="heroicon-o-gift"
            >
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-xl border border-gray-200/70 bg-gray-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-600 dark:text-gray-400">Processed</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $this->assignmentResult['processed_count'] }}</p>
                    </div>

                    <div class="rounded-xl border border-success-200/70 bg-success-50/70 p-4 dark:border-success-500/20 dark:bg-success-500/10">
                        <p class="text-xs font-medium uppercase tracking-wide text-success-700 dark:text-success-300">Assigned</p>
                        <p class="mt-2 text-2xl font-semibold text-success-950 dark:text-white">{{ $this->assignmentResult['assigned_count'] }}</p>
                    </div>

                    <div class="rounded-xl border border-gray-200/70 bg-gray-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-600 dark:text-gray-400">Skipped</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $this->assignmentResult['skipped_count'] }}</p>
                    </div>
                </div>
            </x-filament::section>
        @endif

        <x-filament::section
            heading="Current Top 15"
            description="Admin view with full email visibility, rank, score, and current prize assignment state."
            icon="heroicon-o-rectangle-stack"
        >
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
