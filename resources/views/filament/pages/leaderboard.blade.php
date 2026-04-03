<x-filament-panels::page>
    @php
        $rows = $this->getRows();
        $previewResult = $this->previewResult;
        $assignmentResult = $this->assignmentResult;
    @endphp

    <div class="space-y-6">
        @if ($previewResult)
            <div class="rounded-xl border border-warning-200 bg-warning-50 p-4">
                <h2 class="text-sm font-semibold text-warning-900">Preview Summary</h2>
                <p class="mt-2 text-sm text-warning-800">
                    Processed: {{ $previewResult['processed_count'] }},
                    Ready: {{ $previewResult['ready_count'] }},
                    Warnings: {{ $previewResult['skipped_count'] }}
                </p>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-warning-900">
                                <th class="px-2 py-1">User ID</th>
                                <th class="px-2 py-1">Rank</th>
                                <th class="px-2 py-1">Prize</th>
                                <th class="px-2 py-1">Status</th>
                                <th class="px-2 py-1">Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($previewResult['entries'] as $entry)
                                <tr class="border-t border-warning-200">
                                    <td class="px-2 py-1">{{ $entry['user_id'] }}</td>
                                    <td class="px-2 py-1">{{ $entry['rank'] }}</td>
                                    <td class="px-2 py-1">{{ $entry['prize_title'] ?? 'None' }}</td>
                                    <td class="px-2 py-1">{{ $entry['status'] }}</td>
                                    <td class="px-2 py-1">{{ $entry['reason'] ?? $entry['warning'] ?? 'Ready' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($assignmentResult)
            <div class="rounded-xl border border-success-200 bg-success-50 p-4">
                <h2 class="text-sm font-semibold text-success-900">Auto-Assignment Summary</h2>
                <p class="mt-2 text-sm text-success-800">
                    Processed: {{ $assignmentResult['processed_count'] }},
                    Assigned: {{ $assignmentResult['assigned_count'] }},
                    Skipped: {{ $assignmentResult['skipped_count'] }}
                </p>
            </div>
        @endif

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-700">
                        <th class="px-4 py-3">Rank</th>
                        <th class="px-4 py-3">User ID</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Best Score</th>
                        <th class="px-4 py-3">Prize Assignment Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($rows as $row)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $row['rank'] }}</td>
                            <td class="px-4 py-3">{{ $row['user_id'] }}</td>
                            <td class="px-4 py-3">{{ $row['email'] }}</td>
                            <td class="px-4 py-3">{{ $row['best_score'] }}</td>
                            <td class="px-4 py-3">{{ $row['prize_status'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
