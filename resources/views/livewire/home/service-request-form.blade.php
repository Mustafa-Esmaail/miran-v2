@php
    $locale = app()->getLocale();
    $title = $this->fieldText('heading', $locale, '');
    $subtitle = $this->fieldText('description', $locale, '');
    $submitLabel = $this->fieldText('submit_text', $locale, __('web.home.lead_submit'));
    $successMessage = $this->fieldText('success_message', $locale, __('web.home.lead_success'));
    $nameLabel = $this->fieldText('field_name', $locale, __('web.home.lead_name'));
    $phoneLabel = $this->fieldText('field_phone', $locale, __('web.home.lead_phone'));
    $emailLabel = $this->fieldText('field_email', $locale, __('admin.labels.email'));
    $messageLabel = $this->fieldText('field_message', $locale, __('web.home.lead_message'));
    $courseLabel = $this->fieldText('field_course', $locale, __('web.home.lead_course'));
    $coursePlaceholder = $this->fieldText('course_placeholder', $locale, __('web.home.lead_course_placeholder'));
@endphp

<section class="py-6">
    <div class="app-container">
        <div class="grid gap-8 rounded-2xl p-6 shadow-card sm:p-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">

            @if ($title || $subtitle)
                <div class="space-y-4">
                    @if ($title)
                        <h2 class="text-3xl font-semibold text-neutral-text">{{ $title }}</h2>
                    @endif
                    @if ($subtitle)
                        <p class="text-sm text-secondary">{{ $subtitle }}</p>
                    @endif
                </div>
            @endif

            <form wire:submit.prevent="submit" class="space-y-4 rounded-2xl bg-white p-6 shadow-card">

                <input type="text" wire:model.defer="companyFax" autocomplete="off" tabindex="-1"
                    style="position:absolute;left:-9999px;top:-9999px;width:0;height:0;opacity:0;" aria-hidden="true">

                @if ($submitted)
                    <div class="rounded-full border border-success/40 bg-success/10 px-4 py-3 text-sm text-success">
                        {{ $successMessage }}
                    </div>
                @endif

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-secondary">{{ $nameLabel }}</label>
                        <input type="text" wire:model.defer="name"
                            class="w-full rounded-full border border-border-neutral bg-white px-4 py-3 text-sm text-neutral-text">
                        @error('name')
                            <div class="text-xs text-alert">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-secondary">{{ $phoneLabel }}</label>
                        <input type="tel" wire:model.defer="phone"
                            class="ltr-input w-full rounded-full border border-border-neutral bg-white px-4 py-3 text-sm text-neutral-text">
                        @error('phone')
                            <div class="text-xs text-alert">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                @if ($showEmail)
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-secondary">{{ $emailLabel }}</label>
                        <input type="email" wire:model.defer="email"
                            class="w-full rounded-full border border-border-neutral bg-white px-4 py-3 text-sm text-neutral-text">
                        @error('email')
                            <div class="text-xs text-alert">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                @if ($showCourseSelect)
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-secondary">{{ $courseLabel }}</label>
                        <select wire:model.defer="courseId"
                            class="w-full rounded-full border border-border-neutral bg-white px-4 py-3 text-sm text-neutral-text">
                            <option value="">{{ $coursePlaceholder }}</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}">
                                    {{ $course->getTranslation('title', app()->getLocale()) }}
                                </option>
                            @endforeach
                        </select>
                        @error('courseId')
                            <div class="text-xs text-alert">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                @if ($showMessage)
                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-secondary">{{ $messageLabel }}</label>
                        <textarea rows="3" wire:model.defer="message"
                            class="w-full rounded-2xl border border-border-neutral bg-white px-4 py-3 text-sm text-neutral-text"></textarea>
                        @error('message')
                            <div class="text-xs text-alert">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                <button type="submit"
                    class="w-full rounded-full bg-brand-accent px-8 py-3 text-sm font-semibold text-white">
                    {{ $submitLabel }}
                </button>
            </form>
        </div>
    </div>
</section>
