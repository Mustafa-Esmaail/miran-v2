<div class="bg-[#f5f5f6] text-neutral-text">
    <section class="relative bg-brand-dark text-white">
        <div
            class="pointer-events-none absolute inset-0 opacity-[0.12]"
            style="background-image: linear-gradient(rgba(255,255,255,0.4) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 120px 120px;"
        ></div>
        <div
            class="pointer-events-none absolute -top-[420px] end-[-380px] h-[760px] w-[760px] rounded-full blur-2xl"
            style="background: radial-gradient(circle, rgba(113,150,147,0.4), rgba(113,150,147,0));"
        ></div>

        <div class="app-container py-12 sm:py-16">
            <div class="flex flex-col gap-6">
                <div class="flex flex-wrap items-center gap-3 text-sm text-white/80">
                    <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white">
                        {{ $blog->category?->getTranslation('title', app()->getLocale()) ?? __('web.blog.uncategorized') }}
                    </span>
                    <span>{{ $blog->author?->name ?? __('web.blog.unknown_author') }}</span>
                    <span class="opacity-70">â€¢</span>
                    <span>{{ $blog->published_at?->format('M d, Y') ?? __('web.blog.draft_label') }}</span>
                    @if ($blog->status?->value === 'draft')
                        <span class="rounded-full bg-[#fe4a23] px-3 py-1 text-[11px] font-semibold text-white">
                            {{ __('web.blog.draft_label') }}
                        </span>
                    @endif
                </div>
                <h1 class="text-3xl font-semibold text-white sm:text-4xl lg:text-5xl">{{ $blog->title }}</h1>
            </div>
        </div>
    </section>

    <section class="app-container py-10 lg:py-12">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]">
            <article class="rounded-3xl border border-[#ced0d3] bg-white p-6 text-[#4b4c53] shadow-sm sm:p-8">
                @if ($imageUrl)
                    <div class="mb-6 overflow-hidden rounded-2xl">
                        <img
                            src="{{ $imageUrl }}"
                            alt="{{ $blog->title }}"
                            class="h-[320px] w-full object-cover"
                        >
                    </div>
                @endif
                <div class="rich-content max-w-none">
                    {!! $content !!}
                </div>
            </article>

            <aside class="flex flex-col gap-4 rounded-3xl border border-[#ced0d3] bg-white p-6 text-sm text-[#4b4c53]">
                <div>
                    <p class="text-xs uppercase text-[#82868e]">{{ __('web.blog.meta.author') }}</p>
                    <p class="text-base font-semibold text-[#242528]">{{ $blog->author?->name ?? __('web.blog.unknown_author') }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase text-[#82868e]">{{ __('web.blog.meta.category') }}</p>
                    <p class="text-base font-semibold text-[#242528]">
                        {{ $blog->category?->getTranslation('title', app()->getLocale()) ?? __('web.blog.uncategorized') }}
                    </p>
                </div>
                <div>
                    <p class="text-xs uppercase text-[#82868e]">{{ __('web.blog.meta.published') }}</p>
                    <p class="text-base font-semibold text-[#242528]">
                        {{ $blog->published_at?->format('M d, Y') ?? __('web.blog.draft_label') }}
                    </p>
                </div>
                @if ($showUnapproved)
                    <div class="rounded-2xl bg-[#f5f5f6] p-4 text-xs text-[#71757a]">
                        {{ __('web.blog.admin_preview_notice') }}
                    </div>
                @endif
            </aside>
        </div>

        <div class="mt-10 flex flex-col gap-6">
            <div class="flex items-center justify-between gap-4">
                <x-section-title :title="__('web.blog.comments_title')" />
                <span class="text-sm text-[#71757a]">{{ __('web.blog.comments_count', ['count' => $comments->count()]) }}</span>
            </div>

            <div class="flex flex-col gap-4">
                @forelse ($comments as $comment)
                    <div class="rounded-2xl border border-[#ced0d3] bg-white p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-[#71757a]">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-[#242528]">{{ $comment->user?->name ?? __('web.blog.unknown_author') }}</span>
                                @if ($showUnapproved && $comment->status?->value === 'pending')
                                    <span class="rounded-full bg-[#fff2e9] px-2 py-0.5 text-[10px] font-semibold text-[#fe4a23]">
                                        {{ __('web.blog.pending_label') }}
                                    </span>
                                @endif
                            </div>
                            <span>{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="mt-3 text-sm text-[#4b4c53]">
                            {{ $comment->getTranslation('content', app()->getLocale()) }}
                        </p>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-[#ced0d3] bg-white p-6 text-center text-sm text-[#71757a]">
                        {{ __('web.blog.comments_empty') }}
                    </div>
                @endforelse
            </div>

            <div class="rounded-3xl border border-[#ced0d3] bg-white p-6 sm:p-8">
                @auth
                    <form wire:submit.prevent="submitComment" class="flex flex-col gap-4">
                        <label class="text-sm font-semibold text-[#242528]">{{ __('web.blog.add_comment') }}</label>
                        <textarea
                            wire:model.defer="comment"
                            rows="4"
                            class="w-full rounded-2xl border border-[#ced0d3] px-4 py-3 text-sm text-[#242528] focus:outline-none"
                            placeholder="{{ __('web.blog.comment_placeholder') }}"
                        ></textarea>
                        @error('comment')
                            <p class="text-xs text-alert">{{ $message }}</p>
                        @enderror
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-xs text-[#71757a]">{{ __('web.blog.comment_help') }}</p>
                            <button
                                type="submit"
                                class="rounded-full bg-[#99745c] px-6 py-2 text-sm font-semibold text-white"
                                wire:loading.attr="disabled"
                            >
                                {{ __('web.blog.submit_comment') }}
                            </button>
                        </div>
                    </form>
                @else
                    <div class="flex flex-col gap-3 text-sm text-[#71757a]">
                        <p>{{ __('web.blog.login_prompt') }}</p>
                        <a
                            href="{{ route('login') }}"
                            class="inline-flex items-center justify-center rounded-full bg-[#99745c] px-6 py-2 text-sm font-semibold text-white"
                        >
                            {{ __('web.blog.login_cta') }}
                        </a>
                    </div>
                @endauth
            </div>
        </div>
    </section>
</div>
