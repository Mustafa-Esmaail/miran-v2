<x-filament-panels::page>
    <x-admin.report-filters>
        {{ $this->filtersForm }}
    </x-admin.report-filters>

    @php
        $totals = $this->getReportTotals();
        $avgRate = $totals['avg_rate'];
        $avgBadgeStyle = app(\App\Services\Settings\SettingsService::class)->completionBadgeStyle($avgRate);
        $totalsItems = [
            ['label' => __('admin.labels.total_marketing_sources'), 'value' => number_format($totals['sources_count'])],
            ['label' => __('admin.labels.service_requests_count'), 'value' => number_format($totals['requests_total'])],
            ['label' => __('admin.labels.paid_orders_count'), 'value' => number_format($totals['paid_total'])],
            [
                'label' => __('admin.labels.conversion_rate'),
                'value' => new \Illuminate\Support\HtmlString(sprintf(
                    '<span class="inline-flex w-fit items-center rounded-md px-2 py-1 text-sm font-semibold" style="%s">%s</span>',
                    e($avgBadgeStyle),
                    e($avgRate === null ? '—' : number_format($avgRate, 1).'%'),
                )),
            ],
        ];
    @endphp

    <x-admin.report-totals class="mb-4" :items="$totalsItems" />

    {{ $this->table }}
</x-filament-panels::page>
