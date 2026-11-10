<x-account-shell active="courses" :title="$course['title']" :nav-links="$navLinks" :show-header="false">
    <x-slot:hero>
        <x-course-player-banner :course="$course" />
    </x-slot:hero>

    <div class="space-y-6">
        <section class="rounded-[28px] border border-default bg-surface p-4 sm:p-6">
            <a href="{{ $day['route'] }}" class="text-xs font-semibold text-[#99745c]">{{ $day['title'] }}</a>
            <div class="mt-3 border-b border-muted pb-4">
                <h2 class="text-2xl font-semibold text-primary">{{ $quizData['title'] }}</h2>
                <p class="mt-1 text-sm text-muted">
                    {{ __('web.quizzes.passing_requirement', ['pass' => $quizData['passing_score'] ?? __('web.quizzes.not_available')]) }}
                </p>
                @if ($quizData['allow_resubmit'] && $quizData['max_attempts'])
                    <p class="mt-0.5 text-xs text-muted">
                        {{ __('web.quizzes.attempts_used', ['used' => $attemptCount, 'max' => $quizData['max_attempts']]) }}
                    </p>
                @endif
            </div>

            @if ($quizError)
                <div class="mt-4 rounded-xl border border-[#f3c0c0] bg-[#fdf2f2] px-4 py-2 text-xs text-[#b42318]">
                    {{ $quizError }}
                </div>
            @endif

            @if ($quizReview && !$retaking)
                {{-- Result review --}}
                @php
                    $isPassed = $quizData['passing_score']
                        ? (($quizReview['total_points'] > 0)
                            ? (($quizReview['score'] / $quizReview['total_points']) * 100) >= $quizData['passing_score']
                            : false)
                        : true;
                @endphp
                <div class="mt-6 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <p class="text-sm font-semibold text-primary">
                                {{ __('web.quizzes.result', ['score' => $quizReview['score'], 'total' => $quizReview['total_points']]) }}
                            </p>
                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-semibold
                                {{ $isPassed ? 'bg-[#e8f7ef] text-[#027a48]' : 'bg-[#fdf2f2] text-[#b42318]' }}">
                                {{ $isPassed ? __('web.quizzes.passed_badge') : __('web.quizzes.failed_badge') }}
                            </span>
                        </div>
                        @if ($canResubmit)
                            <button wire:click="retakeQuiz" type="button"
                                class="btn-ghost cursor-pointer rounded-full px-4 py-2 text-xs font-semibold">
                                {{ __('web.quizzes.retake') }}
                            </button>
                        @endif
                    </div>

                    @foreach ($quizReview['items'] as $item)
                        <div class="rounded-lg border border-default bg-white px-4 py-3">
                            <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                                <span class="font-semibold text-primary">{{ $item['question'] }}</span>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold
                                    {{ $item['is_correct'] ? 'bg-[#e8f7ef] text-[#027a48]' : 'bg-[#fdf2f2] text-[#b42318]' }}">
                                    {{ $item['is_correct'] ? __('web.quizzes.correct') : __('web.quizzes.incorrect') }}
                                </span>
                            </div>
                            <div class="mt-2 text-xs text-muted">{{ __('web.quizzes.selected_answer', ['answer' => $item['selected']]) }}</div>
                            <div class="mt-1 text-xs text-muted">{{ __('web.quizzes.correct_answer', ['answer' => $item['correct']]) }}</div>
                        </div>
                    @endforeach
                </div>

                {{-- Previous attempts history --}}
                @if ($quizAttempts->count() > 1)
                    <div class="mt-6">
                        <h3 class="mb-3 text-sm font-semibold text-primary">{{ __('web.quizzes.all_attempts') }}</h3>
                        <div class="space-y-2">
                            @foreach ($quizAttempts as $index => $pastAttempt)
                                <div class="flex items-center justify-between rounded-lg border border-default bg-white px-4 py-3 text-xs">
                                    <span class="font-semibold text-secondary">
                                        {{ __('web.quizzes.attempt_number', ['number' => $index + 1]) }}
                                        <span class="ml-1 text-muted">{{ $pastAttempt->created_at->format('Y-m-d H:i') }}</span>
                                    </span>
                                    <span class="font-semibold text-primary">
                                        {{ __('web.quizzes.attempt_score', ['score' => $pastAttempt->score]) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            @else
                {{-- Quiz form --}}
                <form wire:submit.prevent="submitQuiz" class="mt-6 space-y-4">
                    @foreach ($quizData['questions'] as $question)
                        <div class="rounded-lg border border-default bg-white px-4 py-4">
                            <p class="text-sm font-semibold text-primary">{{ $question->question }}</p>
                            <div class="mt-3 space-y-2">
                                @foreach ($question->options as $option)
                                    <label class="flex items-center gap-2 text-xs text-secondary"
                                        wire:key="course-player-quiz-option-{{ $option->id }}">
                                        <input type="radio" wire:model.live="quizAnswers.{{ $question->id }}"
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
                    <button type="submit" class="cursor-pointer rounded-full bg-primary px-4 py-2 text-xs font-semibold text-white">
                        {{ __('web.quizzes.submit') }}
                    </button>
                </form>
            @endif
        </section>
    </div>
</x-account-shell>
