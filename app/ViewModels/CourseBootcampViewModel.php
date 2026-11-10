<?php

namespace App\ViewModels;

use App\Enums\AttachmentVisibility;
use App\Enums\CourseGroupStatus;
use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Support\S3Url;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Data for the bootcamp course page: localized marketing content from
 * `courses.template_content`, plus cohort, curriculum and schedule data
 * derived from the course's next open group.
 */
class CourseBootcampViewModel
{
    /**
     * @param  array<string, mixed>  $content
     * @param  array{id: int, start_at: ?string, deadline_at: ?string, capacity: ?int, seats_taken: int, seats_percent: ?int, is_available: bool}|null  $cohort
     * @param  array{hours: ?int, modules: int, weeks: int, sessions: int}  $stats
     * @param  array<int, array{week: int, label: string, modules: array<int, array<string, mixed>>}>  $curriculumWeeks
     * @param  array<int, array{week: int, label: string, sessions: array<int, array<string, ?string>>}>  $scheduleWeeks
     */
    public function __construct(
        public array $content,
        public ?array $cohort,
        public array $stats,
        public array $curriculumWeeks,
        public array $scheduleWeeks,
        public ?string $scheduleSummary,
        public ?string $heroImageUrl,
        public ?string $syllabusUrl,
    ) {}

    public static function fromCourse(Course $course): self
    {
        $bookableGroup = self::bookableGroup($course->groups);
        $displayGroup = $bookableGroup ?? $course->groups->sortByDesc('start_at')->first();

        $modules = self::modulesFor($course, $displayGroup);
        $sessions = $displayGroup instanceof CourseGroup
            ? $displayGroup->sessions()->with('curriculumSection')->orderBy('start_at')->get()
            : new Collection;

        $moduleNumbers = $modules->values()->mapWithKeys(
            fn (CourseCurriculumSection $module, int $index): array => [$module->id => $index + 1]
        );

        $curriculumWeeks = self::curriculumWeeks($modules, $moduleNumbers);
        $scheduleWeeks = self::scheduleWeeks($sessions, $moduleNumbers);
        $content = self::localizedContent($course->template_content ?? []);
        $totalMinutes = (int) $sessions->sum('duration_minutes');

        return new self(
            content: $content,
            cohort: $displayGroup instanceof CourseGroup ? self::cohort($displayGroup, $bookableGroup !== null) : null,
            stats: [
                'hours' => $course->duration_hours ?: ($totalMinutes > 0 ? (int) round($totalMinutes / 60) : null),
                'modules' => $modules->count(),
                'weeks' => max(count($scheduleWeeks), count($curriculumWeeks)),
                'sessions' => $sessions->count(),
            ],
            curriculumWeeks: $curriculumWeeks,
            scheduleWeeks: $scheduleWeeks,
            scheduleSummary: self::scheduleSummary($sessions),
            heroImageUrl: self::mediaUrl($course->template_content['hero_image'] ?? null) ?? $course->image_url,
            syllabusUrl: self::syllabusUrl($course),
        );
    }

    /**
     * The earliest open group that still accepts enrollments.
     *
     * @param  Collection<int, CourseGroup>  $groups
     */
    public static function bookableGroup(Collection $groups): ?CourseGroup
    {
        return $groups
            ->filter(function (CourseGroup $group): bool {
                if ($group->status !== CourseGroupStatus::Open) {
                    return false;
                }

                if ($group->enrollment_deadline_at && $group->enrollment_deadline_at->isPast()) {
                    return false;
                }

                if ($group->capacity) {
                    $taken = $group->enrollments_count ?? $group->enrollments()->count();

                    return $taken < $group->capacity;
                }

                return true;
            })
            ->sortBy(fn (CourseGroup $group): int => $group->start_at?->getTimestamp() ?? PHP_INT_MAX)
            ->first();
    }

    /**
     * @return Collection<int, CourseCurriculumSection>
     */
    private static function modulesFor(Course $course, ?CourseGroup $group): Collection
    {
        if ($group instanceof CourseGroup) {
            $groupModules = $course->curriculumSections()
                ->where('course_group_id', $group->id)
                ->get();

            if ($groupModules->isNotEmpty()) {
                return $groupModules;
            }
        }

        return $course->curriculumSections()->get();
    }

