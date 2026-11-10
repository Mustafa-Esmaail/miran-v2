<div>
    @if ($open && $course)
        <div wire:click.self="close" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-6">
            <div class="card w-full max-w-2xl overflow-hidden">
                <div class="flex items-center justify-between border-b border-default px-6 py-4">
                    <div class="text-sm font-semibold text-secondary">{{ $quickViewTitle }}</div>
                    <button type="button" wire:click="close" class="text-muted hover:text-primary" aria-label="Close">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M6 18L18 6" />
                        </svg>
                    </button>
                </div>
                <div class="grid gap-6 px-6 py-5 sm:grid-cols-[1fr_1.1fr]">
                    <img
                        src="{{ $course['thumbnail'] ?? 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=800&q=80' }}"
                        alt="{{ $course['title'] }}"
                        class="h-48 w-full rounded-2xl object-cover"
                    />
                    <div class="space-y-3">
                        <div class="text-xs font-semibold uppercase text-secondary">{{ $course['category'] ?? '' }}</div>
                        <h3 class="text-xl font-semibold text-primary">{{ $course['title'] }}</h3>
                        <p class="text-sm text-secondary">{{ $course['summary'] }}</p>
                        <div class="flex items-center gap-3 text-sm">
                            <span class="font-semibold text-primary">
                                @if ($course['price'] !== null)
                                    {{ __('web.courses.price', ['amount' => $course['price']]) }}
                                @else
                                    {{ __('web.courses.price_tbd') }}
                                @endif
                            </span>
                            <span class="text-muted">
                                @if ($course['next_start'])
                                    {{ __('web.courses.next_start', ['date' => $course['next_start']]) }}
                                @else
                                    {{ __('web.courses.start_tbd') }}
                                @endif
                            </span>
                        </div>
                        <x-button href="{{ route('courses.show', $course['slug']) }}" variant="primary" size="md">
                            {{ $viewCourse }}
                        </x-button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
