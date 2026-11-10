<?php

namespace App\Filament\Widgets;

use App\Enums\BankTransferStatus;
use App\Enums\BlogCommentStatus;
use App\Enums\CertificateStatus;
use App\Enums\CourseReviewStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Announcements\AnnouncementResource;
use App\Filament\Admin\Resources\BankTransferReceipts\BankTransferReceiptResource;
use App\Filament\Admin\Resources\BlogComments\BlogCommentResource;
use App\Filament\Admin\Resources\Certificates\CertificateResource;
use App\Filament\Admin\Resources\CourseReviews\CourseReviewResource;
use App\Filament\Admin\Resources\Enrollments\EnrollmentResource;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Models\Announcement;
use App\Models\BankTransferReceipt;
use App\Models\BlogComment;
use App\Models\Certificate;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OpsKpiOverviewWidget extends BaseStatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    protected bool $contained = true;

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.ops_kpi_heading');
    }

    protected function getStats(): array
    {
        $now = CarbonImmutable::now();
        $startOfToday = $now->startOfDay();
        $startOfLast7Days = $now->subDays(6)->startOfDay();

        $pendingReceipts = BankTransferReceipt::query()
            ->where('status', BankTransferStatus::Submitted)
            ->count();

        $failedPayments = Payment::query()
            ->where('status', PaymentStatus::Failed)
            ->count();

        $pendingOrders = Order::query()
            ->where('status', OrderStatus::Pending)
            ->count();

        $paidOrders = Order::query()
            ->where('status', OrderStatus::Paid)
            ->count();

        $revenue7d = (int) Order::query()
            ->where('status', OrderStatus::Paid)
            ->where('created_at', '>=', $startOfLast7Days)
            ->sum('total_amount');

        $discountTotals7d = (int) Order::query()
            ->where('created_at', '>=', $startOfLast7Days)
            ->sum('discount_amount');

        $enrollmentsToday = Enrollment::query()
            ->where('created_at', '>=', $startOfToday)
            ->count();

        $enrollments7d = Enrollment::query()
            ->where('created_at', '>=', $startOfLast7Days)
            ->count();

        $activatedEnrollments7d = Enrollment::query()
            ->whereNotNull('activated_at')
            ->where('activated_at', '>=', $startOfLast7Days)
            ->count();

        $pendingCertificates = Certificate::query()
            ->where('status', CertificateStatus::Pending)
            ->count();

        $pendingCourseReviews = CourseReview::query()
            ->where('status', CourseReviewStatus::Pending)
            ->count();

        $pendingBlogComments = BlogComment::query()
            ->where('status', BlogCommentStatus::Pending)
            ->count();

        $activeAnnouncements = Announcement::query()
            ->where('status', 'published')
            ->active()
            ->count();

        return [
            Stat::make(__('admin.dashboard_widgets.stats.pending_bank_receipts'), number_format($pendingReceipts))
                ->description(__('admin.dashboard_widgets.stats.pending_bank_receipts_desc'))
                ->descriptionIcon('heroicon-m-clock')
                ->icon('heroicon-o-document-check')
                ->color('warning')
                ->url(BankTransferReceiptResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => BankTransferStatus::Submitted->value]],
                ])),
            Stat::make(__('admin.dashboard_widgets.stats.failed_payments'), number_format($failedPayments))
                ->description(__('admin.dashboard_widgets.stats.failed_payments_desc'))
                ->descriptionIcon('heroicon-m-x-circle')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->url(PaymentResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => PaymentStatus::Failed->value]],
                ])),
            Stat::make(__('admin.dashboard_widgets.stats.orders_pending'), number_format($pendingOrders))
                ->description(__('admin.dashboard_widgets.stats.orders_pending_desc'))
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->icon('heroicon-o-shopping-bag')
                ->color('warning')
                ->url(OrderResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => OrderStatus::Pending->value]],
                ])),
            Stat::make(__('admin.dashboard_widgets.stats.orders_paid'), number_format($paidOrders))
                ->description(__('admin.dashboard_widgets.stats.orders_paid_desc'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->url(OrderResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => OrderStatus::Paid->value]],
                ])),
            Stat::make(__('admin.dashboard_widgets.stats.revenue_7d'), $this->formatMinorAmount($revenue7d))
                ->description(__('admin.dashboard_widgets.stats.revenue_7d_desc'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->url(OrderResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => OrderStatus::Paid->value]],
                ])),
            Stat::make(__('admin.dashboard_widgets.stats.discounts_7d'), $this->formatMinorAmount($discountTotals7d))
                ->description(__('admin.dashboard_widgets.stats.discounts_7d_desc'))
                ->descriptionIcon('heroicon-m-ticket')
                ->icon('heroicon-o-ticket')
                ->color('info')
                ->url(OrderResource::getUrl('index')),
            Stat::make(__('admin.dashboard_widgets.stats.enrollments_today'), number_format($enrollmentsToday))
                ->description(__('admin.dashboard_widgets.stats.enrollments_today_desc'))
                ->descriptionIcon('heroicon-m-sparkles')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->url(EnrollmentResource::getUrl('index')),
            Stat::make(__('admin.dashboard_widgets.stats.enrollments_7d'), number_format($enrollments7d))
                ->description(__('admin.dashboard_widgets.stats.enrollments_7d_desc'))
                ->descriptionIcon('heroicon-m-calendar')
                ->icon('heroicon-o-academic-cap')
                ->color('info')
                ->url(EnrollmentResource::getUrl('index')),
            Stat::make(__('admin.dashboard_widgets.stats.activated_7d'), number_format($activatedEnrollments7d))
                ->description(__('admin.dashboard_widgets.stats.activated_7d_desc'))
                ->descriptionIcon('heroicon-m-bolt')
                ->icon('heroicon-o-check')
                ->color('success')
                ->url(EnrollmentResource::getUrl('index')),
            Stat::make(__('admin.dashboard_widgets.stats.certificates_pending'), number_format($pendingCertificates))
                ->description(__('admin.dashboard_widgets.stats.certificates_pending_desc'))
                ->descriptionIcon('heroicon-m-trophy')
                ->icon('heroicon-o-trophy')
                ->color('warning')
                ->url(CertificateResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => CertificateStatus::Pending->value]],
                ])),
            Stat::make(__('admin.dashboard_widgets.stats.reviews_pending'), number_format($pendingCourseReviews))
                ->description(__('admin.dashboard_widgets.stats.reviews_pending_desc'))
                ->descriptionIcon('heroicon-m-star')
                ->icon('heroicon-o-star')
                ->color('warning')
                ->url(CourseReviewResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => CourseReviewStatus::Pending->value]],
                ])),
            Stat::make(__('admin.dashboard_widgets.stats.blog_comments_pending'), number_format($pendingBlogComments))
                ->description(__('admin.dashboard_widgets.stats.blog_comments_pending_desc'))
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('warning')
                ->url(BlogCommentResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => BlogCommentStatus::Pending->value]],
                ])),
            Stat::make(__('admin.dashboard_widgets.stats.announcements_active'), number_format($activeAnnouncements))
                ->description(__('admin.dashboard_widgets.stats.announcements_active_desc'))
                ->descriptionIcon('heroicon-m-megaphone')
                ->icon('heroicon-o-megaphone')
                ->color('primary')
                ->url(AnnouncementResource::getUrl('index')),
        ];
    }

    protected function formatMinorAmount(int|float $amount): string
    {
        return 'SAR '.number_format((float) $amount, 2);
    }
}
