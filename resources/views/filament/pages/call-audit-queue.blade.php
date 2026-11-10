<x-filament-panels::page>
    <x-admin.report-filters>
        {{ $this->filtersForm }}
    </x-admin.report-filters>

    @php
        $totals = $this->getQueueTotals();
        $totalsItems = [
            ['label' => __('admin.call_quality.queue.awaiting'), 'value' => number_format($totals['awaiting'])],
            ['label' => __('admin.call_quality.queue.employees_in_queue'), 'value' => number_format($totals['employees'])],
            ['label' => __('admin.call_quality.queue.my_evaluations_today'), 'value' => number_format($totals['my_evaluations_today'])],
        ];
    @endphp

    <x-admin.report-totals class="mb-4" :items="$totalsItems" />

    {{ $this->table }}
</x-filament-panels::page>
