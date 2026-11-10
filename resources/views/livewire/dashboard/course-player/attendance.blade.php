<x-account-shell active="courses" :title="$course['title']" :nav-links="$navLinks" :show-header="false">
    <x-slot:hero>
        <x-course-player-banner :course="$course" />
    </x-slot:hero>

    <div class="space-y-6">
        <section class="rounded-[28px] border border-default bg-surface p-4 sm:p-6">
            <h2 class="text-xl font-semibold text-primary">{{ __('web.course_player.my_attendance') }}</h2>

            @if ($sessionRows->isEmpty())
                <p class="mt-4 text-sm text-muted">{{ __('web.course_player.attendance_no_sessions') }}</p>
            @else
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-muted text-xs text-muted">
                                <th class="py-2 text-start font-medium">#</th>
                                <th class="py-2 text-start font-medium">{{ __('web.course_player.attendance_session') }}</th>
                                <th class="py-2 text-start font-medium">{{ __('web.course_player.attendance_date') }}</th>
                                <th class="py-2 text-center font-medium">{{ __('web.course_player.attendance_duration') }}</th>
                                <th class="py-2 text-center font-medium">{{ __('web.course_player.attendance_attended_of') }}</th>
                                <th class="py-2 text-center font-medium">%</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-muted">
                            @foreach ($sessionRows as $i => $row)
                                <tr class="hover:bg-muted/30 transition-colors">
                                    <td class="py-3 text-muted">{{ $i + 1 }}</td>
                                    <td class="py-3">
                                        <a href="{{ $row['route'] }}"
                                            class="font-medium text-primary hover:underline">
                                            {{ $row['title'] }}
                                        </a>
                                    </td>
                                    <td class="py-3 text-muted" dir="ltr">
                                        {{ $row['date'] }}
                                        <span class="text-xs">{{ $row['time'] }}</span>
                                    </td>
                                    <td class="py-3 text-center text-muted">
                                        {{ __('web.course_player.attendance_minutes', ['minutes' => $row['total_minutes']]) }}
                                    </td>
                                    <td class="py-3 text-center">
                                        {{ __('web.course_player.attendance_minutes', ['minutes' => $row['attended_minutes']]) }}
                                    </td>
                                    <td class="py-3 text-center">
                                        @php
                                            $color = $row['pct'] >= 60 ? 'text-green-600' : ($row['pct'] >= 40 ? 'text-yellow-600' : 'text-red-500');
                                        @endphp
                                        <span class="font-semibold {{ $color }}">{{ $row['pct'] }}%</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-account-shell>
