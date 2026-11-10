<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('admin.dashboard_widgets.sales_targets_heading', ['period' => $period]) }}
        </x-slot>

        @if (empty($rows))
            <div class="flex flex-col items-center justify-center py-10 text-center">
                <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                    <x-heroicon-o-chart-bar class="h-6 w-6 text-gray-400" />
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('admin.dashboard_widgets.stats.no_targets_set') }}
                </p>
            </div>
        @else
            {{-- Per-person rows --}}
            <div class="space-y-5">
                @foreach ($rows as $row)
                    <div class="space-y-2.5">
                        {{-- Header: avatar + name + pct badge --}}
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-700 dark:bg-primary-500/20 dark:text-primary-300">
                                {{ $row['initials'] }}
                            </div>
                            <span class="flex-1 truncate text-sm font-medium text-gray-900 dark:text-white">
                                {{ $row['name'] }}
                            </span>
                            <span @class([
                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset',
                                'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-500/10 dark:text-success-400 dark:ring-success-500/20' => $row['revenueColor'] === 'success',
                                'bg-info-50 text-info-700 ring-info-600/20 dark:bg-info-500/10 dark:text-info-400 dark:ring-info-500/20' => $row['revenueColor'] === 'info',
                                'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-500/10 dark:text-warning-400 dark:ring-warning-500/20' => $row['revenueColor'] === 'warning',
                                'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-500/10 dark:text-danger-400 dark:ring-danger-500/20' => $row['revenueColor'] === 'danger',
                            ])>
                                {{ $row['revenuePct'] }}%
                            </span>
                        </div>

                        {{-- Revenue bar --}}
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                                <span>SAR {{ number_format($row['actualRevenue']) }}</span>
                                <span class="text-gray-400 dark:text-gray-500">/ SAR {{ number_format($row['targetRevenue']) }}</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                <div
                                    @class([
                                        'h-2 rounded-full transition-all duration-700',
                                        'bg-success-500' => $row['revenueColor'] === 'success',
                                        'bg-info-500'    => $row['revenueColor'] === 'info',
                                        'bg-warning-400' => $row['revenueColor'] === 'warning',
                                        'bg-danger-500'  => $row['revenueColor'] === 'danger',
                                    ])
                                    style="width: {{ $row['revenuePct'] }}%"
                                ></div>
                            </div>
                        </div>

                        {{-- Leads bar (only if target set) --}}
                        @if ($row['targetLeads'] > 0)
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span class="text-gray-400 dark:text-gray-500">Leads</span>
                                    <span>{{ $row['actualLeads'] }} / {{ $row['targetLeads'] }}</span>
                                </div>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                    <div
                                        class="h-1.5 rounded-full bg-primary-400 transition-all duration-700"
                                        style="width: {{ $row['leadsPct'] }}%"
                                    ></div>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if (! $loop->last)
                        <div class="border-t border-gray-100 dark:border-gray-800"></div>
                    @endif
                @endforeach
            </div>

            {{-- Footer: overall bar + commissions --}}
            <div class="mt-5 space-y-3 rounded-xl bg-gray-50 p-4 dark:bg-gray-800/60">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-medium text-gray-600 dark:text-gray-300">
                        {{ __('admin.dashboard_widgets.overall_progress') }}
                    </span>
                    <span class="font-bold text-primary-600 dark:text-primary-400">
                        {{ $overallPct }}%
                    </span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                    <div
                        class="h-2 rounded-full bg-gradient-to-r from-primary-500 to-primary-400 transition-all duration-700"
                        style="width: {{ $overallPct }}%"
                    ></div>
                </div>
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>SAR {{ number_format($totalActual) }} / SAR {{ number_format($totalTarget) }}</span>
                    @if ($pendingCommissions > 0)
                        <span class="inline-flex items-center gap-1 text-warning-600 dark:text-warning-400">
                            <x-heroicon-m-clock class="h-3 w-3" />
                            SAR {{ number_format($pendingCommissions) }} pending commissions
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
