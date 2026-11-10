<x-filament-panels::page>

    {{-- Filters --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">

            {{-- Course --}}
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.labels.course') }}
                </label>
                <div wire:key="course-select">
                    <x-admin.searchable-select
                        :options="$this->getCourseOptions()"
                        wire-model="selectedCourseId"
                        :current-value="$selectedCourseId"
                        :placeholder="'— ' . __('admin.labels.select') . ' —'"
                    />
                </div>
            </div>

            {{-- Group --}}
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.labels.group') }}
                </label>
                <div wire:key="group-select-{{ $selectedCourseId }}">
                    <x-admin.searchable-select
                        :options="$this->getCourseGroupOptions()"
                        wire-model="selectedCourseGroupId"
                        :current-value="$selectedCourseGroupId"
                        :placeholder="'— ' . __('admin.labels.select') . ' —'"
                        :disabled="!$selectedCourseId"
                    />
                </div>
            </div>

            {{-- Day (CourseCurriculumSection) --}}
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.labels.course_day') }}
                    <span class="text-xs text-gray-400">({{ __('admin.labels.optional') }})</span>
                </label>
                <div wire:key="section-select-{{ $selectedCourseGroupId }}">
                    <x-admin.searchable-select
                        :options="$this->getSectionOptions()"
                        wire-model="selectedSectionId"
                        :current-value="$selectedSectionId"
                        :placeholder="'— ' . __('admin.labels.all_days') . ' —'"
                        :disabled="!$selectedCourseGroupId"
                    />
                </div>
            </div>

            {{-- Session --}}
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.labels.session_day') }}
                    <span class="text-xs text-gray-400">({{ __('admin.labels.optional') }})</span>
                </label>
                <div wire:key="session-select-{{ $selectedCourseGroupId }}-{{ $selectedSectionId }}">
                    <x-admin.searchable-select
                        :options="$this->getSessionOptions()"
                        wire-model="selectedSessionId"
                        :current-value="$selectedSessionId"
                        :placeholder="'— ' . __('admin.labels.all_sessions') . ' —'"
                        :disabled="!$selectedCourseGroupId"
                    />
                </div>
            </div>

            {{-- Threshold --}}
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.labels.certificate_threshold') }} (%)
                </label>
                <input type="number" wire:model.blur="threshold" min="0" max="100" step="5"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
            </div>

        </div>
    </div>

    @if ($selectedCourseGroupId)
        @php
            $daySections = $this->getSectionOptions();
            $rows = $this->getAttendanceRows();
            $stats = $this->getStats();
            $isSessionMode = (bool) $selectedSessionId;
        @endphp

        {{-- Day Tabs --}}
        @if (count($daySections))
            <div
                class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <nav class="flex min-w-max">
                    <button wire:click="selectSection(null)" @class([
                        'whitespace-nowrap border-b-2 px-5 py-3 text-sm font-medium transition-colors',
                        'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' => !$selectedSectionId,
                        'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => (bool) $selectedSectionId,
                    ])>
                        {{ __('admin.labels.all_days') }}
                    </button>
                    @foreach ($daySections as $sectionId => $sectionLabel)
                        <button wire:click="selectSection({{ $sectionId }})" @class([
                            'whitespace-nowrap border-b-2 px-5 py-3 text-sm font-medium transition-colors',
                            'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' =>
                                $selectedSectionId == $sectionId,
                            'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' =>
                                $selectedSectionId != $sectionId,
                        ])>
                            {{ $sectionLabel }}
                        </button>
                    @endforeach
                </nav>
            </div>
        @endif

        {{-- Stats bar --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.labels.total_enrolled') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    @if ($isSessionMode)
                        {{ __('admin.labels.present') }}
                    @else
                        {{ __('admin.labels.met_threshold') }} (≥ {{ $threshold }}%)
                    @endif
                </p>
                <p class="mt-1 text-2xl font-bold text-success-600 dark:text-success-400">{{ $stats['met'] }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    @if ($isSessionMode)
                        {{ __('admin.labels.absent') }}
                    @else
                        {{ __('admin.labels.no_attendance') }}
                    @endif
                </p>
                @php $noAttendancePct = $stats['total'] > 0 ? round($stats['no_attendance'] / $stats['total'] * 100) : 0; @endphp
                <p class="mt-1 text-2xl font-bold text-danger-600 dark:text-danger-400">
                    {{ $stats['no_attendance'] }}
                    <span class="text-sm font-normal text-gray-400">({{ $noAttendancePct }}%)</span>
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.labels.avg_attendance') }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['avg'] }}%</p>
            </div>
        </div>

        {{-- Action buttons --}}
        <div class="flex flex-wrap items-center justify-end gap-3">
            {{-- Sync from Zoom --}}
            <button wire:click="syncFromZoom" wire:loading.attr="disabled" wire:target="syncFromZoom"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                <x-heroicon-o-arrow-path class="h-4 w-4" wire:loading.class="animate-spin" wire:target="syncFromZoom" />
                <span wire:loading.remove
                    wire:target="syncFromZoom">{{ __('admin.labels.sync_zoom_attendance') }}</span>
                <span wire:loading wire:target="syncFromZoom">{{ __('admin.labels.syncing') }}…</span>
            </button>

            {{-- Export XLSX --}}
            @if ($rows->isNotEmpty())
                <button wire:click="exportXlsx"
                    class="inline-flex items-center gap-2 rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-success-700 focus:outline-none focus:ring-2 focus:ring-success-500 focus:ring-offset-2">
                    <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                    {{ __('admin.actions.export_xlsx') }}
                </button>
            @endif
        </div>

        {{-- Table --}}
        @if ($rows->isEmpty())
            <div
                class="rounded-xl border border-gray-200 bg-white p-10 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <x-heroicon-o-users class="mx-auto h-10 w-10 text-gray-400" />
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.labels.no_enrollments') }}</p>
            </div>
        @else
            <div
                class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">#</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">
                                {{ __('admin.labels.student') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">
                                {{ __('admin.labels.email') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">
                                {{ __('admin.labels.phone') }}</th>
                            @if ($isSessionMode)
                                <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">
                                    {{ __('admin.labels.attended_minutes') }}</th>
                            @else
                                <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">
                                    {{ __('admin.labels.sessions_attended') }}</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">
                                    {{ __('admin.labels.attended_minutes') }}</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">
                                    {{ __('admin.labels.attendance_pct') }}</th>
                            @endif
                            <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300">
                                {{ __('admin.labels.status') }}</th>
                            <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-300"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($rows as $i => $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $row['name'] }}
                                    @if ($row['is_guest'])
                                        <span
                                            class="ms-1 text-xs font-normal text-gray-400 dark:text-gray-500">({{ __('admin.labels.not_registered') }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $row['email'] }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 ltr:font-mono" dir="ltr">
                                    {{ $row['phone'] }}</td>
                                @if ($isSessionMode)
                                    <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-200">
                                        {{ $row['attended_minutes'] }} / {{ $row['total_minutes'] }} min
                                    </td>
                                @else
                                    <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-200">
                                        {{ $row['attended'] }} / {{ $row['total'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-200">
                                        {{ $row['attended_minutes'] }} / {{ $row['total_minutes'] }} min
                                    </td>
                                    <td class="px-4 py-3 text-center font-semibold">
                                        <span @class([
                                            'text-success-600 dark:text-success-400' =>
                                                $row['badge_color'] === 'success',
                                            'text-warning-600 dark:text-warning-400' =>
                                                $row['badge_color'] === 'warning',
                                            'text-danger-600 dark:text-danger-400' => $row['badge_color'] === 'danger',
                                        ])>{{ $row['pct'] }}%</span>
                                    </td>
                                @endif
                                <td class="px-4 py-3 text-center">
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                        'bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200' =>
                                            $row['badge_color'] === 'success',
                                        'bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-200' =>
                                            $row['badge_color'] === 'warning',
                                        'bg-danger-100 text-danger-800 dark:bg-danger-900 dark:text-danger-200' =>
                                            $row['badge_color'] === 'danger',
                                    ])>
                                        {{ $row['badge_label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($row['is_guest'])
                                        <button
                                            wire:click="openCorrectAttendance({{ json_encode($row['guest_record_ids']) }}, '{{ addslashes($row['name'] . ' — ' . $row['email']) }}')"
                                            class="inline-flex items-center gap-1 rounded-lg border border-warning-300 bg-warning-50 px-2.5 py-1 text-xs font-medium text-warning-700 hover:bg-warning-100 dark:border-warning-600 dark:bg-warning-900/30 dark:text-warning-400 dark:hover:bg-warning-900/60">
                                            <x-heroicon-o-pencil-square class="h-3.5 w-3.5" />
                                            {{ __('admin.actions.correct_attendance') }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @else
        {{-- Empty state --}}
        <div
            class="rounded-xl border border-gray-200 bg-white p-12 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <x-heroicon-o-clipboard-document-check class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-3 text-base font-semibold text-gray-900 dark:text-white">
                {{ __('admin.navigation.attendance_summary') }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.labels.select_group_prompt') }}</p>
        </div>
    @endif

    {{-- Correct Attendance Modal --}}
    <x-filament::modal
        id="correct-attendance"
        :heading="__('admin.labels.correct_attendance')"
        width="md"
        :close-button="true"
        :close-by-clicking-away="true"
    >
        <div class="space-y-4">
            <div class="rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                <span class="font-medium text-gray-700 dark:text-gray-200">{{ __('admin.labels.guest') }}:</span>
                {{ $pendingGuestLabel }}
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.labels.assign_to_student') }}
                </label>
                <div wire:key="assign-user-select-{{ implode('-', $pendingRecordIds) }}">
                    <x-admin.searchable-select
                        :options="$this->getEnrolledUserOptions()"
                        wire-model="assignToEnrollmentId"
                        :current-value="$assignToEnrollmentId"
                        :placeholder="'— ' . __('admin.labels.select') . ' —'"
                    />
                </div>
            </div>
        </div>

        <x-slot name="footer">
            <x-filament::button
                color="gray"
                wire:click="closeCorrectAttendance"
            >
                {{ __('admin.actions.cancel') }}
            </x-filament::button>

            <x-filament::button
                wire:click="confirmCorrectAttendance"
                wire:loading.attr="disabled"
                wire:target="confirmCorrectAttendance"
                :disabled="! $assignToEnrollmentId"
            >
                {{ __('admin.actions.confirm') }}
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

</x-filament-panels::page>
