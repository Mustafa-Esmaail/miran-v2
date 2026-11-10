<?php

namespace App\Services\Admin;

use App\Filament\Admin\Resources\BankTransferReceipts\BankTransferReceiptResource;
use App\Filament\Admin\Resources\Courses\CourseResource;
use App\Filament\Admin\Resources\Enrollments\EnrollmentResource;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Filament\Admin\Resources\Permissions\PermissionResource;
use App\Filament\Admin\Resources\Roles\RoleResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Pages\Settings;
use App\Models\User;
use App\Repositories\Contracts\BankTransferReceiptRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;

class DashboardService
{
    public function __construct(
        private readonly CourseRepositoryInterface $courses,
        private readonly CategoryRepositoryInterface $categories,
        private readonly UserRepositoryInterface $users,
        private readonly OrderRepositoryInterface $orders,
        private readonly PaymentRepositoryInterface $payments,
        private readonly BankTransferReceiptRepositoryInterface $bankReceipts,
    ) {}

    public function statsFor(User $user): array
    {
        $stats = [
            'total_courses' => $this->courses->countForUser($user),
            'total_categories' => $this->categories->countAll(),
        ];

        if ($user->can('users.viewAny')) {
            $stats['total_trainees'] = $this->users->countByRole('student');
        }

        if ($user->can('orders.viewAny')) {
            $stats['orders_today'] = $this->orders->countToday();
            $stats['orders_month'] = $this->orders->countThisMonth();
        }

        if ($user->can('payments.viewAny')) {
            $stats['revenue_month'] = $this->payments->sumPaidThisMonth();
        }

        if ($user->can('bank_receipts.viewAny')) {
            $stats['pending_bank_receipts'] = $this->bankReceipts->countPending();
        }

        return $stats;
    }

    public function quickLinksFor(User $user): array
    {
        $system = [
            ...($user->can('users.viewAny') ? [[
                'label' => __('admin.navigation.users'),
                'url' => UserResource::getUrl('index'),
                'icon' => 'heroicon-o-users',
            ]] : []),
            ...($user->can('roles.viewAny') ? [[
                'label' => __('admin.navigation.roles'),
                'url' => RoleResource::getUrl('index'),
                'icon' => 'heroicon-o-key',
            ]] : []),
            ...($user->can('permissions.viewAny') ? [[
                'label' => __('admin.navigation.permissions'),
                'url' => PermissionResource::getUrl('index'),
                'icon' => 'heroicon-o-shield-check',
            ]] : []),
            ...($user->can('settings.view') ? [[
                'label' => __('admin.navigation.settings'),
                'url' => Settings::getUrl(),
                'icon' => 'heroicon-o-cog-6-tooth',
            ]] : []),
        ];

        $operations = [
            ...($user->can('orders.viewAny') ? [[
                'label' => __('admin.navigation.orders'),
                'url' => OrderResource::getUrl('index'),
                'icon' => 'heroicon-o-shopping-bag',
            ]] : []),
            ...($user->can('payments.viewAny') ? [[
                'label' => __('admin.navigation.payments'),
                'url' => PaymentResource::getUrl('index'),
                'icon' => 'heroicon-o-credit-card',
            ]] : []),
            ...($user->can('bank_receipts.viewAny') ? [[
                'label' => __('admin.navigation.bank_receipts'),
                'url' => BankTransferReceiptResource::getUrl('index'),
                'icon' => 'heroicon-o-banknotes',
            ]] : []),
            ...($user->can('courses.viewAny') ? [[
                'label' => __('admin.navigation.courses'),
                'url' => CourseResource::getUrl('index'),
                'icon' => 'heroicon-o-book-open',
            ]] : []),
            ...($user->can('enrollments.viewAny') ? [[
                'label' => __('admin.navigation.enrollments'),
                'url' => EnrollmentResource::getUrl('index'),
                'icon' => 'heroicon-o-academic-cap',
            ]] : []),
        ];

        return [
            'system' => array_slice($system, 0, 4),
            'operations' => array_slice($operations, 0, 4),
        ];
    }

    public function formatSar(int|float $amount): string
    {
        $formatted = number_format((float) $amount, 2);

        return __('admin.currency', ['amount' => $formatted]);
    }
}
