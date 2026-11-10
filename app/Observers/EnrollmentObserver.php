<?php

namespace App\Observers;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Services\ChatService;

class EnrollmentObserver
{
    public function created(Enrollment $enrollment): void
    {
        if ($enrollment->status === EnrollmentStatus::Active) {
            app(ChatService::class)->addEnrolledStudentToGroupRoom($enrollment);
            $this->flagForGtmSignup($enrollment);
        }
    }

    public function restored(Enrollment $enrollment): void
    {
        if ($enrollment->status === EnrollmentStatus::Active) {
            app(ChatService::class)->addEnrolledStudentToGroupRoom($enrollment);
        }
    }

    public function updated(Enrollment $enrollment): void
    {
        if (! $enrollment->wasChanged('status')) {
            return;
        }

        if ($enrollment->status === EnrollmentStatus::Active) {
            app(ChatService::class)->addEnrolledStudentToGroupRoom($enrollment);
        }

        if ($enrollment->status === EnrollmentStatus::Cancelled) {
            app(ChatService::class)->removeEnrolledStudentFromGroupRoom($enrollment);
        }
    }

    /**
     * Flags this enrollment so the student's next page load (order confirmation) can
     * fire a GTM `sign_up` event. Only meaningful inside a real web request with a
     * session (e.g. checkout); a no-op during queued jobs/CLI/webhooks.
     */
    private function flagForGtmSignup(Enrollment $enrollment): void
    {
        if (! request()->hasSession()) {
            return;
        }

        $ids = session('gtm_signup_enrollment_ids', []);
        $ids[] = $enrollment->id;
        session()->flash('gtm_signup_enrollment_ids', $ids);
    }
}
