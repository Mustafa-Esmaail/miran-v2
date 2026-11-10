@php
    use App\ViewModels\QuizAttemptAnswersViewModel;
    $allAttempts = $allAttempts ?? collect([$record]);
    $activeId = $record->id;
@endphp

<div x-data="{ activeTab: {{ $activeId }} }">

    {{-- Tabs --}}
    @if ($allAttempts->count() > 1)
        <div class="mb-4 flex flex-wrap gap-2 border-b border-gray-200 pb-3 dark:border-gray-700">
            @foreach ($allAttempts as $i => $attempt)
                <button
                    type="button"
                    x-on:click="activeTab = {{ $attempt->id }}"
                    x-bind:class="activeTab === {{ $attempt->id }}
                        ? 'bg-primary-600 text-white shadow-sm'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                >
                    {{ __('admin.labels.attempt_tab', ['number' => $i + 1]) }}
                </button>
            @endforeach
        </div>
    @endif

    {{-- Tab panels --}}
    @foreach ($allAttempts as $attempt)
        @php $answersView = QuizAttemptAnswersViewModel::fromAttempt($attempt); @endphp

        <div x-show="activeTab === {{ $attempt->id }}" x-cloak class="space-y-4">

            <dl class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.labels.score') }}</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">{{ $attempt->score }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.labels.answered_questions') }}</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">
                        {{ $answersView['answered_count'] }} / {{ $answersView['question_count'] }}
                    </dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ __('admin.labels.correct_answers') }}</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">
                        {{ $answersView['correct_count'] }} / {{ $answersView['question_count'] }}
                    </dd>
                </div>
            </dl>

            @forelse ($answersView['items'] as $item)
                <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                #{{ $item['number'] }} | {{ $item['points'] }} {{ __('admin.labels.points') }}
                            </p>
                            <h3 class="mt-1 text-sm font-semibold leading-6 text-gray-950 dark:text-white">
                                {{ $item['question'] }}
                            </h3>
                        </div>

                        @if (! $item['is_answered'])
                            <span class="inline-flex w-fit items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                {{ __('admin.labels.unanswered') }}
                            </span>
                        @elseif ($item['is_correct'])
                            <span class="inline-flex w-fit items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300">
                                {{ __('admin.labels.correct') }}
                            </span>
                        @else
                            <span class="inline-flex w-fit items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                {{ __('admin.labels.incorrect') }}
                            </span>
                        @endif
                    </div>

                    <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('admin.labels.selected_answer') }}</dt>
                            <dd class="mt-1 text-sm text-gray-950 dark:text-white">
                                @if (! $item['is_answered'])
                                    {{ __('admin.labels.unanswered') }}
                                @elseif ($item['has_missing_option'])
                                    {{ __('admin.labels.missing_option', ['answer' => $item['selected_answer']]) }}
                                @else
                                    {{ $item['selected_answer'] }}
                                @endif
                            </dd>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('admin.labels.correct_answer') }}</dt>
                            <dd class="mt-1 text-sm text-gray-950 dark:text-white">
                                {{ $item['correct_answer'] ?? __('admin.labels.not_available') }}
                            </dd>
                        </div>
                    </dl>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                    {{ __('admin.labels.no_quiz_questions') }}
                </div>
            @endforelse

            @if ($answersView['extra_answers'] !== [])
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/60 dark:bg-amber-950/30">
                    <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">{{ __('admin.labels.extra_answers') }}</p>
                    <dl class="mt-3 space-y-2 text-xs text-amber-900 dark:text-amber-100">
                        @foreach ($answersView['extra_answers'] as $extraAnswer)
                            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-white/70 px-3 py-2 dark:bg-gray-900/60">
                                <dt>{{ __('admin.labels.question') }} #{{ $extraAnswer['question_id'] }}</dt>
                                <dd class="font-medium">{{ $extraAnswer['answer'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            <details class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                <summary class="cursor-pointer font-medium">{{ __('admin.labels.raw_answers') }}</summary>
                <pre class="mt-3 max-h-72 overflow-auto whitespace-pre-wrap break-words">{{ $answersView['raw_json'] }}</pre>
            </details>

        </div>
    @endforeach

</div>
