<section class="relative overflow-hidden text-white">
    @if ($course['image_url'])
        <div class="absolute inset-0 bg-cover bg-center opacity-88" style="background-image: url('{{ $course['image_url'] }}');">
        </div>
    @else
        <div class="absolute inset-0 bg-[linear-gradient(135deg,#374746_0%,#1f2a2a_100%)]"></div>
    @endif

    <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(0,0,0,0.100),rgba(10,14,14,0.68),rgba(0,0,0,0.150))]"></div>
    <div class="absolute inset-0 bg-black/10"></div>

    <div class="relative mx-auto w-full max-w-[1440px] px-6 py-10 sm:px-8 lg:px-10 lg:py-14">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(320px,0.9fr)] lg:items-end">
            <div class="space-y-4">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/80">{{ $course['group'] }}</p>
                <h1 class="max-w-4xl text-3xl font-semibold leading-tight sm:text-4xl lg:text-5xl">
                    {{ $course['title'] }}
                </h1>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-[28px] border border-white/15 bg-black/20 px-4 py-4 backdrop-blur-sm">
                        <p class="text-xs font-medium text-white/85">{{ __('web.course_player.learners_count') }}</p>
                        <p class="mt-2 text-2xl font-semibold">{{ $course['stats']['learners_count'] }}</p>
                    </div>
                    <div class="rounded-[28px] border border-white/15 bg-black/20 px-4 py-4 backdrop-blur-sm">
                        <p class="text-xs font-medium text-white/85">{{ __('web.course_player.completion') }}</p>
                        <p class="mt-2 text-2xl font-semibold">{{ $course['stats']['completion_percent'] }}%</p>
                    </div>
                    <div class="rounded-[28px] border border-white/15 bg-black/20 px-4 py-4 backdrop-blur-sm">
                        <p class="text-xs font-medium text-white/85">{{ __('web.course_player.sections_count') }}</p>
                        <p class="mt-2 text-2xl font-semibold">{{ $course['stats']['sections_count'] }}</p>
                    </div>
                    <div class="rounded-[28px] border border-white/15 bg-black/20 px-4 py-4 backdrop-blur-sm">
                        <p class="text-xs font-medium text-white/85">{{ __('web.course_player.sessions_count') }}</p>
                        <p class="mt-2 text-2xl font-semibold">{{ $course['stats']['sessions_count'] }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-[28px] border border-white/15 bg-black/25 p-4 backdrop-blur-sm sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold">{{ __('web.course_player.progress') }}</p>
                    <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold">
                        {{ $course['stats']['completion_percent'] }}%
                    </span>
                </div>

                <div class="mt-4">
                    <livewire:course-progress-bar :course-slug="$course['slug']" />
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 text-sm text-white/80">
                    <div class="rounded-[24px] border border-white/10 bg-black/20 px-4 py-3">
                        <p class="text-xs font-medium text-white/75">{{ __('web.course_player.lessons') }}</p>
                        <p class="mt-1 text-lg font-semibold text-white">{{ $course['stats']['lessons_count'] }}</p>
                    </div>
                    <div class="rounded-[24px] border border-white/10 bg-black/20 px-4 py-3">
                        <p class="text-xs font-medium text-white/75">{{ __('web.course_player.curriculum') }}</p>
                        <p class="mt-1 text-lg font-semibold text-white">
                            {{ __('web.course_player.completed_items', ['completed' => $course['stats']['completed_content'], 'total' => $course['stats']['total_content']]) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
