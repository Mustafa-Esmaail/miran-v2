<x-filament-panels::page>
    <x-admin.report-filters>
        {{ $this->filtersForm }}
    </x-admin.report-filters>

    @php
        $totals = $this->getReportTotals();
        $totalsItems = [
            ['label' => __('admin.landing_versions.labels.versions_count'), 'value' => number_format($totals['versions_count'])],
            ['label' => __('admin.landing_versions.labels.leads_count'), 'value' => number_format($totals['leads_total'])],
        ];
    @endphp

    <x-admin.report-totals class="mb-4" :items="$totalsItems" />

    {{ $this->table }}
</x-filament-panels::page>
