<x-account-shell active="courses" :title="$course['title']" :nav-links="$navLinks" :show-header="false">
    <x-slot:hero>
        <x-course-player-banner :course="$course" />
    </x-slot:hero>

    <div class="space-y-6">
        <section class="rounded-[28px] border border-default bg-surface p-4 sm:p-6">
            <a href="{{ $day['route'] }}" class="text-xs font-semibold text-[#99745c]">{{ $day['title'] }}</a>
            <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-b border-muted pb-4">
                <div>
                    <h2 class="text-2xl font-semibold text-primary">{{ $sessionData['title'] }}</h2>
                    <p class="mt-1 text-sm text-muted">{{ __('web.course_player.session_statuses.' . $sessionData['status']) }}</p>
                    @if ($sessionData['start_at'])
                        <p class="mt-1 text-sm text-muted">{{ $sessionData['start_at'] }}</p>
                    @endif
                    @if ($sessionData['start_at'] && $sessionData['end_at'])
                        <p class="mt-1 text-sm text-muted">{{ $sessionData['start_at'] }} - {{ $sessionData['end_at'] }}</p>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2">
                    @if (in_array($sessionData['status'], ['upcoming', 'live'], true) && $sessionData['join_url'])
                        <a class="inline-flex items-center rounded-lg bg-[#374746] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                            href="{{ $sessionData['join_url'] }}" target="_blank" rel="noopener noreferrer">
                            {{ __('web.course_player.join_session') }}
                        </a>
                    @endif
                    @if ($sessionData['status'] === 'ended' && $sessionData['recording_url'])
                        <a class="inline-flex items-center rounded-lg bg-[#374746] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                            href="{{ $sessionData['recording_url'] }}" target="_blank" rel="noopener noreferrer">
                            {{ __('web.course_player.view_recording') }}
                        </a>
                    @endif
                </div>
            </div>

            @if ($sessionData['content'])
                <div class="mt-6 rich-content">
                    {!! $sessionData['content'] !!}
                </div>
            @endif

            @if ($sessionData['attachments']->isNotEmpty())
                <div class="mt-6 border-t border-muted pt-4">
                    <p class="text-sm font-semibold text-primary">{{ __('web.course_player.session_attachments') }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($sessionData['attachments'] as $attachment)
                            <a class="inline-flex items-center rounded-lg bg-[#374746] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                                href="{{ $attachment['url'] }}" target="_blank" rel="noopener noreferrer">
                                {{ $attachment['name'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        {{-- Attendance records (only for ended sessions with synced data) --}}
        @if ($sessionData['status'] === 'ended')
            <section class="rounded-[28px] border border-default bg-surface p-4 sm:p-6">
                <p class="text-sm font-semibold text-primary">{{ __('web.course_player.your_attendance') }}</p>

                @if ($attendanceRecords->isEmpty())
                    <p class="mt-3 text-sm text-muted">{{ __('web.course_player.attendance_none') }}</p>
                @else
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-muted text-xs text-muted">
                                    <th class="py-2 text-start font-medium">#</th>
                                    <th class="py-2 text-start font-medium">{{ __('web.course_player.attendance_joined') }}</th>
                                    <th class="py-2 text-start font-medium">{{ __('web.course_player.attendance_left') }}</th>
                                    <th class="py-2 text-start font-medium">{{ __('web.course_player.attendance_duration') }} ({{ $sessionDurationMinutes }})</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-muted">
                                @foreach ($attendanceRecords as $i => $record)
                                    <tr>
                                        <td class="py-2 text-muted">{{ $i + 1 }}</td>
                                        <td class="py-2" >{{ $record->joined_at?->format('H:i') }}</td>
                                        <td class="py-2">{{ $record->left_at?->format('H:i') }}</td>
                                        <td class="py-2">{{ __('web.course_player.attendance_minutes', ['minutes' => $record->duration_minutes]) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-muted font-semibold">
                                    <td colspan="3" class="pt-3 text-start text-xs text-muted">{{ __('web.course_player.attendance_total') }}</td>
                                    <td class="pt-3">
                                        {{ __('web.course_player.attendance_minutes', ['minutes' => $totalAttendedMinutes]) }}  / {{ __('web.course_player.attendance_minutes', ['minutes' => $sessionDurationMinutes]) }}
                                        @if ($sessionDurationMinutes > 0)
                                            <span class="ms-1 text-xs font-normal text-muted">({{ $attendancePct }}%)</span>
                                        @endif
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </section>
        @endif

        <section class="rounded-[28px] border border-default bg-surface p-4 sm:p-6">
            <livewire:comments.comment-thread commentable-type="course_group_session" :commentable-id="$sessionRecord->id"
                :key="'course-player-session-comments-' . $sessionRecord->id" />
        </section>
    </div>
</x-account-shell>
