<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Models\CourseGroupSession;

class SessionStatusService
{
    public function status(CourseGroupSession $session): SessionStatus
    {
        $start = $session->start_at;
        $end = $start->copy()->addMinutes($session->duration_minutes);

        if (now()->between($start, $end)) {
            return SessionStatus::Live;
        }

        if (now()->lessThan($start)) {
            return SessionStatus::Upcoming;
        }

        return SessionStatus::Ended;
    }
}
