<x-filament-widgets::widget>
    <x-filament::section
        :heading="__('admin.kpi_dashboard.top_courses.heading')"
        :description="__('admin.kpi_dashboard.top_courses.description')"
    >
        <div class="overflow-x-auto rounded-xl border" style="border-color: var(--kpi-border)">
            <table class="w-full text-sm">
                <thead class="kpi-head">
                    <tr class="text-start text-xs font-bold">
                        <th class="px-3 py-2.5 text-start">{{ __('admin.kpi_dashboard.top_courses.course') }}</th>
                        <th class="px-3 py-2.5 text-start">{{ __('admin.kpi_dashboard.top_courses.orders') }}</th>
                        <th class="px-3 py-2.5 text-start">{{ __('admin.kpi_dashboard.top_courses.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($courses as $course)
                        <tr wire:key="kpi-course-{{ $loop->index }}" class="border-t" style="border-color: var(--kpi-border)">
                            <td class="px-3 py-3 font-medium">{{ $course['title'] }}</td>
                            <td class="px-3 py-3 font-semibold">{{ number_format($course['orders_count']) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-16 font-semibold tabular-nums">{{ number_format($course['total']) }}</span>
                                    <span class="kpi-fill inline-block h-1.5 rounded-full" style="width: {{ max(4, round($course['total'] / $maxTotal * 112)) }}px"></span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="kpi-muted px-3 py-8 text-center">
                                {{ __('admin.kpi_dashboard.top_courses.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
