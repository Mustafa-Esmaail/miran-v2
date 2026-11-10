<x-layouts.app :title="$page->title" :meta-title="$metaTitle ?? null" :meta-description="$metaDescription ?? null" :og-image-url="$ogImageUrl ?? null"
    :whatsapp-message="__('web.floating_contact.course_message', ['title' => $page->title])">
    @php
        $locale = app()->getLocale();
        $tabSections = $sections->where('type', 'course_profile_tab')->values();
        $otherSections = $sections->reject(fn($section) => $section->type === 'course_profile_tab');
    @endphp

    <section class="app-container py-10 text-center">
        <h1 class="course-profile-hero-title text-3xl font-bold text-[#100f14] sm:text-4xl">{{ $page->title }}</h1>
    </section>

    @if (!empty($courseGroups) && $courseGroups->count() > 0)
        <section class="app-container pb-10">
            <x-course-profile-groups-table :groups="$courseGroups" :heading="__('web.course_profile.groups_title')" />
        </section>
    @endif

    @if (!empty($courseAttachments) && $courseAttachments->count() > 0)
        <section class="app-container pb-10 text-center">
            <x-course-profile-attachments-list :attachments="$courseAttachments">
                @if ($ctaUrl ?? null)
                    <li>
                        <x-course-profile-cta :cta-label="$ctaLabel ?? null" :cta-url="$ctaUrl ?? null" />
                    </li>
                @endif
            </x-course-profile-attachments-list>
        </section>
    @endif

    @if (!empty($bundleCourses) && $bundleCourses->count() > 0)
        <section class="app-container pb-10 space-y-10">
            @foreach ($bundleCourses as $bundleCourse)
                <div class="space-y-4">
                    <h2 class="text-2xl font-bold text-[#100f14] text-center">{{ $bundleCourse->title }}</h2>

                    <x-course-profile-groups-table :groups="$bundleCourse->groups" :heading="__('web.course_profile.groups_title')" />

                    <div class="text-center">
                        <x-course-profile-attachments-list :attachments="$bundleCourse->attachments" />
                    </div>
                </div>
            @endforeach
        </section>
    @endif

    @if ($otherSections->count() > 0)
        @foreach ($otherSections as $section)
            @includeIf("sections.{$section->type}", ['section' => $section, 'landable' => $landable ?? null])
        @endforeach
    @endif

    @if ($tabSections->count() > 0)
        <section class="app-container py-10">
            <div class="course-profile-shell mx-auto w-full rounded-3xl bg-surface px-6 py-6 sm:px-10 lg:px-[72px]"
                x-data="{ active: 0 }">
                <div class="flex flex-wrap justify-center gap-2" role="tablist">
                    @foreach ($tabSections as $index => $section)
                        <button type="button" role="tab"
                            @click="active = (active === {{ $index }} ? null : {{ $index }})"
                            :class="active === {{ $index }} ? 'text-[#374746]' : 'bg-[#374746] text-white'"
                            class="course-profile-tab-btn rounded-full px-5 py-3 text-base font-semibold transition-colors">
                            {{ $section->title }}
                        </button>
                    @endforeach
                </div>

                @foreach ($tabSections as $index => $section)
                    @php
                        $body = $section->fieldText('body', $locale);
                        $gallery = $section->fieldValue('gallery', []);
                        $gallery = is_array($gallery) ? $gallery : [];
                    @endphp
                    <div x-show="active === {{ $index }}" role="tabpanel" class="space-y-4 py-6">
                        @if ($body)
                            <div class="rich-content max-w-none text-sm">{!! $body !!}</div>
                        @endif
                        @if (count($gallery) > 0)
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                                @foreach ($gallery as $imagePath)
                                    <img src="{{ \App\Support\S3Url::temporary($imagePath) }}"
                                        alt="{{ $section->title }}"
                                        class="aspect-square w-full rounded-xl object-cover">
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ((empty($courseAttachments) || $courseAttachments->count() === 0) && empty($bundleCourses))
        <section class="app-container pb-10 text-center">
            <div class="mx-auto max-w-xs">
                <x-course-profile-cta :cta-label="$ctaLabel ?? null" :cta-url="$ctaUrl ?? null" />
            </div>
        </section>
    @endif

    @if (!empty($bundleCourses) && $bundleCourses->count() > 0)
        <section class="app-container pb-10 text-center">
            <div class="mx-auto max-w-xs">
                <x-course-profile-cta :cta-label="$ctaLabel ?? null" :cta-url="$ctaUrl ?? null" />
            </div>
        </section>
    @endif

    <style>
        @keyframes course-profile-attachment-pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.06);
            }
        }

        .course-profile-attachment-link {
            animation: course-profile-attachment-pulse 1.8s ease-in-out infinite;
        }

        .course-profile-attachment-link:hover {
            animation-play-state: paused;
            transform: scale(1.08);
            background-color: #7d5f49;
        }

        @keyframes course-profile-tab-pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.06);
            }
        }

        .course-profile-tab-btn {
            transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out;
            animation: course-profile-tab-pulse 2.2s ease-in-out infinite;
        }

        .course-profile-tab-btn:hover {
            animation-play-state: paused;
            transform: scale(1.1);
        }

        @keyframes course-profile-hero-grow {
            from {
                transform: scale(0.85);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .course-profile-hero-title {
            animation: course-profile-hero-grow 0.6s ease-out;
        }
    </style>
</x-layouts.app>