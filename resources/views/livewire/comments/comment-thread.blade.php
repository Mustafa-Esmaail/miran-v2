<div class="flex flex-col gap-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-lg font-semibold text-[#242528]">{{ __('web.comments.title') }}</p>
            <p class="text-sm text-[#4b4c53]">{{ __('web.comments.subtitle') }}</p>
        </div>
    </div>

    <div class="rounded-3xl border border-[#ced0d3] bg-white p-6">
        @auth
            <form wire:submit.prevent="submitComment" class="flex flex-col gap-3">
                <textarea wire:model="commentContent"
                    class="min-h-[120px] w-full rounded-2xl border border-[#ced0d3] px-4 py-2 text-sm"
                    placeholder="{{ __('web.comments.placeholder') }}"></textarea>
                @error('commentContent')
                    <p class="text-xs text-alert">{{ $message }}</p>
                @enderror
                <button type="submit"
                    class="self-start rounded-3xl bg-[#99745c] px-4 py-2 text-sm font-semibold text-white">
                    {{ __('web.comments.submit') }}
                </button>
            </form>
        @else
            <p class="text-sm text-[#4b4c53]">{{ __('web.comments.login_prompt') }}</p>
            <a href="{{ route('login') }}"
                class="mt-3 inline-flex items-center justify-center rounded-3xl bg-[#99745c] px-4 py-2 text-sm font-semibold text-white">
                {{ __('web.comments.login_cta') }}
            </a>
        @endauth
    </div>

    <div class="flex flex-col gap-4">
        @forelse ($comments as $comment)
            <div class="rounded-3xl border border-[#ced0d3] bg-white p-6" wire:key="comment-{{ $comment->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-[#242528]">
                            {{ $comment->user?->name ?? __('web.blog.unknown_author') }}
                        </p>
                        <p class="text-xs text-[#868686]">{{ $comment->created_at?->diffForHumans() }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if (! $comment->approved)
                            <span class="rounded-full bg-[#fef3c7] px-3 py-1 text-xs font-semibold text-[#92400e]">
                                {{ __('web.comments.pending') }}
                            </span>
                        @endif
                        @if ($comment->hidden)
                            <span class="rounded-full bg-[#fee2e2] px-3 py-1 text-xs font-semibold text-[#b91c1c]">
                                {{ __('web.comments.hidden') }}
                            </span>
                        @endif
                    </div>
                </div>

                @if ($editingCommentId === $comment->id)
                    <div class="mt-3 flex flex-col gap-3">
                        <textarea wire:model="editingContent"
                            class="min-h-[100px] w-full rounded-2xl border border-[#ced0d3] px-4 py-2 text-sm"></textarea>
                        @error('editingContent')
                            <p class="text-xs text-alert">{{ $message }}</p>
                        @enderror
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button"
                                class="rounded-3xl bg-[#99745c] px-4 py-2 text-xs font-semibold text-white"
                                wire:click="saveEdit">
                                {{ __('web.comments.save') }}
                            </button>
                            <button type="button"
                                class="rounded-3xl border border-[#ced0d3] px-4 py-2 text-xs font-semibold text-[#4b4c53]"
                                wire:click="cancelEdit">
                                {{ __('web.comments.cancel') }}
                            </button>
                        </div>
                    </div>
                @else
                    <p class="mt-3 text-sm text-[#4b4c53]">{{ $comment->content }}</p>
                @endif

                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                    @if (auth()->check() && $comment->user_id === auth()->id())
                        <button type="button"
                            class="rounded-3xl border border-[#ced0d3] px-3 py-1 text-[#4b4c53]"
                            wire:click="startEditComment({{ $comment->id }})">
                            {{ __('web.comments.edit') }}
                        </button>
                        <button type="button"
                            class="rounded-3xl border border-[#fee2e2] px-3 py-1 text-[#b91c1c]"
                            wire:click="deleteComment({{ $comment->id }})">
                            {{ __('web.comments.delete') }}
                        </button>
                    @endif
                </div>

                <div class="mt-4 flex flex-col gap-3">
                    @forelse ($comment->replies as $reply)
                        @include('livewire.comments.partials.reply-item', ['reply' => $reply, 'comment' => $comment])
                    @empty
                        <p class="text-sm text-[#4b4c53]">{{ __('web.comments.no_replies') }}</p>
                    @endforelse
                </div>

                @auth
                    @php($commentReplyKey = "comment-{$comment->id}")
                    <form wire:submit.prevent="submitReply({{ $comment->id }})" class="mt-4 flex flex-col gap-3">
                        <textarea wire:model="replyContent.{{ $commentReplyKey }}"
                            class="min-h-[90px] w-full rounded-2xl border border-[#ced0d3] px-4 py-2 text-sm"
                            placeholder="{{ __('web.comments.reply_placeholder') }}"></textarea>
                        @error("replyContent.$commentReplyKey")
                            <p class="text-xs text-alert">{{ $message }}</p>
                        @enderror
                        <button type="submit"
                            class="self-start rounded-3xl border border-[#99745c] px-4 py-2 text-xs font-semibold text-[#99745c]">
                            {{ __('web.comments.reply') }}
                        </button>
                    </form>
                @endauth
            </div>
        @empty
            <p class="text-sm text-[#4b4c53]">{{ __('web.comments.empty') }}</p>
        @endforelse
    </div>
</div>
