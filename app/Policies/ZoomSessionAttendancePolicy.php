<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ZoomSessionAttendance;

class ZoomSessionAttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('zoom_session_attendances.viewAny');
    }

    public function view(User $user, ZoomSessionAttendance $zoomSessionAttendance): bool
    {
        return $user->can('zoom_session_attendances.view');
    }

    public function create(User $user): bool
    {
        return $user->can('zoom_session_attendances.create');
    }

    public function update(User $user, ZoomSessionAttendance $zoomSessionAttendance): bool
    {
        return $user->can('zoom_session_attendances.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('zoom_session_attendances.delete');
    }

    public function delete(User $user, ZoomSessionAttendance $zoomSessionAttendance): bool
    {
        return $user->can('zoom_session_attendances.delete');
    }
}
