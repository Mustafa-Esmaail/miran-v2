<?php

namespace App\Services;

use App\Enums\CrmActivityType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Exceptions\DuplicatePurchaseException;
use App\Models\Cart;
use App\Models\CrmActivity;
use App\Models\Employee;
use App\Models\Order;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\CourseGroupRepositoryInterface;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\Settings\SettingsService;
use App\Support\Purchasables\PurchasableResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutService
{
    public function __construct(
        private readonly CartRepositoryInterface $carts,
        private readonly CourseGroupRepositoryInterface $groups,
        private readonly EnrollmentRepositoryInterface $enrollments,
        private readonly OrderRepositoryInterface $orders,
        private readonly PricingService $pricing,
        private readonly PurchasableResolver $purchasables,
        private readonly OrderSeatService $seats,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param  array<int, array<int, string>>  $seatAssignments  item ID → phones
     * @param  array<int, string>  $inviteEmails  cart item ID → invitee email (for gifting duplicates)
     * @param  array<int, string>  $invitePhones  cart item ID → invitee phone (optional, for gifting duplicates)
     * @param  array<int, array<int, string>>  $seatEmails  item ID → emails parallel to seatAssignments
     * @param  array<int, int>  $confirmedDuplicateItemIds  cart item IDs the buyer explicitly confirmed to purchase despite already owning the course
     */
    public function createOrderFromCart(int $userId, array $seatAssignments = [], array $inviteEmails = [], array $invitePhones = [], array $seatEmails = [], ?string $customerName = null, ?string $customerPhone = null, array $confirmedDuplicateItemIds = []): ?Order
    {
        $cart = $this->carts->getCartWithItems($userId);
        if (! $cart || $cart->items->isEmpty()) {
            return null;
        }

        $this->validateCartGroups($userId, $cart, $inviteEmails, $confirmedDuplicateItemIds);
        $totals = $this->pricing->calculateTotals($cart);
        $referredByEmployeeId = $this->resolveReferredEmployeeId();

        $orderData = [
            'user_id' => $userId,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'order_no' => $this->generateOrderNo(),
            'status' => OrderStatus::Pending,
            'currency' => config('lms.currency'),
            'subtotal_amount' => $totals->subtotalAmount,
            'discount_amount' => $totals->discountAmount,
            'total_amount' => $totals->totalAmount,
            'coupon_id' => $cart->coupon_id,
            'referred_by_employee_id' => $referredByEmployeeId,
            'marketing_source' => session('marketing_source'),
            'source' => $this->resolveOrderSource($referredByEmployeeId)->value,
        ];

        $items = $cart->items->map(function ($item) use ($orderData) {
            $purchasable = $item->purchasable;
            $metadata = [
                'type_label' => $purchasable ? $this->purchasables->getTypeLabel($purchasable) : 'unknown',
                'subtitle' => $purchasable ? $this->purchasables->getSubtitle($purchasable) : '',
                'image_path' => $purchasable ? $this->purchasables->getImagePath($purchasable) : null,
            ];

            if ($purchasable instanceof \App\Models\CourseGroup) {
                $metadata['course_group_id'] = $purchasable->id;
                $metadata['course_id'] = $purchasable->course_id;
                $metadata['course_slug'] = $purchasable->course?->slug;
            }

            if ($purchasable instanceof \App\Models\Bundle) {
                $courseCount = $purchasable->relationLoaded('courses')
                    ? $purchasable->courses->count()
                    : $purchasable->courses()->count();
                $metadata['bundle_id'] = $purchasable->id;
                $metadata['bundle_course_count'] = $courseCount;

                $selectedGroups = data_get($item->selection_snapshot, 'bundle_course_groups', []);
                if (is_array($selectedGroups)) {
                    $normalizedSelection = collect($selectedGroups)
                        ->mapWithKeys(fn ($groupId, $courseId): array => [(int) $courseId => (int) $groupId])
                        ->filter(fn (int $groupId, int $courseId): bool => $courseId > 0 && $groupId > 0)
                        ->all();

                    if ($normalizedSelection !== []) {
                        $metadata['selected_course_groups'] = $normalizedSelection;
                        $metadata['selected_course_group_ids'] = array_values(array_unique(array_values($normalizedSelection)));
                    }
                }
            }

            return [
                'purchasable_type' => $item->purchasable_type,
                'purchasable_id' => $item->purchasable_id,
                'title_snapshot' => $purchasable ? $this->purchasables->getDisplayLabel($purchasable) : '',
                'price_amount' => $item->price_amount,
                'currency' => $orderData['currency'],
                'metadata_snapshot' => $metadata,
                'quantity' => $item->quantity,
            ];
        })->all();

        return DB::transaction(function () use ($orderData, $items, $cart, $seatAssignments, $inviteEmails, $invitePhones, $seatEmails) {
            $order = $this->orders->createOrder($orderData, $items);
            $this->seats->createSeatsForOrder($order, $cart, $seatAssignments, $inviteEmails, $invitePhones, $seatEmails);

            if ($order->referred_by_employee_id) {
                CrmActivity::create([
                    'entity_type' => Order::class,
                    'entity_id' => $order->id,
                    'type' => CrmActivityType::Note,
                    'subject' => 'Order attributed via referral link',
                    'body' => "Order #{$order->order_no} — {$order->total_amount} {$order->currency} — placed by customer after visiting via this employee's referral link.",
                    'employee_id' => $order->referred_by_employee_id,
                    'created_at' => now(),
                ]);
            }

            Log::channel('lms')->info('order.created', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'user_id' => $order->user_id,
                'total_amount' => $order->total_amount,
                'currency' => $order->currency,
                'items_count' => count($items),
            ]);

            $this->notifications->notifyOrderPlaced($order);

            return $order;
        });
    }

    public function getAvailablePaymentMethods(SettingsService $settings, bool $isFreeOrder = false): array
    {
        if ($isFreeOrder) {
            return [
                'free' => __('web.checkout.free'),
            ];
        }

        $methods = [];
        if ($settings->paymentsEnabled()) {
            if ($settings->paymentProviderIsReady('moyasar')) {
                $methods['moyasar'] = __('web.checkout.moyasar');
            }
            if ($settings->paymentProviderIsReady('tamara')) {
                $methods['tamara'] = __('web.checkout.tamara');
            }
            if ($settings->paymentProviderIsReady('tabby')) {
                $methods['tabby'] = __('web.checkout.tabby');
            }
        }

        if ($settings->getValue('payments.enable_cash', true)) {
            $methods['cash'] = __('web.checkout.cash');
        }
        if ($settings->getValue('payments.enable_bank_transfer', true)) {
            $methods['bank_transfer'] = __('web.checkout.bank_transfer');
        }

        return $methods;
    }

    /**
     * @param  array<int, string>  $inviteEmails
     * @param  array<int, int>  $confirmedDuplicateItemIds
     */
    private function validateCartGroups(int $userId, Cart $cart, array $inviteEmails = [], array $confirmedDuplicateItemIds = []): void
    {
        $duplicates = [];

        foreach ($cart->items as $item) {
            if ($item->purchasable_type !== \App\Models\CourseGroup::class) {
                continue;
            }

            if (! $this->groups->availableForEnrollment($item->purchasable_id)) {
                throw new \RuntimeException(__('web.course.group_unavailable'));
            }

            if (config('lms.duplicate_purchase_policy') !== 'prevent') {
                continue;
            }

            $ownsSameGroup = $this->enrollments->existsForUserCourseGroup($userId, $item->purchasable_id);
            $ownsSameCourse = $ownsSameGroup || $this->enrollments->existsForUserCourse($userId, $item->purchasable->course_id);

            if (! $ownsSameCourse) {
                continue;
            }

            if ($ownsSameGroup) {
                $email = $inviteEmails[$item->id] ?? '';
                if ($email !== '') {
                    continue;
                }

                $duplicates[] = ['cartItemId' => $item->id, 'type' => 'same_group'];

                continue;
            }

            if (in_array($item->id, $confirmedDuplicateItemIds, true)) {
                continue;
            }

            $duplicates[] = ['cartItemId' => $item->id, 'type' => 'different_group'];
        }

        if ($duplicates !== []) {
            throw new DuplicatePurchaseException($duplicates);
        }
    }

    private function generateOrderNo(): string
    {
        return 'ORD-'.now()->format('YmdHis').'-'.random_int(1000, 9999);
    }

    /**
     * The referring employee id is captured into the session once and kept for the
     * rest of that session (see CaptureReferral middleware). If that employee was
     * since deactivated or deleted, drop the stale reference instead of letting the
     * order insert fail on the foreign key constraint.
     */
    private function resolveReferredEmployeeId(): ?int
    {
        $employeeId = session('referral_employee_id');
        if (! $employeeId) {
            return null;
        }

        $exists = Employee::query()->whereKey($employeeId)->where('is_active', true)->exists();
        if (! $exists) {
            session()->forget('referral_employee_id');

            return null;
        }

        return (int) $employeeId;
    }

    /**
     * Site: no `source` query param was ever captured for this session.
     * Sales: the captured `source` matched an active employee code.
     * Store: a `source` param was captured but matched no employee.
     */
    private function resolveOrderSource(?int $referredByEmployeeId): OrderSource
    {
        if ($referredByEmployeeId !== null) {
            return OrderSource::Sales;
        }

        return session('marketing_source') ? OrderSource::Store : OrderSource::Site;
    }
}
