<div>
    {{-- TODO: Questions UI temporarily disabled; keeping markup commented for later reuse. --}}
    {{--
<div class="flex flex-col gap-8">
    <div class="flex flex-col gap-3">
        <p class="text-xl font-semibold text-[#242528]">{{ __('web.questions.title') }}</p>
        <p class="text-base text-[#4b4c53]">{{ __('web.questions.subtitle') }}</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
        <div class="flex flex-col gap-4">
            <div class="rounded-3xl border border-[#ced0d3] bg-white p-5">
                <p class="text-base font-semibold text-[#242528]">{{ __('web.questions.list_title') }}</p>
                <div class="mt-4 flex flex-col gap-3">
                    @forelse ($questions as $question)
                        <button type="button"
                            class="flex flex-col gap-2 rounded-2xl border px-4 py-3 text-left transition {{ $selectedQuestion?->id === $question->id ? 'border-[#99745c] bg-[#f9f4f0]' : 'border-[#e5e5e5] bg-white' }}"
                            wire:click="selectQuestion({{ $question->id }})"
                            wire:key="question-select-{{ $question->id }}">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-[#242528]">{{ $question->title }}</p>
                                <span class="text-xs text-[#868686]">
                                    {{ $question->approved_answers_count ?? 0 }} {{ __('web.questions.answers') }}
                                </span>
                            </div>
                            <p class="text-xs text-[#868686]">
                                {{ $question->user?->name ?? __('web.blog.unknown_author') }}
                                Â· {{ $question->created_at?->diffForHumans() }}
                            </p>
                        </button>
                    @empty
                        <p class="text-sm text-[#4b4c53]">{{ __('web.questions.empty') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-3xl border border-[#ced0d3] bg-white p-5">
                <p class="text-base font-semibold text-[#242528]">{{ __('web.questions.ask_title') }}</p>
                @auth
                    <form wire:submit.prevent="submitQuestion" class="mt-4 flex flex-col gap-3">
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-[#4b4c53]">{{ __('web.questions.title_label') }}</label>
                            <input type="text" wire:model="questionTitle"
                                class="w-full rounded-2xl border border-[#ced0d3] px-4 py-2 text-sm"
                                placeholder="{{ __('web.questions.title_placeholder') }}">
                            @error('questionTitle')
                                <p class="text-xs text-alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-[#4b4c53]">{{ __('web.questions.content_label') }}</label>
                            <textarea wire:model="questionContent"
                                class="min-h-[120px] w-full rounded-2xl border border-[#ced0d3] px-4 py-2 text-sm"
                                placeholder="{{ __('web.questions.content_placeholder') }}"></textarea>
                            @error('questionContent')
                                <p class="text-xs text-alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit"
                            class="rounded-3xl bg-[#99745c] px-4 py-2 text-sm font-semibold text-white">
                            {{ __('web.questions.submit_question') }}
                        </button>
                    </form>
                @else
                    <p class="mt-3 text-sm text-[#4b4c53]">{{ __('web.questions.login_prompt') }}</p>
                    <a href="{{ route('login') }}"
                        class="mt-3 inline-flex items-center justify-center rounded-3xl bg-[#99745c] px-4 py-2 text-sm font-semibold text-white">
                        {{ __('web.questions.login_cta') }}
                    </a>
                @endauth
            </div>
        </div>

        <div class="flex flex-col gap-6">
            <div class="rounded-3xl border border-[#ced0d3] bg-white p-6">
                @if ($selectedQuestion)
                    <div class="flex flex-col gap-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex flex-col gap-1">
                                <p class="text-lg font-semibold text-[#242528]">{{ $selectedQuestion->title }}</p>
                                <p class="text-sm text-[#4b4c53]">{{ $selectedQuestion->content }}</p>
                            </div>
                            <div class="text-right text-xs text-[#868686]">
                                <p>{{ $selectedQuestion->user?->name ?? __('web.blog.unknown_author') }}</p>
                                <p>{{ $selectedQuestion->created_at?->diffForHumans() }}</p>
                            </div>
                        </div>
                        <div class="text-xs text-[#868686]">
                            {{ $selectedQuestion->approved_answers_count ?? 0 }} {{ __('web.questions.answers') }}
                        </div>
                    </div>
                @else
                    <p class="text-sm text-[#4b4c53]">{{ __('web.questions.select_prompt') }}</p>
                @endif
            </div>

            <div class="rounded-3xl border border-[#ced0d3] bg-white p-6">
                <p class="text-base font-semibold text-[#242528]">{{ __('web.questions.answers_title') }}</p>
                <div class="mt-4 flex flex-col gap-4">
                    @if ($selectedQuestion)
                        @forelse ($selectedQuestion->answers as $answer)
                            <div class="flex flex-col gap-3 rounded-2xl border border-[#e5e5e5] p-4"
                                wire:key="question-answer-{{ $answer->id }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-[#242528]">
                                            {{ $answer->user?->name ?? __('web.blog.unknown_author') }}
                                        </p>
                                        <p class="text-xs text-[#868686]">
                                            {{ $answer->created_at?->diffForHumans() }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if ($answer->is_accepted)
                                            <span class="rounded-full bg-[#e7f6ed] px-3 py-1 text-xs font-semibold text-[#1b7f3d]">
                                                {{ __('web.questions.accepted') }}
                                            </span>
                                        @endif
                                        @if ($answer->status->value === 'pending')
                                            <span class="rounded-full bg-[#fef3c7] px-3 py-1 text-xs font-semibold text-[#92400e]">
                                                {{ __('web.questions.answer_pending') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <p class="text-sm text-[#4b4c53]">{{ $answer->content }}</p>

                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="button"
                                        class="inline-flex items-center gap-2 rounded-3xl border border-[#e5e5e5] px-3 py-1 text-xs text-[#4b4c53]"
                                        wire:click="markHelpful({{ $answer->id }})">
                                        <span>{{ __('web.questions.helpful') }}</span>
                                        <span class="text-[#99745c]">{{ $answer->helpful_count ?? 0 }}</span>
                                    </button>

                                    @if ($selectedQuestion && auth()->check() && ($selectedQuestion->user_id === auth()->id() || $isInstructor))
                                        <button type="button"
                                            class="inline-flex items-center gap-2 rounded-3xl border border-[#99745c] px-3 py-1 text-xs font-semibold text-[#99745c]"
                                            wire:click="acceptAnswer({{ $answer->id }})">
                                            {{ __('web.questions.accept_answer') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-[#4b4c53]">{{ __('web.questions.answers_empty') }}</p>
                        @endforelse
                    @else
                        <p class="text-sm text-[#4b4c53]">{{ __('web.questions.select_prompt') }}</p>
                    @endif
                </div>
            </div>

            <div class="rounded-3xl border border-[#ced0d3] bg-white p-6">
                <p class="text-base font-semibold text-[#242528]">{{ __('web.questions.add_answer') }}</p>
                @auth
                    <form wire:submit.prevent="submitAnswer" class="mt-4 flex flex-col gap-3">
                        <textarea wire:model="answerContent"
                            class="min-h-[120px] w-full rounded-2xl border border-[#ced0d3] px-4 py-2 text-sm"
                            placeholder="{{ __('web.questions.answer_placeholder') }}"></textarea>
                        @error('answerContent')
                            <p class="text-xs text-alert">{{ $message }}</p>
                        @enderror
                        <button type="submit"
                            class="rounded-3xl bg-[#99745c] px-4 py-2 text-sm font-semibold text-white">
                            {{ __('web.questions.submit_answer') }}
                        </button>
                    </form>
                @else
                    <p class="mt-3 text-sm text-[#4b4c53]">{{ __('web.questions.login_prompt_answer') }}</p>
                    <a href="{{ route('login') }}"
                        class="mt-3 inline-flex items-center justify-center rounded-3xl bg-[#99745c] px-4 py-2 text-sm font-semibold text-white">
                        {{ __('web.questions.login_cta') }}
                    </a>
                @endauth
            </div>

            @if ($paginator->hasPages())
                <div class="rounded-3xl border border-[#ced0d3] bg-white px-6 py-4">
                    {{ $paginator->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

    --}}
</div>
