@php
    use App\Support\Crm\LeadTimeline;

    $entries = LeadTimeline::for($getRecord());

    $dotClasses = [
        'primary' => 'bg-primary-50 text-primary-600 ring-primary-200 dark:bg-primary-500/15 dark:text-primary-400 dark:ring-primary-500/30',
        'success' => 'bg-success-50 text-success-600 ring-success-200 dark:bg-success-500/15 dark:text-success-400 dark:ring-success-500/30',
        'warning' => 'bg-warning-50 text-warning-600 ring-warning-200 dark:bg-warning-500/15 dark:text-warning-400 dark:ring-warning-500/30',
        'danger' => 'bg-danger-50 text-danger-600 ring-danger-200 dark:bg-danger-500/15 dark:text-danger-400 dark:ring-danger-500/30',
        'info' => 'bg-info-50 text-info-600 ring-info-200 dark:bg-info-500/15 dark:text-info-400 dark:ring-info-500/30',
        'gray' => 'bg-gray-50 text-gray-500 ring-gray-200 dark:bg-gray-500/15 dark:text-gray-400 dark:ring-gray-500/30',
    ];

    $sourceLabels = [
        LeadTimeline::SOURCE_LOG => __('admin.lead_view.source_log'),
        LeadTimeline::SOURCE_CRM_ACTIVITY => __('admin.lead_view.source_crm_activity'),
        LeadTimeline::SOURCE_CRM_TASK => __('admin.lead_view.source_crm_task'),
        LeadTimeline::SOURCE_CALL_EVALUATION => __('admin.lead_view.source_call_evaluation'),
    ];
@endphp

<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @if ($entries->isEmpty())
        <div class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-gray-300 px-6 py-10 text-center dark:border-white/10">
            <span class="flex size-10 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500">
                <x-filament::icon icon="heroicon-o-clock" class="size-5" />
            </span>
            <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('admin.lead_view.timeline_empty_heading') }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.lead_view.timeline_empty_description') }}</p>
        </div>
    @else
        <ol class="relative flex flex-col gap-5 ps-1" data-lead-timeline>
            @foreach ($entries as $item)
                <li wire:key="lead-timeline-{{ $item['key'] }}" class="relative flex gap-3" data-timeline-entry="{{ $item['key'] }}">
                    @unless ($loop->last)
                        <span aria-hidden="true" class="absolute top-9 -bottom-5 start-4 w-px -translate-x-1/2 rtl:translate-x-1/2 bg-gray-200 dark:bg-white/10"></span>
                    @endunless

                    <span class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full ring-1 {{ $dotClasses[$item['color']] ?? $dotClasses['gray'] }}">
                        <x-filament::icon :icon="$item['icon']" class="size-4" />
                    </span>

                    <div class="min-w-0 flex-1 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-white/10 dark:bg-white/5">
                        <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-1">
                            <div class="flex min-w-0 flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $item['title'] }}</p>

                                @foreach ($item['badges'] as $badge)
                                    <x-filament::badge size="sm" :color="$item['color']">{{ $badge }}</x-filament::badge>
                                @endforeach

                                @if ($item['rating'])
                                    <x-filament::badge size="sm" icon="heroicon-m-star" :color="$item['rating']['color']" data-timeline-rating>{{ $item['rating']['label'] }}</x-filament::badge>
                                @endif
                            </div>

                            @if ($item['at'])
                                <time
                                    datetime="{{ $item['at']->toIso8601String() }}"
                                    title="{{ $item['at']->format('Y-m-d H:i') }}"
                                    class="shrink-0 text-xs text-gray-500 dark:text-gray-400"
                                >
                                    {{ $item['at']->diffForHumans() }}
                                    <span class="text-gray-400 dark:text-gray-500" dir="ltr">· {{ $item['at']->format('Y-m-d H:i') }}</span>
                                </time>
                            @endif
                        </div>

                        @if ($item['from'] !== null || $item['to'] !== null)
                            <p class="mt-1 flex flex-wrap items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300">
                                @if ($item['from'] !== null)
                                    <span class="rounded-md bg-gray-100 px-1.5 py-0.5 dark:bg-white/10">{{ $item['from'] }}</span>
                                    <x-filament::icon icon="heroicon-m-arrow-long-right" class="size-4 text-gray-400 rtl:rotate-180" />
                                @endif
                                @if ($item['to'] !== null)
                                    <span class="rounded-md bg-primary-50 px-1.5 py-0.5 font-medium text-primary-700 dark:bg-primary-500/15 dark:text-primary-300">{{ $item['to'] }}</span>
                                @endif
                            </p>
                        @endif

                        @if ($item['detail'])
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $item['detail'] }}</p>
                        @endif

                        @if ($item['body'])
                            <p class="mt-2 whitespace-pre-line border-s-2 border-gray-200 ps-3 text-sm text-gray-700 dark:border-white/10 dark:text-gray-200">{{ $item['body'] }}</p>
                        @endif

                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                            <span class="inline-flex items-center gap-1">
                                <x-filament::icon icon="heroicon-m-tag" class="size-3.5" />
                                {{ $sourceLabels[$item['source']] ?? $item['source'] }}
                            </span>
                            @if ($item['actor'])
                                <span class="inline-flex items-center gap-1">
                                    <x-filament::icon icon="heroicon-m-user-circle" class="size-3.5" />
                                    {{ $item['actor'] }}
                                </span>
                            @endif
                            @if ($item['url'])
                                <a href="{{ $item['url'] }}" class="inline-flex items-center gap-1 font-medium text-primary-600 hover:underline dark:text-primary-400">
                                    <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="size-3.5" />
                                    {{ __('admin.call_quality.lead.open_evaluation') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</x-dynamic-component>
