<?php

namespace App\Livewire\Dashboard\CoursePlayer;

use App\Enums\CertificateRequestStatus;
use App\Enums\EnrollmentStatus;
use App\Livewire\Dashboard\CoursePlayer\Concerns\InteractsWithCoursePlayer;
use App\Models\CertificateRequest;
use App\Services\CourseProgressionService;
use Illuminate\View\View;
use Livewire\Component;

class Main extends Component
{
    use InteractsWithCoursePlayer;

    public string $course_slug;

    public int $group;

    public function mount(string $course_slug, int $group): void
    {
        $this->course_slug = $course_slug;
        $this->group = $group;
    }

    public function render(): View
    {
        $enrollment = $this->resolveEnrollment($this->course_slug, $this->group);
        $progression = app(CourseProgressionService::class);

        $certificateRequest = CertificateRequest::query()
            ->where('user_id', auth()->id())
            ->where('course_group_id', $this->group)
            ->first();

        $hasCertificateRequest = $certificateRequest !== null;
        $certificateRequestStatus = $certificateRequest?->status;
        $contentLocked = ! $hasCertificateRequest || $certificateRequestStatus === CertificateRequestStatus::Rejected;

        $days = $this->effectiveSections($enrollment)
            ->sortBy('sort_order')
            ->values()
            ->map(function ($day) use ($enrollment, $progression): array {
                $state = $progression->dayState($enrollment, $day);
                $isLocked = $state === 'locked';

                return [
                    'id' => $day->id,
                    'title' => $day->getTranslation('title', app()->getLocale()),
                    'description' => filled($day->description) ? $day->getTranslation('description', app()->getLocale()) : null,
                    'items_count' => $day->lessons()->count() + $day->sessions()->count() + $day->attachments()->count() + $day->quizzes()->count(),
                    'route' => $isLocked ? '#' : route('dashboard.course-player.day', [$this->course_slug, $this->group, $day->id]),
                    'state' => $state,
                    'is_locked' => $isLocked,
                    'is_completed' => $state === 'completed',
                ];
            });

        $isPendingCertificate = $enrollment->status === EnrollmentStatus::PendingCertificate;

        return view('livewire.dashboard.course-player.main', [
            'course' => $this->buildCourseMeta($enrollment),
            'days' => $days,
            'generalAttachments' => $this->buildGeneralAttachmentItems($enrollment),
            'navLinks' => $this->buildMainNavLinks($enrollment, $progression),
            'hasCertificateRequest' => $hasCertificateRequest,
            'certificateRequestStatus' => $certificateRequestStatus,
            'contentLocked' => $contentLocked,
            'isPendingCertificate' => $isPendingCertificate,
            'certificateFormUrl' => route('dashboard.certificate-form', ['group' => $this->group]),
        ])->layout('layouts.app');
    }
}
