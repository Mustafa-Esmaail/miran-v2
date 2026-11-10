@php
    /** @var array{counts: array<string, int>, problems: list<array{row: int, status: string, name: string|null, phone: string|null, reason: string|null}>, more_problems: int}|null $preview */
    $counts = $preview['counts'] ?? null;
    $problems = $preview['problems'] ?? [];
    $statusColors = [
        'duplicate_in_file' => 'warning',
        'duplicate_existing' => 'warning',
        'error' => 'danger',
    ];
    $cards = [
        ['key' => 'total', 'color' => 'text-gray-950 dark:text-white'],
        ['key' => 'new', 'color' => 'text-success-600 dark:text-success-400'],
        ['key' => 'duplicates', 'color' => 'text-warning-600 dark:text-warning-400'],
        ['key' => 'errors', 'color' => 'text-danger-600 dark:text-danger-400'],
    ];
@endphp

<div class="flex flex-col gap-4">
    @if ($counts === null)
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.lead_import.preview.unavailable') }}</p>
    @else
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ($cards as $card)
                <div wire:key="lead-import-count-{{ $card['key'] }}" class="rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-white/5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('admin.lead_import.counts.'.$card['key']) }}</p>
                    <p class="mt-1 text-2xl font-semibold {{ $card['color'] }}">{{ number_format($counts[$card['key']] ?? 0) }}</p>
                </div>
            @endforeach
        </div>

        @if (($counts['duplicates'] ?? 0) > 0)
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ __('admin.lead_import.preview.duplicates_breakdown', ['in_file' => $counts['duplicates_in_file'] ?? 0, 'existing' => $counts['duplicates_existing'] ?? 0]) }}
            </p>
        @endif

        @if ($problems === [])
            <p class="text-sm text-success-600 dark:text-success-400">{{ __('admin.lead_import.preview.no_problems') }}</p>
        @else
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
                <table class="w-full divide-y divide-gray-200 text-start text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-3 py-2 text-start font-semibold text-gray-950 dark:text-white">{{ __('admin.lead_import.preview.row') }}</th>
                            <th class="px-3 py-2 text-start font-semibold text-gray-950 dark:text-white">{{ __('admin.lead_import.preview.name') }}</th>
                            <th class="px-3 py-2 text-start font-semibold text-gray-950 dark:text-white">{{ __('admin.lead_import.preview.phone') }}</th>
                            <th class="px-3 py-2 text-start font-semibold text-gray-950 dark:text-white">{{ __('admin.lead_import.preview.status') }}</th>
                            <th class="px-3 py-2 text-start font-semibold text-gray-950 dark:text-white">{{ __('admin.lead_import.preview.reason') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($problems as $problem)
                            <tr wire:key="lead-import-problem-{{ $problem['row'] }}">
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $problem['row'] }}</td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $problem['name'] ?? '—' }}</td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-200" dir="ltr">{{ $problem['phone'] ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    <x-filament::badge :color="$statusColors[$problem['status']] ?? 'gray'">
                                        {{ __('admin.lead_import.statuses.'.$problem['status']) }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $problem['reason'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (($preview['more_problems'] ?? 0) > 0)
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.lead_import.preview.more_problems', ['count' => $preview['more_problems']]) }}</p>
            @endif
        @endif
    @endif
</div>
