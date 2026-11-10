<?php

namespace App\Filament\Widgets;

use App\Enums\BankTransferStatus;
use App\Enums\BlogCommentStatus;
use App\Enums\CertificateStatus;
use App\Enums\CourseReviewStatus;
use App\Enums\OrderStatus;
use App\Filament\Admin\Resources\Announcements\AnnouncementResource;
use App\Filament\Admin\Resources\BankTransferReceipts\BankTransferReceiptResource;
use App\Filament\Admin\Resources\BlogComments\BlogCommentResource;
use App\Filament\Admin\Resources\Certificates\CertificateResource;
use App\Filament\Admin\Resources\CourseReviews\CourseReviewResource;
use App\Filament\Admin\Resources\Courses\CourseResource;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Filament\Admin\Resources\ServiceRequests\ServiceRequestResource;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class OpsQuickActionsWidget extends Widget
{
    protected string $view = 'filament.widgets.ops-quick-actions-widget';

    protected int|string|array $columnSpan = 'full';

    public function getActions(): array
    {
        $user = Filament::auth()->user();

        return [
            ...($user?->can('orders.create') ? [[
                'label' => __('admin.dashboard_widgets.quick_actions.create_order'),
                'icon' => 'heroicon-o-shopping-bag',
                'url' => OrderResource::getUrl('create'),
            ]] : []),
            ...($user?->can('courses.create') ? [[
                'label' => __('admin.dashboard_widgets.quick_actions.create_course'),
                'icon' => 'heroicon-o-book-open',
                'url' => CourseResource::getUrl('create'),
            ]] : []),
            ...($user?->can('announcements.create') ? [[
                'label' => __('admin.dashboard_widgets.quick_actions.publish_announcement'),
                'icon' => 'heroicon-o-megaphone',
                'url' => AnnouncementResource::getUrl('create'),
            ]] : []),
            ...($user?->can('bank_receipts.viewAny') ? [[
                'label' => __('admin.dashboard_widgets.quick_actions.review_bank_receipts'),
                'icon' => 'heroicon-o-document-check',
                'url' => BankTransferReceiptResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => BankTransferStatus::Submitted->value]],
                ]),
            ]] : []),
            ...($user?->can('certificates.viewAny') ? [[
                'label' => __('admin.dashboard_widgets.quick_actions.approve_certificates'),
                'icon' => 'heroicon-o-trophy',
                'url' => CertificateResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => CertificateStatus::Pending->value]],
                ]),
            ]] : []),
            ...($user?->can('course_reviews.viewAny') ? [[
                'label' => __('admin.dashboard_widgets.quick_actions.moderate_reviews'),
                'icon' => 'heroicon-o-star',
                'url' => CourseReviewResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => CourseReviewStatus::Pending->value]],
                ]),
            ]] : []),
            ...($user?->can('blog_comments.viewAny') ? [[
                'label' => __('admin.dashboard_widgets.quick_actions.moderate_blog_comments'),
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'url' => BlogCommentResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => BlogCommentStatus::Pending->value]],
                ]),
            ]] : []),
            ...($user?->can('service_requests.viewAny') ? [[
                'label' => __('admin.dashboard_widgets.quick_actions.open_service_requests'),
                'icon' => 'heroicon-o-lifebuoy',
                'url' => ServiceRequestResource::getUrl('index'),
            ]] : []),
            ...($user?->can('orders.viewAny') ? [[
                'label' => __('admin.dashboard_widgets.quick_actions.pending_orders'),
                'icon' => 'heroicon-o-clock',
                'url' => OrderResource::getUrl('index', [
                    'tableFilters' => ['status' => ['value' => OrderStatus::Pending->value]],
                ]),
            ]] : []),
        ];
    }
}
