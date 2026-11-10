<?php

namespace App\Livewire;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class DashboardMyCourses extends Component
{
    public function render(): View
    {
        $enrollments = Enrollment::query()
            ->where('user_id', auth()->id())
            ->where('status', EnrollmentStatus::Active)
            ->with(['course', 'courseGroup'])
            ->get();

        $courseIds = $enrollments->pluck('course_id')->filter()->unique()->values();
        $lessonTotals = Lesson::query()
            ->select('course_id', DB::raw('count(*) as total'))
            ->whereIn('course_id', $courseIds)
            ->groupBy('course_id')
            ->pluck('total', 'course_id');

        $completedTotals = LessonProgress::query()
            ->select('lessons.course_id', DB::raw('count(*) as completed'))
            ->join('lessons', 'lessons.id', '=', 'lesson_progress.lesson_id')
            ->where('lesson_progress.user_id', auth()->id())
            ->whereIn('lessons.course_id', $courseIds)
            ->groupBy('lessons.course_id')
            ->pluck('completed', 'lessons.course_id');

        $enrollments = $enrollments->map(function ($enrollment) use ($lessonTotals, $completedTotals) {
            $courseId = $enrollment->course_id;
            $totalLessons = (int) ($lessonTotals[$courseId] ?? 0);
            $completedLessons = (int) ($completedTotals[$courseId] ?? 0);
            $progress = $totalLessons > 0 ? (int) round(($completedLessons / $totalLessons) * 100) : 0;
            $course = $enrollment->course;
            $thumbnail = $course?->image_url ?? null;

            return [
                'course_title' => $course?->getTranslation('title', app()->getLocale()) ?? '',
                'course_slug' => $course?->slug ?? '',
                'group_id' => $enrollment->courseGroup?->id,
                'group_title' => $enrollment->courseGroup?->getTranslation('title', app()->getLocale()) ?? '',
                'status' => $enrollment->status->value,
                'progress' => $progress,
                'thumbnail_url' => $thumbnail,
                'enrolled_at' => ($enrollment->activated_at ?? $enrollment->created_at)?->format('d/m/Y'),
                'is_invited' => $enrollment->is_invited,
            ];
        });

        return view('livewire.dashboard-my-courses', ['enrollments' => $enrollments])
            ->layout('layouts.app');
    }
}
