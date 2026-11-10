<?php

namespace Database\Seeders;

use App\Enums\CourseGroupStatus;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\ZoomSessionAttendance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a demo course group with 5 ended sessions and 10 students
 * distributed across all attendance badge states:
 *
 *  Green  (≥ 60%) — Eligible      : Ahmed, Sara, Omar
 *  Yellow (40–59%) — At Risk       : Fatima, Khalid
 *  Red    (< 40%) — Insufficient   : Nora, Yousef
 *  Red    (0%)    — No Attendance  : Layla, Hassan, Mona
 */
class AttendanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $group = $this->seedCourseAndGroup();

        [$day1, $day2, $day3] = $this->seedSections($group);

        $sessions = $this->seedSessions($group, $day1, $day2, $day3);

        $this->seedStudents($group, $sessions);

        $this->command?->info('AttendanceDemoSeeder done.');
        $this->command?->info('  Course group ID : '.$group->id);
        $this->command?->info('  Sessions seeded : '.$sessions->count());
        $this->command?->info('  Students seeded : 10');
    }

    private function seedCourseAndGroup(): CourseGroup
    {
        $category = Category::updateOrCreate(
            ['slug' => 'attendance-demo'],
            [
                'name' => ['en' => 'Demo Category', 'ar' => 'فئة تجريبية'],
                'is_active' => true,
            ]
        );

        $course = Course::updateOrCreate(
            ['slug' => 'attendance-demo-course'],
            [
                'category_id' => $category->id,
                'title' => ['en' => 'Attendance Demo Course', 'ar' => 'دورة حضور تجريبية'],
                'summary' => ['en' => 'Demo course for attendance page testing.', 'ar' => 'دورة تجريبية لصفحة ملخص الحضور.'],
                'description' => ['en' => 'This course is used to demo attendance tracking.', 'ar' => 'هذه الدورة لعرض تتبع حضور الطلاب.'],
                'status' => CourseStatus::Published,
                'days_count' => 3,
            ]
        );

        return CourseGroup::updateOrCreate(
            ['course_id' => $course->id, 'title->en' => 'Attendance Demo Group'],
            [
                'title' => ['en' => 'Attendance Demo Group', 'ar' => 'مجموعة حضور تجريبية'],
                'status' => CourseGroupStatus::Open,
                'start_at' => now()->subDays(20),
                'end_at' => now()->subDays(1),
            ]
        );
    }

    /** @return array{0: CourseCurriculumSection, 1: CourseCurriculumSection, 2: CourseCurriculumSection} */
    private function seedSections(CourseGroup $group): array
    {
        $make = fn (int $n, string $en, string $ar): CourseCurriculumSection => CourseCurriculumSection::updateOrCreate(
            ['course_id' => $group->course_id, 'course_group_id' => $group->id, 'sort_order' => $n],
            ['title' => ['en' => $en, 'ar' => $ar], 'description' => ['en' => '', 'ar' => '']]
        );

        return [
            $make(1, 'Day 1 — Introduction', 'اليوم الأول — مقدمة'),
            $make(2, 'Day 2 — Core Concepts', 'اليوم الثاني — المفاهيم'),
            $make(3, 'Day 3 — Wrap-up', 'اليوم الثالث — الختام'),
        ];
    }

    /** @return \Illuminate\Support\Collection<int, CourseGroupSession> */
    private function seedSessions(
        CourseGroup $group,
        CourseCurriculumSection $day1,
        CourseCurriculumSection $day2,
        CourseCurriculumSection $day3,
    ): \Illuminate\Support\Collection {
        $definitions = [
            [$day1, 'Opening session',  14, 60],
            [$day1, 'Morning workshop', 13, 90],
            [$day2, 'Core concepts',    10,  60],
            [$day2, 'Group exercise',    9, 120],
            [$day3, 'Final review',      5,  60],
        ];

        return collect($definitions)->map(function (array $def) use ($group): CourseGroupSession {
            [$section, $title, $daysAgo, $duration] = $def;

            return CourseGroupSession::updateOrCreate(
                [
                    'course_group_id' => $group->id,
                    'course_curriculum_section_id' => $section->id,
                    'title' => $title,
                ],
                [
                    'start_at' => now()->subDays($daysAgo)->setTime(10, 0),
                    'duration_minutes' => $duration,
                    'zoom_meeting_id' => 'demo-session-'.$group->id.'-'.$daysAgo,
                ]
            );
        });
    }

    /**
     * @param  \Illuminate\Support\Collection<int, CourseGroupSession>  $sessions
     */
    private function seedStudents(CourseGroup $group, \Illuminate\Support\Collection $sessions): void
    {
        /*
         * sessions index: 0=Opening(60m) 1=Workshop(90m) 2=Concepts(60m) 3=Exercise(120m) 4=Review(60m)
         * Total sessions: 5  |  Total minutes: 390
         *
         * Badge rules: Green ≥60%, Yellow 40-59%, Red <40%
         */
        $students = [
            // Green — Eligible (≥60%)
            ['Ahmed Al-Rashidi',  'ahmed.rashidi@demo.miran',  '+966501234001', [0, 1, 2, 3, 4]], // 5/5 = 100%
            ['Sara Al-Otaibi',    'sara.otaibi@demo.miran',    '+966501234002', [0, 1, 2, 3]],    // 4/5 = 80%
            ['Omar Al-Zahrani',   'omar.zahrani@demo.miran',   '+966501234003', [0, 2, 4]],        // 3/5 = 60%

            // Yellow — At Risk (40–59%)
            ['Fatima Al-Qahtani', 'fatima.qahtani@demo.miran', '+966501234004', [1, 3]],           // 2/5 = 40%
            ['Khalid Al-Ghamdi',  'khalid.ghamdi@demo.miran',  '+966501234005', [0, 2]],           // 2/5 = 40%

            // Red — Insufficient (<40%)
            ['Nora Al-Shamrani',  'nora.shamrani@demo.miran',  '+966501234006', [4]],              // 1/5 = 20%
            ['Yousef Al-Harbi',   'yousef.harbi@demo.miran',   '+966501234007', [1]],              // 1/5 = 20%

            // Red — No Attendance (0%)
            ['Layla Al-Dosari',   'layla.dosari@demo.miran',   '+966501234008', []],               // 0/5 = 0%
            ['Hassan Al-Malki',   'hassan.malki@demo.miran',   '+966501234009', []],               // 0/5 = 0%
            ['Mona Al-Shehri',    'mona.shehri@demo.miran',    '+966501234010', []],               // 0/5 = 0%
        ];

        foreach ($students as [$name, $email, $phone, $attendedIndexes]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'phone' => $phone,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $enrollment = Enrollment::updateOrCreate(
                ['user_id' => $user->id, 'course_group_id' => $group->id],
                [
                    'course_id' => $group->course_id,
                    'status' => EnrollmentStatus::Active,
                    'activated_at' => now()->subDays(20),
                ]
            );

            // Remove old attendance so the seeder is safely re-runnable
            ZoomSessionAttendance::query()
                ->where('enrollment_id', $enrollment->id)
                ->whereIn('course_group_session_id', $sessions->pluck('id'))
                ->delete();

            foreach ($attendedIndexes as $idx) {
                /** @var CourseGroupSession $session */
                $session = $sessions[$idx];
                $joinedAt = $session->start_at->copy()->addMinutes(rand(0, 8));
                $leftAt = $joinedAt->copy()->addMinutes($session->duration_minutes - rand(0, 5));

                ZoomSessionAttendance::create([
                    'course_group_session_id' => $session->id,
                    'user_id' => $user->id,
                    'enrollment_id' => $enrollment->id,
                    'zoom_meeting_id' => $session->zoom_meeting_id ?? ('demo-'.$session->id),
                    'participant_name' => $user->name,
                    'participant_email' => $user->email,
                    'joined_at' => $joinedAt,
                    'left_at' => $leftAt,
                    'duration_minutes' => (int) $joinedAt->diffInMinutes($leftAt),
                    'raw_payload' => [],
                ]);
            }
        }
    }
}
