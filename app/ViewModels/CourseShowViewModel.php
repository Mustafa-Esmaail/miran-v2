<?php

namespace App\ViewModels;

use App\Enums\CourseGroupStatus;
use App\Models\Course;
use App\Models\CourseGroupSession;
use App\Support\S3Url;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CourseShowViewModel
{
    public function __construct(
        public int $id,
        public string $title,
        public string $summary,
        public string $description,
        public string $slug,
        public ?string $imageUrl,
        public ?string $introVideoUrl,
        public ?float $price,
        public ?float $finalPrice,
        public bool $isFree,
        public ?int $durationHours,
        public ?int $daysCount,
        public ?string $durationGroup,
        public ?string $deliveryTypeLabel,
        public ?string $courseLevelLabel,
        public ?string $accreditationNumber,
        public string $categoryName,
        public array $groups,
        public array $sessions,
        public int $studentsCount,
        public array $instructors,
        public ?float $ratingAverage,
        public int $reviewsCount,
        public array $ratingBreakdown,
        public int $satisfactionPercent,
    ) {}

    public static function fromCourse(
        Course $course,
        Collection $sessions,
        array $reviewSummary = []
    ): self {
        $imageUrl = $course->image_url;
        $introVideoUrl = $course->intro_video_url;

        $groups = $course->groups->filter(function ($group) {
            $statusValue = $group->status instanceof \BackedEnum ? $group->status->value : (string) $group->status;
            if ($statusValue !== CourseGroupStatus::Open->value) {
                return false;
            }

            if ($group->enrollment_deadline_at && $group->enrollment_deadline_at->isPast()) {
                return false;
            }

            if ($group->capacity) {
                $count = $group->enrollments_count ?? $group->enrollments()->count();
                if ($count >= $group->capacity) {
                    return false;
                }
            }

            return true;
        })->map(function ($group) {
            return [
                'id' => $group->id,
                'title' => $group->getTranslation('title', app()->getLocale()),
            ];
        })->all();

        $sessionData = $sessions->map(function (CourseGroupSession $session) {
            return [
                'title' => $session->title ?? '',
                'start_at' => optional($session->start_at)->format('Y-m-d H:i'),
                'duration_minutes' => $session->duration_minutes,
            ];
        })->all();

        $courseLevelValue = $course->course_level instanceof \BackedEnum
            ? $course->course_level->value
            : ($course->course_level ?? null);
        $courseLevelLabel = $courseLevelValue
            ? __('web.courses.level_'.$courseLevelValue)
            : null;

        $instructors = $course->relationLoaded('trainers')
            ? $course->trainers->map(function ($trainer) {
                $avatar = $trainer->avatar_path ?? null;
                if ($avatar && ! Str::startsWith($avatar, ['http://', 'https://'])) {
                    $avatar = S3Url::temporary($avatar);
                }

                return [
                    'name' => $trainer->name ?? '',
                    'avatar' => $avatar,
                    'about' => $trainer->about ?? '',
                ];
            })->all()
            : [];

        $summaryDefaults = [
            'average' => null,
            'count' => 0,
            'breakdown' => [],
            'satisfaction_percent' => 0,
        ];
        $reviewSummary = array_merge($summaryDefaults, $reviewSummary);
        $reviewCount = (int) $reviewSummary['count'];
        $reviewAverage = $reviewSummary['average'] !== null ? (float) $reviewSummary['average'] : null;
        if ($reviewCount === 0) {
            $reviewAverage = 5.0;
        }

        return new self(
            $course->id,
            $course->title ?? '',
            $course->summary ?? '',
            $course->description ?? '',
            $course->slug,
            $imageUrl,
            $introVideoUrl,
            $course->price,
            $course->final_price,
            (bool) $course->is_free,
            $course->duration_hours,
            $course->days_count,
            $course->duration_group,
            $course->delivery_type ? Str::headline($course->delivery_type->value) : null,
            $courseLevelLabel,
            $course->accreditation_number,
            $course->category?->name ?? '',
            $groups,
            $sessionData,
            (int) ($course->enrollments_count ?? ($course->relationLoaded('enrollments') ? $course->enrollments->count() : 0)),
            $instructors,
            $reviewAverage,
            $reviewCount,
            $reviewSummary['breakdown'],
            (int) $reviewSummary['satisfaction_percent']
        );
    }
}
