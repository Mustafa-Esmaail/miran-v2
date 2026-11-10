{{-- The filter card above a report's table. Usage: <x-admin.report-filters>{{ $this->filtersForm }}</x-admin.report-filters> --}}
<div {{ $attributes->class(['fi-report-filters mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900']) }}>
    {{ $slot }}
</div>
