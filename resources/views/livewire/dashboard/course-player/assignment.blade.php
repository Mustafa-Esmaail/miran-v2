<x-account-shell active="courses" :title="$course['title']" :nav-links="$navLinks" :show-header="false">
    <x-slot:hero>
        <x-course-player-banner :course="$course" />
    </x-slot:hero>

    <div class="space-y-6">
        <section class="rounded-[28px] border border-default bg-surface p-4 sm:p-6">
            <a href="{{ $day['route'] }}" class="text-xs font-semibold text-[#99745c]">{{ $day['title'] }}</a>
            <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-b border-muted pb-4">
                <div>
                    <h2 class="text-2xl font-semibold text-primary">{{ $assignmentData['title'] }}</h2>
                    <p class="mt-1 text-sm text-muted">
                        @if ($assignmentData['due_at'])
                            {{ __('web.course_player.assignment_due', ['date' => $assignmentData['due_at']->format('Y-m-d H:i')]) }}
                        @else
                            {{ __('web.course_player.assignment_no_due_date') }}
                        @endif
                    </p>
                </div>
            </div>

            @if ($assignmentData['description'])
                <div class="mt-6 rich-content">
                    {!! $assignmentData['description'] !!}
                </div>
            @endif

            @if (count($assignmentData['reference_files']))
                <div class="mt-6">
                    <h3 class="text-sm font-semibold text-primary">{{ __('web.course_player.assignment_download_reference') }}</h3>
                    <ul class="mt-2 space-y-1">
                        @foreach ($assignmentData['reference_files'] as $file)
                            <li>
                                <a href="{{ $file['url'] }}" target="_blank" rel="noopener noreferrer" class="text-sm text-[#5b62c3] underline">
                                    {{ $file['name'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        <section class="rounded-[28px] border border-default bg-surface p-4 sm:p-6">
            @if ($submission)
                <h3 class="text-lg font-semibold text-primary">{{ __('web.course_player.assignment_already_submitted') }}</h3>
                <p class="mt-1 text-sm text-muted">{{ __('web.course_player.assignment_submitted_at', ['date' => $submission->submitted_at?->format('Y-m-d H:i')]) }}</p>

                <ul class="mt-4 space-y-1">
                    @foreach ($submissionFiles as $file)
                        <li>
                            <a href="{{ $file['url'] }}" target="_blank" rel="noopener noreferrer" class="text-sm text-[#5b62c3] underline">
                                {{ $file['name'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-6 rounded-2xl bg-[#f7f5f2] p-4">
                    @if ($submission->grade !== null)
                        <p class="text-sm font-semibold text-primary">
                            {{ __('web.course_player.assignment_grade', ['grade' => $submission->grade, 'max' => $assignmentData['max_grade']]) }}
                        </p>
                        @if ($submission->feedback)
                            <p class="mt-2 text-sm text-muted">
                                <span class="font-semibold">{{ __('web.course_player.assignment_feedback') }}:</span>
                                {{ $submission->feedback }}
                            </p>
                        @endif
                    @else
                        <p class="text-sm text-muted">{{ __('web.course_player.assignment_not_graded_yet') }}</p>
                    @endif
                </div>
            @else
                <h3 class="text-lg font-semibold text-primary">{{ __('web.course_player.assignment_upload_label') }}</h3>

                @if (count($files))
                    <ul class="mt-4 space-y-2">
                        @foreach ($files as $index => $file)
                            <li class="flex items-center justify-between rounded-2xl border border-muted bg-[#f7f5f2] px-4 py-3 text-sm">
                                <span class="truncate text-primary">{{ $file->getClientOriginalName() }}</span>
                                <button type="button" wire:click="removeFile({{ $index }})"
                                    class="ms-3 shrink-0 text-xs font-semibold text-red-600 hover:underline">
                                    {{ __('web.course_player.assignment_remove_file') }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <form wire:submit="submit" class="mt-4 space-y-4">
                    <div x-data="{ fileName: '' }">
                        <input
                            id="assignment-file-upload"
                            type="file"
                            wire:model="newFile"
                            accept=".pdf,.xls,.xlsx,.csv,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.webp"
                            class="sr-only"
                            x-on:change="fileName = $event.target.files[0]?.name || ''"
                        >
                        <label
                            for="assignment-file-upload"
                            wire:loading.class="opacity-60 pointer-events-none"
                            wire:target="newFile"
                            class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-[#5b62c3] bg-[#eef0f9] px-4 py-8 text-sm text-muted transition hover:bg-[#e4e7f7]"
                        >
                            <span class="text-base font-semibold text-[#5b62c3]">
                                {{ __('web.course_player.assignment_add_file') }}
                            </span>
                            <span class="text-xs text-muted">{{ __('web.course_player.assignment_upload_hint') }}</span>
                        </label>
                        <div class="mt-2 text-xs text-muted" x-show="fileName" x-text="fileName"></div>
                    </div>

                    @error('newFile')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('files')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <button type="submit"
                        wire:loading.attr="disabled"
                        wire:target="submit"
                        class="inline-flex items-center rounded-lg bg-[#374746] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-60">
                        {{ __('web.course_player.assignment_submit') }}
                    </button>
                </form>
            @endif
        </section>
    </div>
</x-account-shell>
