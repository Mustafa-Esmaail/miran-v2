<x-filament-panels::page>
    @php
        $attempt    = $this->record;
        $quiz       = $attempt->quiz;
        $user       = $attempt->user;
        $totalPts   = (int) ($quiz?->questions->sum('points') ?? 0);
        $passingPct = (int) ($quiz?->passing_score ?? 0);
        $isPassed   = $totalPts > 0
            ? (int) round(($attempt->score / $totalPts) * 100) >= $passingPct
            : ($passingPct === 0);
        $attemptNo  = \App\Models\QuizAttempt::query()
            ->where('quiz_id', $attempt->quiz_id)
            ->where('user_id', $attempt->user_id)
            ->where('id', '<=', $attempt->id)
            ->count();
        $answersView = $this->getAnswersData();
    @endphp

    <div class="space-y-6">

        {{-- ── Summary header ─────────────────────────────────────────────── --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="grid grid-cols-2 gap-px bg-gray-200 dark:bg-gray-700 sm:grid-cols-4">

                {{-- User --}}
                <div class="bg-white px-5 py-4 dark:bg-gray-900">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ __('admin.labels.user') }}
                    </p>
                    <p class="mt-1 truncate text-sm font-semibold text-gray-950 dark:text-white">
                        {{ $user?->name ?? '—' }}
                    </p>
                    <p class="truncate text-xs text-gray-400 dark:text-gray-500">
                        {{ $user?->email ?? '—' }}
                    </p>
                </div>

                {{-- Quiz --}}
                <div class="bg-white px-5 py-4 dark:bg-gray-900">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ __('admin.labels.quiz') }}
                    </p>
                    <p class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">
                        {{ $quiz?->title ?? '—' }}
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        {{ __('admin.labels.passing_score') }}: {{ $passingPct }}%
                    </p>
                </div>

                {{-- Score --}}
                <div class="bg-white px-5 py-4 dark:bg-gray-900">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ __('admin.labels.score') }}
                    </p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-gray-950 dark:text-white">
                        {{ $attempt->score }}<span class="text-base font-normal text-gray-400"> / {{ $totalPts }}</span>
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        {{ $totalPts > 0 ? round(($attempt->score / $totalPts) * 100) : 0 }}%
                    </p>
                </div>

                {{-- Status + Attempt # + Date --}}
                <div class="bg-white px-5 py-4 dark:bg-gray-900">
                    <div class="flex items-start justify-between gap-2">
                        {{-- Pass / Fail badge --}}
                        <span @class([
                            'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold',
                            'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' => $isPassed,
                            'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'         => ! $isPassed,
                        ])>
                            {{ $isPassed ? __('admin.labels.passed') : __('admin.labels.failed') }}
                        </span>
                        {{-- Attempt number badge --}}
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            #{{ $attemptNo }}
                        </span>
                    </div>
                    <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                        {{ $attempt->created_at?->format('Y-m-d  H:i') }}
                    </p>
                </div>

            </div>
        </div>

        {{-- ── Questions & Answers ─────────────────────────────────────────── --}}
        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                {{ __('admin.sections.questions_and_answers') }}
                <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-700 dark:text-gray-300">
                    {{ $answersView['correct_count'] }} / {{ $answersView['question_count'] }} {{ __('admin.labels.correct') }}
                </span>
            </h3>

            @include('filament.quiz-attempt-answers', ['record' => $attempt])
        </div>

    </div>
</x-filament-panels::page>
