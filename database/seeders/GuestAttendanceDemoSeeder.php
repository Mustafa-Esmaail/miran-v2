<?php

namespace Database\Seeders;

use App\Models\CourseGroup;
use App\Models\ZoomSessionAttendance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Seeds unregistered (guest) ZoomSessionAttendance rows onto the demo group
 * created by AttendanceDemoSeeder. Run AttendanceDemoSeeder first.
 *
 * Guests: enrollment_id = null, user_id = null — these are the rows that
 * show the "Correct Attendance" button in the attendance summary page.
 *
 *  Abdullah Saud    (sessions 0,2,4) — joined under a personal Gmail
 *  Rana Omar        (sessions 1,3)   — joined under a work email alias
 *  Zoom Guest       (session 0 only) — Zoom auto-generated guest account
 *  محمد العمري       (all sessions)   — high-attender, unrecognised corp email
 */
class GuestAttendanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $group = CourseGroup::query()
            ->whereHas('course', fn ($q) => $q->where('slug', 'attendance-demo-course'))
            ->first();

        if (! $group) {
            $this->command?->error('Demo group not found. Run AttendanceDemoSeeder first.');

            return;
        }

        $sessions = $group->sessions()
            ->ended()
            ->orderBy('start_at')
            ->get();

        if ($sessions->isEmpty()) {
            $this->command?->error('No ended sessions found on the demo group.');

            return;
        }

        $guests = [
            [
                'name'     => 'Abdullah Saud',
                'email'    => 'abdullahsaud88@gmail.com',
                'indexes'  => [0, 2, 4],
            ],
            [
                'name'     => 'Rana Omar',
                'email'    => 'rana.omar.work@hotmail.com',
                'indexes'  => [1, 3],
            ],
            [
                'name'     => 'Zoom Guest',
                'email'    => '',
                'indexes'  => [0],
            ],
            [
                'name'     => 'محمد العمري',
                'email'    => 'm.omari@corp-external.sa',
                'indexes'  => [0, 1, 2, 3, 4],
            ],
        ];

        $created = 0;

        foreach ($guests as $guest) {
            // Remove stale rows so the seeder is safely re-runnable
            ZoomSessionAttendance::query()
                ->whereNull('enrollment_id')
                ->where('participant_name', $guest['name'])
                ->whereIn('course_group_session_id', $sessions->pluck('id'))
                ->delete();

            foreach ($guest['indexes'] as $idx) {
                $session = $sessions->get($idx);

                if (! $session) {
                    continue;
                }

                $joinedAt = $session->start_at->copy()->addMinutes(rand(0, 10));
                $leftAt   = $joinedAt->copy()->addMinutes($session->duration_minutes - rand(0, 8));

                ZoomSessionAttendance::create([
                    'course_group_session_id' => $session->id,
                    'user_id'                 => null,
                    'enrollment_id'           => null,
                    'zoom_meeting_id'         => $session->zoom_meeting_id ?? ('demo-' . $session->id),
                    'participant_name'        => $guest['name'],
                    'participant_email'       => $guest['email'],
                    'joined_at'               => $joinedAt,
                    'left_at'                 => $leftAt,
                    'duration_minutes'        => (int) $joinedAt->diffInMinutes($leftAt),
                    'raw_payload'             => [],
                ]);

                $created++;
            }
        }

        $this->command?->info('GuestAttendanceDemoSeeder done.');
        $this->command?->info("  Group ID       : {$group->id}");
        $this->command?->info("  Guest records  : {$created}");
    }
}
