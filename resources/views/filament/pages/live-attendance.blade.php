<x-filament-panels::page>

    <div class="flex items-center justify-end">
        <button wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh"
            class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
            <x-heroicon-o-arrow-path class="h-4 w-4" wire:loading.class="animate-spin" wire:target="refresh" />
            {{ __('admin.labels.refresh') }}
        </button>
    </div>

    @php $rows = $this->getLiveSessions(); @endphp

    @if ($rows->isEmpty())
        <div class="rounded-xl border border-gray-200 bg-white p-12 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <x-heroicon-o-signal class="mx-auto h-12 w-12 text-gray-400" />
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.labels.no_live_sessions') }}</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            @foreach ($rows as $row)
                @php $session = $row['session']; @endphp
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                {{ $row['course_title'] }}
                            </p>
                            <h3 class="mt-0.5 text-base font-semibold text-gray-900 dark:text-white">
                                {{ $session->title ?: $session->start_at->format('H:i') }}
                            </h3>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                {{ $session->start_at->format('Y-m-d H:i') }}
                            </p>
                            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                @if ($row['meeting_started_at'])
                                    {{ __('admin.labels.meeting_started_at', ['time' => $row['meeting_started_at']->format('H:i')]) }}
                                @else
                                    {{ __('admin.labels.meeting_not_started') }}
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-1.5">
                            @if ($row['meeting_ended_at'])
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    {{ __('admin.labels.meeting_ended_at', ['time' => $row['meeting_ended_at']->format('H:i')]) }}
                                </span>
                            @endif
                            @if ($row['recording_url'])
                                <a href="{{ $row['recording_url'] }}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap text-primary-700 hover:bg-primary-100 dark:bg-primary-900/30 dark:text-primary-400 dark:hover:bg-primary-900/60">
                                    <x-heroicon-o-play-circle class="h-3.5 w-3.5" />
                                    {{ __('admin.labels.view_recording') }}
                                </a>
                            @endif
                            <span @class([
                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap',
                                'bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200' => $row['instructor_status']['color'] === 'success',
                                'bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-200' => $row['instructor_status']['color'] === 'warning',
                                'bg-danger-100 text-danger-800 dark:bg-danger-900 dark:text-danger-200' => $row['instructor_status']['color'] === 'danger',
                            ])>
                                {{ __('admin.labels.instructor_status') }}: {{ $row['instructor_status']['label'] }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('admin.labels.enrolled_joined') }}:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">
                                {{ $row['joined_count'] }} / {{ $row['enrolled_count'] }}
                            </span>
                        </div>
                    </div>

                    @if ($row['attendees']->isNotEmpty())
                        <x-admin.collapsible-list
                            :label="__('admin.labels.attendees')"
                            :items="$row['attendees']"
                            color="success"
                        />
                    @endif

                    @if ($row['waiting']->isNotEmpty())
                        <x-admin.collapsible-list
                            :label="__('admin.labels.waiting_for_host')"
                            :items="$row['waiting']"
                            color="warning"
                        />
                    @endif

                    @if ($row['joined_before_host']->isNotEmpty())
                        <x-admin.collapsible-list
                            :label="__('admin.labels.joined_before_host')"
                            :items="$row['joined_before_host']"
                            color="primary"
                        />
                    @endif

                    @if ($row['not_joined']->isNotEmpty())
                        <x-admin.collapsible-list
                            :label="__('admin.labels.not_joined_yet')"
                            :items="$row['not_joined']"
                            color="danger"
                        />
                    @endif
                </div>
            @endforeach
        </div>
    @endif

</x-filament-panels::page>
