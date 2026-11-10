<x-filament-panels::page>
    <x-admin.report-filters>
        {{ $this->filtersForm }}
    </x-admin.report-filters>

    {{ $this->table }}

    @php
        $totals = $this->getReportTotals();
        $avgRate = $totals['avg_rate'];
        $avgBadgeStyle = app(\App\Services\Settings\SettingsService::class)->completionBadgeStyle($avgRate);
        $totalsItems = [
            ['label' => __('admin.labels.total_courses'), 'value' => number_format($totals['courses_count'])],
            ['label' => __('admin.labels.service_requests_count'), 'value' => number_format($totals['requests_total'])],
            ['label' => __('admin.labels.paid_orders_count'), 'value' => number_format($totals['paid_total'])],
            ['label' => __('admin.labels.total_revenue'), 'value' => number_format($totals['paid_total_amount'], 2)],
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

    <x-admin.report-totals class="mt-4" :items="$totalsItems" />
</x-filament-panels::page>
