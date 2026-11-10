<x-account-shell active="courses" :title="$course['title']" :nav-links="$navLinks" :show-header="false">
    <x-slot:hero>
        <x-course-player-banner :course="$course" />
    </x-slot:hero>

    <div class="space-y-6">
        <section class="rounded-[28px] border border-default bg-surface p-4 sm:p-6">
            <a href="{{ $day['route'] }}" class="text-xs font-semibold text-[#99745c]">{{ $day['title'] }}</a>
            <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-b border-muted pb-4">
                <div>
                    <h2 class="text-2xl font-semibold text-primary">{{ $lessonData['title'] }}</h2>
                    <p class="mt-1 text-sm text-muted">{{ __('web.course_player.lesson_types.' . $lessonData['type']) }}</p>
                </div>
                @if ($lessonData['resource_url'])
                    <a class="inline-flex items-center rounded-lg bg-[#374746] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                        href="{{ $lessonData['resource_url'] }}" target="_blank" rel="noopener noreferrer">
                        {{ __('web.course_player.open_resource') }}
                    </a>
                @endif
            </div>

            <div class="mt-6 rich-content">
                {!! $lessonData['content'] !!}
            </div>
        </section>

        <section class="rounded-[28px] border border-default bg-surface p-4 sm:p-6">
            <livewire:comments.comment-thread commentable-type="lesson" :commentable-id="$lessonRecord->id"
                :key="'course-player-lesson-comments-' . $lessonRecord->id" />
        </section>
    </div>
</x-account-shell>
