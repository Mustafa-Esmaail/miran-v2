<?php

namespace App\Filament\Widgets;

use App\Enums\CertificateStatus;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Filament\Admin\Resources\Certificates\CertificateResource;
use App\Filament\Admin\Resources\Courses\CourseResource;
use App\Filament\Admin\Resources\Enrollments\EnrollmentResource;
use App\Filament\Admin\Resources\Students\StudentResource;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class LmsStatsWidget extends BaseStatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected bool $contained = true;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.lms_stats_heading');
    }

    protected function getStats(): array
    {
        $now = CarbonImmutable::now();
        $weekAgo = $now->subDays(7)->startOfDay();
        $monthStart = $now->startOfMonth();

        $totalStudents = User::query()
            ->whereHas('roles', fn (Builder $q) => $q->where('name', 'student'))
            ->count();

        $newStudentsWeek = User::query()
            ->whereHas('roles', fn (Builder $q) => $q->where('name', 'student'))
            ->where('created_at', '>=', $weekAgo)
            ->count();

        $publishedCourses = Course::query()
            ->where('status', CourseStatus::Published)
            ->count();

        $draftCourses = Course::query()
            ->where('status', CourseStatus::Draft)
            ->count();

        $activeEnrollments = Enrollment::query()
            ->where('status', EnrollmentStatus::Active)
            ->count();

        $newEnrollmentsWeek = Enrollment::query()
            ->where('created_at', '>=', $weekAgo)
            ->count();

        $pendingCertificates = Certificate::query()
            ->where('status', CertificateStatus::Pending)
            ->count();

        $approvedThisMonth = Certificate::query()
            ->where('status', CertificateStatus::Approved)
            ->where('approved_at', '>=', $monthStart)
            ->count();

        $totalAttempts = QuizAttempt::query()->count();

        $attemptsThisMonth = QuizAttempt::query()
            ->where('created_at', '>=', $monthStart)
            ->count();

        return [
            Stat::make(__('admin.dashboard_widgets.lms_stats.total_students'), number_format($totalStudents))
                ->description(__('admin.dashboard_widgets.lms_stats.new_this_week', ['count' => $newStudentsWeek]))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-users')
                ->color('primary')
                ->url(StudentResource::getUrl('index')),

            Stat::make(__('admin.dashboard_widgets.lms_stats.published_courses'), number_format($publishedCourses))
                ->description(__('admin.dashboard_widgets.lms_stats.draft_courses', ['count' => $draftCourses]))
                ->descriptionIcon('heroicon-m-pencil')
                ->icon('heroicon-o-book-open')
                ->color('success')
                ->url(CourseResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => CourseStatus::Published->value]],
                ])),

            Stat::make(__('admin.dashboard_widgets.lms_stats.active_enrollments'), number_format($activeEnrollments))
                ->description(__('admin.dashboard_widgets.lms_stats.new_this_week', ['count' => $newEnrollmentsWeek]))
                ->descriptionIcon('heroicon-m-academic-cap')
                ->icon('heroicon-o-academic-cap')
                ->color('info')
                ->url(EnrollmentResource::getUrl('index')),

            Stat::make(__('admin.dashboard_widgets.lms_stats.pending_certificates'), number_format($pendingCertificates))
                ->description(__('admin.dashboard_widgets.lms_stats.issued_this_month', ['count' => $approvedThisMonth]))
                ->descriptionIcon('heroicon-m-trophy')
                ->icon('heroicon-o-trophy')
                ->color($pendingCertificates > 0 ? 'warning' : 'success')
                ->url(CertificateResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => CertificateStatus::Pending->value]],
                ])),

            Stat::make(__('admin.dashboard_widgets.lms_stats.quiz_attempts'), number_format($totalAttempts))
                ->description(__('admin.dashboard_widgets.lms_stats.attempts_this_month', ['count' => $attemptsThisMonth]))
                ->descriptionIcon('heroicon-m-pencil-square')
                ->icon('heroicon-o-pencil-square')
                ->color('info'),
        ];
    }
}
