<?php

namespace App\Observers;

use App\Models\CourseGroupSession;
use App\Services\NotificationService;

class CourseGroupSessionObserver
{
    public function created(CourseGroupSession $session): void
    {
        app(NotificationService::class)->notifyNewCourseGroupContent($session);
    }
}
