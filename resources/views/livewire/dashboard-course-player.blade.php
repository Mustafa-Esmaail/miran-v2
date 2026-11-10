<x-account-shell active="courses" :title="$course['title']" :nav-links="$navLinks">
    <div class="space-y-6" x-data="{ tab: 'curriculum' }">
        <section id="course-overview"
            class="relative overflow-hidden rounded-[28px] border border-default bg-[#374746] text-white">
            @if ($course['image_url'])
                <div class="absolute inset-0 bg-cover bg-center"
                    style="background-image: linear-gradient(135deg, rgba(16, 15, 20, 0.85), rgba(55, 71, 70, 0.72)), url('{{ $course['image_url'] }}');">
                </div>
            @else
                <div class="absolute inset-0 bg-[linear-gradient(135deg,#100f14,#374746)]"></div>
            @endif

            <div class="relative grid gap-6 px-5 py-6 sm:px-6 lg:grid-cols-[minmax(0,1.7fr)_minmax(260px,0.9fr)] lg:px-8 lg:py-8">
                <div class="space-y-5">
                    <div class="space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/70">
                            {{ $course['group'] }}
                        </p>
                        <h2 class="max-w-3xl text-2xl font-semibold leading-tight sm:text-3xl lg:text-4xl">
                            {{ $course['title'] }}
                        </h2>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-4 backdrop-blur-sm">
                            <p class="text-xs font-medium text-white/70">{{ __('web.course_player.learners_count') }}</p>
                            <p class="mt-2 text-2xl font-semibold">{{ $course['stats']['learners_count'] }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-4 backdrop-blur-sm">
                            <p class="text-xs font-medium text-white/70">{{ __('web.course_player.completion') }}</p>
                            <p class="mt-2 text-2xl font-semibold">{{ $course['stats']['completion_percent'] }}%</p>
                            <p class="mt-1 text-xs text-white/70">
                                {{ __('web.course_player.completed_items', [
                                    'completed' => $course['stats']['completed_content'],
                                    'total' => $course['stats']['total_content'],
                                ]) }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-4 backdrop-blur-sm">
                            <p class="text-xs font-medium text-white/70">{{ __('web.course_player.sections_count') }}</p>
                            <p class="mt-2 text-2xl font-semibold">{{ $course['stats']['sections_count'] }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-4 backdrop-blur-sm">
                            <p class="text-xs font-medium text-white/70">{{ __('web.course_player.sessions_count') }}</p>
                            <p class="mt-2 text-2xl font-semibold">{{ $course['stats']['sessions_count'] }}</p>
                            <p class="mt-1 text-xs text-white/70">
                                {{ __('web.course_player.lessons_count', ['count' => $course['stats']['lessons_count']]) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[24px] border border-white/15 bg-white/10 p-4 backdrop-blur-sm sm:p-5">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold">{{ __('web.course_player.progress') }}</p>
                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold">
                            {{ $course['stats']['completion_percent'] }}%
                        </span>
                    </div>
                    <div class="mt-4">
                        <livewire:course-progress-bar :course-slug="$course_slug" />
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-[28px] border border-default bg-surface p-4 sm:p-6">
            <div class="flex flex-wrap items-center gap-2 border-b border-muted pb-4">
                <button class="cursor-pointer rounded-full px-4 py-2 text-xs font-semibold transition"
                    :class="tab === 'curriculum' ? 'bg-elevated text-primary' : 'text-secondary hover:bg-elevated'"
                    @click="tab = 'curriculum'" type="button">
                    {{ __('web.course_player.curriculum') }}
                </button>
                <button class="cursor-pointer rounded-full px-4 py-2 text-xs font-semibold transition"
                    :class="tab === 'comments' ? 'bg-elevated text-primary' : 'text-secondary hover:bg-elevated'"
                    @click="tab = 'comments'" type="button">
                    {{ __('web.comments.tab') }}
                </button>
                <button class="cursor-pointer rounded-full px-4 py-2 text-xs font-semibold transition"
                    :class="tab === 'quizzes' ? 'bg-elevated text-primary' : 'text-secondary hover:bg-elevated'"
                    @click="tab = 'quizzes'" type="button">
                    {{ __('web.quizzes.tab') }}
                </button>
            </div>

            <div class="mt-6 space-y-5" x-show="tab === 'curriculum'">
                @forelse ($curriculumSections as $section)
                    <section id="{{ $section['key'] }}" class="scroll-mt-28 rounded-[24px] border border-default bg-white p-4 sm:p-5">
                        <div class="flex flex-col gap-3 border-b border-muted pb-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="space-y-2">
                                <p class="text-lg font-semibold text-primary">{{ $section['title'] }}</p>
                                @if ($section['description'])
                                    <div class="rich-content text-sm text-secondary">
                                        {!! $section['description'] !!}
                                    </div>
                                @endif
                            </div>
                            <span class="rounded-full bg-elevated px-3 py-1 text-xs font-semibold text-primary">
                                {{ __('web.course_player.section_summary', ['count' => count($section['items'])]) }}
                            </span>
                        </div>

                        <div class="mt-4" x-data="{ activeItemId: {{ $section['items'][0]['id'] ?? 'null' }} }">
                            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                @foreach ($section['items'] as $item)
                                    <button type="button"
                                        class="flex min-h-[152px] cursor-pointer flex-col items-start gap-4 rounded-[22px] border bg-white p-4 text-start shadow-sm transition"
                                        :class="activeItemId === {{ $item['id'] }} ? 'border-[#99745c] ring-2 ring-[#99745c]/15' : 'border-default hover:border-[#99745c]/50'"
                                        @click="activeItemId = {{ $item['id'] }}; $wire.markContentViewed('{{ $item['content_type'] }}', {{ $item['id'] }})">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl
                                            @if ($item['content_type'] === 'lesson') bg-[#eef3f2] text-[#374746]
                                            @elseif ($item['content_type'] === 'session') bg-[#f6efe8] text-[#99745c]
                                            @else bg-[#eef0f9] text-[#5b62c3] @endif">
                                            @if ($item['content_type'] === 'lesson')
                                                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="currentColor" aria-hidden="true">
                                                    <path d="M6.75 3.5A2.75 2.75 0 0 0 4 6.25v11.5a2.75 2.75 0 0 0 2.75 2.75h10.5A2.75 2.75 0 0 0 20 17.75V9.5607a2.75 2.75 0 0 0-.8055-1.9445l-2.8101-2.8101A2.75 2.75 0 0 0 14.4393 4H6.75Z" />
                                                    <path opacity="0.4" d="M8 11.25a.75.75 0 0 1 .75-.75h6.5a.75.75 0 0 1 0 1.5h-6.5A.75.75 0 0 1 8 11.25Zm0 3a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 0 1.5h-4.5A.75.75 0 0 1 8 14.25Z" />
                                                </svg>
                                            @elseif ($item['content_type'] === 'session')
                                                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="currentColor" aria-hidden="true">
                                                    <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm1 10.25a.75.75 0 0 1-.3416.6324l-3 2A.75.75 0 1 1 8.825 13.6176L11.5 11.8343V7.75a.75.75 0 0 1 1.5 0v4.5Z" />
                                                </svg>
                                            @else
                                                <svg viewBox="0 0 24 24" class="h-6 w-6" fill="currentColor" aria-hidden="true">
                                                    <path d="M7.75 3.5A2.75 2.75 0 0 0 5 6.25v11.5a2.75 2.75 0 0 0 2.75 2.75h8.5A2.75 2.75 0 0 0 19 17.75V8.8107a2.75 2.75 0 0 0-.8055-1.9445l-2.0607-2.0607A2.75 2.75 0 0 0 14.1893 4H7.75Z" />
                                                    <path opacity="0.4" d="M9 12.25a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 0 1.5h-4.5A.75.75 0 0 1 9 12.25Zm0 3a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 0 1.5h-4.5A.75.75 0 0 1 9 15.25Z" />
                                                </svg>
                                            @endif
                                        </div>
                                        <div class="space-y-2">
                                            <p class="text-sm font-semibold text-primary">{{ $item['title'] }}</p>
                                            <p class="text-xs text-muted">
                                                @if ($item['content_type'] === 'lesson')
                                                    {{ $item['type_label'] }}
                                                @elseif ($item['content_type'] === 'session')
                                                    {{ __('web.course_player.session_statuses.' . $item['status']) }}
                                                @else
                                                    {{ __('web.course_player.attachment_visibility.' . $item['visibility']) }}
                                                @endif
                                            </p>
                                        </div>
                                        <span class="mt-auto rounded-full bg-elevated px-3 py-1 text-[11px] font-semibold text-primary">
                                            @if ($item['content_type'] === 'lesson')
                                                {{ __('web.course_player.lessons') }}
                                            @elseif ($item['content_type'] === 'session')
                                                {{ __('web.course_player.sessions') }}
                                            @else
                                                {{ __('web.course_player.attachments') }}
                                            @endif
                                        </span>
                                    </button>
                                @endforeach
                            </div>

                            @foreach ($section['items'] as $item)
                                <div id="{{ $item['anchor'] }}" x-show="activeItemId === {{ $item['id'] }}" x-cloak
                                    class="mt-4 scroll-mt-28 overflow-hidden rounded-[22px] border border-default bg-white shadow-sm">
                                    <div class="border-b border-muted px-4 py-4">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <p class="text-base font-semibold text-primary">{{ $item['title'] }}</p>
                                                <p class="mt-1 text-xs text-muted">
                                                    @if ($item['content_type'] === 'lesson')
                                                        {{ $item['type_label'] }}
                                                    @elseif ($item['content_type'] === 'session')
                                                        {{ __('web.course_player.session_statuses.' . $item['status']) }}
                                                    @else
                                                        {{ __('web.course_player.attachment_visibility.' . $item['visibility']) }}
                                                    @endif
                                                </p>
                                            </div>
                                            <span class="rounded-full bg-elevated px-3 py-1 text-[11px] font-semibold text-primary">
                                                @if ($item['content_type'] === 'lesson')
                                                    {{ __('web.course_player.lessons') }}
                                                @elseif ($item['content_type'] === 'session')
                                                    {{ __('web.course_player.sessions') }}
                                                @else
                                                    {{ __('web.course_player.attachments') }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>

                                    <div class="px-4 py-4 text-sm text-secondary">
                                        @if ($item['content_type'] === 'lesson')
                                            <div class="space-y-4">
                                                @if ($item['resource_url'])
                                                    <a class="inline-flex items-center rounded-lg bg-[#374746] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                                                        href="{{ $item['resource_url'] }}" target="_blank" rel="noopener noreferrer">
                                                        {{ __('web.course_player.open_resource') }}
                                                    </a>
                                                @endif
                                                <div class="rich-content">
                                                    {!! $item['content'] !!}
                                                </div>
                                            </div>
                                        @elseif ($item['content_type'] === 'session')
                                            <div class="space-y-3">
                                                @if ($item['start_at'])
                                                    <p>{{ $item['start_at'] }}</p>
                                                @endif
                                                @if ($item['status'] !== 'ended' && $item['join_url'])
                                                    <a class="inline-flex items-center rounded-lg bg-[#374746] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                                                        href="{{ $item['join_url'] }}" target="_blank" rel="noopener noreferrer">
                                                        {{ __('web.course_player.join_session') }}
                                                    </a>
                                                @endif
                                                @if ($item['status'] === 'ended' && $item['recording_url'])
                                                    <a class="inline-flex items-center rounded-lg bg-[#374746] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                                                        href="{{ $item['recording_url'] }}" target="_blank" rel="noopener noreferrer">
                                                        {{ __('web.course_player.view_recording') }}
                                                    </a>
                                                @endif
                                                @if ($item['content'])
                                                    <div class="rich-content">
                                                        {!! $item['content'] !!}
                                                    </div>
                                                @endif
                                                @if (!empty($item['attachments']))
                                                    <div class="space-y-2">
                                                        <div class="text-xs font-semibold text-primary">
                                                            {{ __('web.course_player.session_attachments') }}
                                                        </div>
                                                        <div class="flex flex-wrap gap-2">
                                                            @foreach ($item['attachments'] as $attachment)
                                                                <a class="inline-flex items-center rounded-lg bg-[#374746] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                                                                    href="{{ $attachment['url'] }}" target="_blank" rel="noopener noreferrer">
                                                                    {{ $attachment['name'] }}
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            @if ($item['url'])
                                                <a class="inline-flex items-center rounded-lg bg-[#374746] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                                                    href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer">
                                                    {{ __('web.course_player.open_resource') }}
                                                </a>
                                            @else
                                                <span class="text-muted">{{ __('web.common.empty') }}</span>
                                            @endif
                                        @endif
                                    </div>

                                    <div class="border-t border-muted px-4 py-4">
                                        @if ($item['content_type'] === 'lesson')
                                            <livewire:comments.comment-thread commentable-type="lesson" :commentable-id="$item['id']"
                                                :key="'lesson-comments-' . $item['id']" />
                                        @elseif ($item['content_type'] === 'session')
                                            <livewire:comments.comment-thread commentable-type="course_group_session" :commentable-id="$item['id']"
                                                :key="'session-comments-' . $item['id']" />
                                        @else
                                            <livewire:comments.comment-thread commentable-type="course_attachment" :commentable-id="$item['id']"
                                                :key="'attachment-comments-' . $item['id']" />
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @empty
                    <div class="rounded-xl border border-dashed border-default bg-surface p-6 text-center text-sm text-muted">
                        {{ __('web.course_player.no_curriculum') }}
                    </div>
                @endforelse
            </div>

            <div id="course-comments" class="mt-6 space-y-3 scroll-mt-28" x-show="tab === 'comments'">
                <livewire:comments.comment-thread commentable-type="course" :commentable-id="$course['id']" />
            </div>

            <div id="course-quizzes" class="mt-6 space-y-3 scroll-mt-28" x-show="tab === 'quizzes'">
                @if ($quizError)
                    <div class="rounded-xl border border-[#f3c0c0] bg-[#fdf2f2] px-4 py-2 text-xs text-[#b42318]">
                        {{ $quizError }}
                    </div>
                @endif
                @forelse ($quizzes as $quiz)
                    <div class="rounded-xl border border-default bg-surface p-4" wire:key="quiz-{{ $quiz['id'] }}">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-primary">{{ $quiz['title'] }}</p>
                                <p class="mt-1 text-xs text-muted">
                                    {{ __('web.quizzes.questions_count', ['count' => $quiz['questions_count']]) }}
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-elevated px-3 py-1 text-xs font-semibold text-primary">
                                    {{ __('web.quizzes.statuses.' . $quiz['status']) }}
                                </span>
                                @if ($quiz['has_attempt'])
                                    <button type="button" wire:click="startQuiz({{ $quiz['id'] }})"
                                        class="cursor-pointer rounded-full border border-primary px-3 py-1 text-xs font-semibold text-primary">
                                        {{ __('web.quizzes.view_results') }}
                                    </button>
                                @else
                                    <button type="button" wire:click="startQuiz({{ $quiz['id'] }})"
                                        class="cursor-pointer rounded-full bg-primary px-3 py-1 text-xs font-semibold text-white">
                                        {{ __('web.quizzes.start') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-secondary">
                            <span>
                                {{ __('web.quizzes.score', [
                                    'score' => $quiz['score'] ?? __('web.quizzes.not_available'),
                                    'pass' => $quiz['passing_score'] ?? __('web.quizzes.not_available'),
                                ]) }}
                            </span>
                        </div>

                        @if ($activeQuiz && $activeQuiz->id === $quiz['id'])
                            <div class="mt-4 rounded-xl border border-default bg-surface p-4">
                                @if ($activeQuizResults)
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-sm font-semibold text-primary">
                                            {{ __('web.quizzes.score', [
                                                'score' => $activeQuizResults['score'],
                                                'pass' => $activeQuizResults['total_points'],
                                            ]) }}
                                        </p>
                                        <span class="text-xs text-muted">
                                            {{ __('web.quizzes.attempted') }}
                                        </span>
                                    </div>
                                    <div class="mt-4 space-y-3">
                                        @foreach ($activeQuizResults['items'] as $item)
                                            <div class="rounded-lg border border-default bg-surface px-3 py-2">
                                                <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                                                    <span class="font-semibold text-primary">
                                                        {{ $item['question'] }}
                                                    </span>
                                                    <span
                                                        class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $item['is_correct'] ? 'bg-[#e8f7ef] text-[#027a48]' : 'bg-[#fdf2f2] text-[#b42318]' }}">
                                                        {{ $item['is_correct'] ? __('web.quizzes.correct') : __('web.quizzes.incorrect') }}
                                                    </span>
                                                </div>
                                                <div class="mt-2 text-xs text-muted">
                                                    {{ __('web.quizzes.selected_answer', ['answer' => $item['selected']]) }}
                                                </div>
                                                <div class="mt-1 text-xs text-muted">
                                                    {{ __('web.quizzes.correct_answer', ['answer' => $item['correct']]) }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <form wire:submit.prevent="submitQuiz({{ $quiz['id'] }})" class="space-y-4">
                                        @foreach ($activeQuiz->questions as $question)
                                            <div class="rounded-lg border border-default bg-surface px-3 py-3">
                                                <p class="text-sm font-semibold text-primary">{{ $question->question }}</p>
                                                <div class="mt-3 space-y-2">
                                                    @foreach ($question->options as $option)
                                                        <label class="flex items-center gap-2 text-xs text-secondary"
                                                            wire:key="quiz-option-{{ $option->id }}">
                                                            <input type="radio"
                                                                wire:model.live="quizAnswers.{{ $question->id }}"
                                                                value="{{ $option->id }}" class="h-4 w-4">
                                                            <span>{{ $option->label }}</span>
                                                        </label>
                                                    @endforeach
                                                    @error('quizAnswers.' . $question->id)
                                                        <div class="text-xs text-alert">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        @endforeach
                                        <button type="submit"
                                            class="cursor-pointer rounded-full bg-primary px-4 py-2 text-xs font-semibold text-white">
                                            {{ __('web.quizzes.submit') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-default bg-surface p-6 text-center text-sm text-muted">
                        {{ __('web.quizzes.empty') }}
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-account-shell>
