<div class="rounded-2xl border border-[#e5e5e5] bg-[#fafafa] p-4" wire:key="reply-{{ $reply->id }}">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-[#242528]">
                {{ $reply->user?->name ?? __('web.blog.unknown_author') }}
            </p>
            <p class="text-xs text-[#868686]">{{ $reply->created_at?->diffForHumans() }}</p>
        </div>
        <div class="flex items-center gap-2">
            @if (! $reply->approved)
                <span class="rounded-full bg-[#fef3c7] px-3 py-1 text-xs font-semibold text-[#92400e]">
                    {{ __('web.comments.pending') }}
                </span>
            @endif
            @if ($reply->hidden)
                <span class="rounded-full bg-[#fee2e2] px-3 py-1 text-xs font-semibold text-[#b91c1c]">
                    {{ __('web.comments.hidden') }}
                </span>
            @endif
        </div>
    </div>

    @if ($editingReplyId === $reply->id)
        <div class="mt-3 flex flex-col gap-3">
            <textarea wire:model="editingContent"
                class="min-h-[90px] w-full rounded-2xl border border-[#ced0d3] px-4 py-2 text-sm"></textarea>
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
        <p class="mt-3 text-sm text-[#4b4c53]">{{ $reply->content }}</p>
    @endif

    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
        @if (auth()->check() && $reply->user_id === auth()->id())
            <button type="button"
                class="rounded-3xl border border-[#ced0d3] px-3 py-1 text-[#4b4c53]"
                wire:click="startEditReply({{ $reply->id }})">
                {{ __('web.comments.edit') }}
            </button>
            <button type="button"
                class="rounded-3xl border border-[#fee2e2] px-3 py-1 text-[#b91c1c]"
                wire:click="deleteReply({{ $reply->id }})">
                {{ __('web.comments.delete') }}
            </button>
        @endif
    </div>

    @auth
        @php($replyFormKey = "reply-{$comment->id}-{$reply->id}")
        <form wire:submit.prevent="submitReplyToReply({{ $comment->id }}, {{ $reply->id }})" class="mt-4 flex flex-col gap-3">
            <textarea wire:model="replyContent.{{ $replyFormKey }}"
                class="min-h-[90px] w-full rounded-2xl border border-[#ced0d3] px-4 py-2 text-sm"
                placeholder="{{ __('web.comments.reply_placeholder') }}"></textarea>
            @error("replyContent.$replyFormKey")
                <p class="text-xs text-alert">{{ $message }}</p>
            @enderror
            <button type="submit"
                class="self-start rounded-3xl border border-[#99745c] px-4 py-2 text-xs font-semibold text-[#99745c]">
                {{ __('web.comments.reply') }}
            </button>
        </form>
    @endauth

    @if ($reply->childReplies->isNotEmpty())
        <div class="mt-4 ms-4 flex flex-col gap-3 border-s border-[#e5e5e5] ps-4">
            @foreach ($reply->childReplies as $childReply)
                @include('livewire.comments.partials.reply-item', ['reply' => $childReply, 'comment' => $comment])
            @endforeach
        </div>
    @endif
</div>