    /**
     * @param  Collection<int, CourseCurriculumSection>  $modules
     * @param  Collection<int, int>  $moduleNumbers
     * @return array<int, array{week: int, label: string, modules: array<int, array<string, mixed>>}>
     */
    private static function curriculumWeeks(Collection $modules, Collection $moduleNumbers): array
    {
        return $modules
            ->groupBy(fn (CourseCurriculumSection $module): int => $module->week ?: 1)
            ->sortKeys()
            ->map(function (Collection $weekModules, int $week) use ($moduleNumbers): array {
                $numbers = $weekModules->map(fn (CourseCurriculumSection $module): int => $moduleNumbers[$module->id]);
                $range = $numbers->min() === $numbers->max()
                    ? (string) $numbers->min()
                    : $numbers->min().'–'.$numbers->max();

                return [
                    'week' => $week,
                    'label' => __('web.bootcamp.week_with_modules', ['week' => $week, 'range' => $range]),
                    'modules' => $weekModules->map(fn (CourseCurriculumSection $module): array => [
                        'id' => $module->id,
                        'number' => str_pad((string) $moduleNumbers[$module->id], 2, '0', STR_PAD_LEFT),
                        'title' => $module->title,
                        'description' => $module->description,
                        'badge' => self::filled($module->badge),
                        'outcome' => self::filled($module->outcome),
                        'bullets' => collect($module->bullets ?? [])
                            ->map(fn (mixed $bullet): ?string => is_array($bullet) ? self::localize($bullet['text'] ?? null) : null)
                            ->filter()
                            ->values()
                            ->all(),
                        'is_featured' => $module->is_featured,
                        'is_capstone' => $module->is_capstone,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Sessions grouped into calendar weeks counted from the first session.
     *
     * @param  Collection<int, CourseGroupSession>  $sessions
     * @param  Collection<int, int>  $moduleNumbers
     * @return array<int, array{week: int, label: string, sessions: array<int, array<string, ?string>>}>
     */
    private static function scheduleWeeks(Collection $sessions, Collection $moduleNumbers): array
    {
        $datedSessions = $sessions->filter(fn (CourseGroupSession $session): bool => $session->start_at !== null)->values();
        $firstDay = $datedSessions->first()?->start_at->copy()->startOfDay();

        if (! $firstDay) {
            return [];
        }

        return $datedSessions
            ->groupBy(fn (CourseGroupSession $session): int => intdiv((int) $firstDay->diffInDays($session->start_at->copy()->startOfDay()), 7) + 1)
            ->map(fn (Collection $weekSessions, int $week): array => [
                'week' => $week,
                'label' => __('web.bootcamp.week', ['week' => $week]),
                'sessions' => $weekSessions->map(function (CourseGroupSession $session) use ($moduleNumbers): array {
                    $module = $session->curriculumSection;
                    $moduleNumber = $module ? ($moduleNumbers[$module->id] ?? null) : null;

                    return [
                        'day' => $session->start_at->translatedFormat('l'),
                        'date' => $session->start_at->translatedFormat('j M'),
                        'time' => self::timeRange($session->start_at, $session->duration_minutes),
                        'module' => $moduleNumber ? __('web.bootcamp.module_number', ['number' => str_pad((string) $moduleNumber, 2, '0', STR_PAD_LEFT)]) : null,
                        'title' => self::filled($session->title) ?? $module?->title,
                    ];
                })->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * e.g. "Saturday, Monday, Wednesday · 6:00 PM – 10:00 PM".
     *
     * @param  Collection<int, CourseGroupSession>  $sessions
     */
    private static function scheduleSummary(Collection $sessions): ?string
    {
        $first = $sessions->first(fn (CourseGroupSession $session): bool => $session->start_at !== null);
        if (! $first) {
            return null;
        }

        $days = $sessions
            ->filter(fn (CourseGroupSession $session): bool => $session->start_at !== null)
            ->sortBy(fn (CourseGroupSession $session): int => ($session->start_at->dayOfWeek + 1) % 7)
            ->map(fn (CourseGroupSession $session): string => $session->start_at->translatedFormat('l'))
            ->unique()
            ->implode(app()->getLocale() === 'ar' ? '، ' : ', ');

        return $days.' · '.self::timeRange($first->start_at, $first->duration_minutes);
    }

    private static function timeRange(CarbonInterface $start, ?int $durationMinutes): string
    {
        $from = $start->translatedFormat('g:i A');

        if (! $durationMinutes) {
            return $from;
        }

        return $from.' – '.$start->copy()->addMinutes($durationMinutes)->translatedFormat('g:i A');
    }

    /**
     * @return array{id: int, start_at: ?string, deadline_at: ?string, capacity: ?int, seats_taken: int, seats_percent: ?int, is_available: bool}
     */
    private static function cohort(CourseGroup $group, bool $isAvailable): array
    {
        $taken = (int) ($group->enrollments_count ?? $group->enrollments()->count());
        $capacity = $group->capacity ?: null;
        $deadline = $group->enrollment_deadline_at;

        return [
            'id' => $group->id,
            'start_at' => $group->start_at?->translatedFormat('j F Y'),
            'deadline_at' => $deadline && $deadline->isFuture() ? $deadline->toIso8601String() : null,
            'capacity' => $capacity,
            'seats_taken' => $taken,
            'seats_percent' => $capacity ? (int) min(100, round($taken / $capacity * 100)) : null,
            'is_available' => $isAvailable,
        ];
    }

    private static function syllabusUrl(Course $course): ?string
    {
        $attachment = $course->attachments
            ->first(fn (CourseAttachment $attachment): bool => in_array(
                $attachment->visibility,
                [AttachmentVisibility::Profile, AttachmentVisibility::Public],
                true
            ));

        return $attachment ? route('attachments.view', $attachment) : null;
    }

    /**
     * Resolves every `{ar, en}` pair in the stored content to the current locale.
     *
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private static function localizedContent(array $content): array
    {
        $text = fn (string $key): ?string => self::localize($content[$key] ?? null);
        $list = fn (string $key, array $fields, bool $withImage = false): array => collect($content[$key] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(function (array $item) use ($fields, $withImage): ?array {
                $row = collect($fields)->mapWithKeys(fn (string $field): array => [$field => self::localize($item[$field] ?? null)])->all();

                if (collect($row)->filter()->isEmpty()) {
                    return null;
                }

                if ($withImage) {
                    $row['image'] = self::mediaUrl($item['image'] ?? null);
                }

                return $row;
            })
            ->filter()
            ->values()
            ->all();

        return [
            'tagline' => $text('tagline'),
            'title_highlight' => $text('title_highlight'),
            'offer_label' => $text('offer_label'),
            'method_note' => $text('method_note'),
            'not_for' => $text('not_for'),
            'final_cta_title' => $text('final_cta_title'),
            'final_cta_text' => $text('final_cta_text'),
            'industries' => array_column($list('industries', ['text']), 'text'),
            'pillars' => $list('pillars', ['title', 'description', 'tag', 'footer']),
            'method_steps' => $list('method_steps', ['title', 'description']),
            'projects' => $list('projects', ['title', 'description', 'tag', 'module_label'], withImage: true),
            'before_items' => array_column($list('before_items', ['text']), 'text'),
            'after_items' => array_column($list('after_items', ['text']), 'text'),
            'audience' => $list('audience', ['icon', 'title', 'description']),
            'included_features' => array_column($list('included_features', ['text']), 'text'),
            'faqs' => $list('faqs', ['question', 'answer']),
        ];
    }

    private static function localize(mixed $value): ?string
    {
        if (! is_array($value)) {
            return self::filled($value);
        }

        $locale = app()->getLocale();
        $fallback = $locale === 'ar' ? 'en' : 'ar';

        return self::filled($value[$locale] ?? null) ?? self::filled($value[$fallback] ?? null);
    }

    private static function filled(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private static function mediaUrl(mixed $path): ?string
    {
        $path = self::filled($path);
        if ($path === null) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return S3Url::temporary($path);
    }
}
