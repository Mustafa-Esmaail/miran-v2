<?php

namespace App\Services;

use App\Enums\OrderItemSeatStatus;
use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemSeat;
use App\Models\User;
use App\Notifications\StudentCredentialsNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

class OrderSeatService
{
    public function __construct(
        private readonly EnrollmentService $enrollments,
        private readonly PricingService $pricing,
    ) {}

    /**
     * @param  array<int, array<int, string>>  $seatAssignments  item ID → phones
     * @param  array<int, string>  $inviteEmails  cart item ID → invitee email (gifted seat)
     * @param  array<int, string>  $invitePhones  cart item ID → invitee phone (optional, gifted seat)
     * @param  array<int, array<int, string>>  $seatEmails  item ID → emails parallel to seatAssignments
     */
    public function createSeatsForOrder(Order $order, Cart $cart, array $seatAssignments = [], array $inviteEmails = [], array $invitePhones = [], array $seatEmails = []): void
    {
        $order->loadMissing('items.purchasable', 'user');
        $cart->loadMissing('items.purchasable');

        $purchaserName = $order->user?->name;

        $orderItems = $order->items->keyBy(function (OrderItem $item) {
            return $this->purchasableKey($item->purchasable_type, $item->purchasable_id);
        });

        foreach ($cart->items as $cartItem) {
            $orderItem = $orderItems->get($this->purchasableKey($cartItem->purchasable_type, $cartItem->purchasable_id));
            if (! $orderItem) {
                continue;
            }

            if ((int) $orderItem->quantity === 1) {
                $inviteEmail = $inviteEmails[$cartItem->id] ?? '';
                $invitedUserId = $order->user_id;

                if ($inviteEmail !== '') {
                    $invitePhone = $invitePhones[$cartItem->id] ?? null;
                    $invitee = $this->findOrCreateUserByEmail(
                        $inviteEmail,
                        $orderItem->title_snapshot,
                        data_get($orderItem->metadata_snapshot, 'image_path'),
                        $purchaserName,
                        $invitePhone !== '' ? $invitePhone : null,
                    );
                    $invitedUserId = $invitee->id;
                }

                $orderItem->seats()->create([
                    'order_id' => $order->id,
                    'course_id' => $this->resolveCourseId($orderItem),
                    'course_group_id' => $this->resolveCourseGroupId($orderItem),
                    'purchaser_user_id' => $order->user_id,
                    'invited_user_id' => $invitedUserId,
                    'invited_phone' => null,
                    'status' => OrderItemSeatStatus::Invited,
                ]);

                continue;
            }

            // Reserve one seat for the purchaser when quantity is greater than 1.
            $orderItem->seats()->create([
                'order_id' => $order->id,
                'course_id' => $this->resolveCourseId($orderItem),
                'course_group_id' => $this->resolveCourseGroupId($orderItem),
                'purchaser_user_id' => $order->user_id,
                'invited_user_id' => $order->user_id,
                'invited_phone' => null,
                'status' => OrderItemSeatStatus::Invited,
            ]);

            $rawPhones = array_values($seatAssignments[$cartItem->id] ?? []);
            $rawEmails = array_values($seatEmails[$cartItem->id] ?? []);
            $phones = $this->normalizePhones($rawPhones);
            $inviteableSeats = max(0, (int) $orderItem->quantity - 1);
            if (count($phones) > $inviteableSeats) {
                $phones = array_slice($phones, 0, $inviteableSeats);
            }

            $assignedCount = 0;
            foreach ($phones as $index => $phone) {
                $emailForSeat = $this->normalizeEmail($rawEmails[$index] ?? null);
                $user = $this->findOrCreateUserByPhone(
                    $phone,
                    null,
                    $emailForSeat,
                    $orderItem->title_snapshot,
                    data_get($orderItem->metadata_snapshot, 'image_path'),
                    $purchaserName,
                );

                $orderItem->seats()->create([
                    'order_id' => $order->id,
                    'course_id' => $this->resolveCourseId($orderItem),
                    'course_group_id' => $this->resolveCourseGroupId($orderItem),
                    'purchaser_user_id' => $order->user_id,
                    'invited_user_id' => $user->id,
                    'invited_phone' => $phone,
                    'status' => OrderItemSeatStatus::Invited,
                ]);
                $assignedCount++;
            }

            $remaining = max(0, $inviteableSeats - $assignedCount);
            if ($remaining === 0) {
                continue;
            }

            $seats = [];
            for ($i = 0; $i < $remaining; $i++) {
                $seats[] = [
                    'order_id' => $order->id,
                    'order_item_id' => $orderItem->id,
                    'course_id' => $this->resolveCourseId($orderItem),
                    'course_group_id' => $this->resolveCourseGroupId($orderItem),
                    'purchaser_user_id' => $order->user_id,
                    'invited_user_id' => null,
                    'invited_phone' => null,
                    'status' => OrderItemSeatStatus::Unassigned,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $orderItem->seats()->insert($seats);
        }
    }

    public function assignSeatToPhone(
        OrderItem $orderItem,
        string $phone,
        ?string $name = null,
        ?string $email = null
    ): OrderItemSeat {
        $normalizedPhone = $this->normalizePhone($phone);
        if ($normalizedPhone === '') {
            throw new RuntimeException(__('web.checkout.invite_phone_required'));
        }

        $existing = $orderItem->seats()
            ->where('invited_phone', $normalizedPhone)
            ->whereIn('status', [OrderItemSeatStatus::Invited, OrderItemSeatStatus::Enrolled])
            ->exists();

        if ($existing) {
            throw new RuntimeException(__('web.checkout.invite_duplicate_phone'));
        }

        $normalizedEmail = $this->normalizeEmail($email);
        if ($normalizedEmail !== null) {
            $existingUserByEmail = User::query()->where('email', $normalizedEmail)->first();
            if ($existingUserByEmail) {
                $emailAlreadyInvited = $orderItem->seats()
                    ->where('invited_user_id', $existingUserByEmail->id)
                    ->whereIn('status', [OrderItemSeatStatus::Invited, OrderItemSeatStatus::Enrolled])
                    ->exists();

                if ($emailAlreadyInvited) {
                    throw new RuntimeException(__('web.checkout.invite_duplicate_email'));
                }
            }
        }

        $seat = $orderItem->seats()
            ->where('status', OrderItemSeatStatus::Unassigned)
            ->first();

        if (! $seat) {
            throw new RuntimeException(__('web.checkout.invite_limit_reached'));
        }

        $orderItem->loadMissing('order.user');
        $purchaserName = $orderItem->order?->user?->name;

        $user = $this->findOrCreateUserByPhone(
            $normalizedPhone,
            $name,
            $email,
            $orderItem->title_snapshot,
            data_get($orderItem->metadata_snapshot, 'image_path'),
            $purchaserName,
        );

        $seat->update([
            'invited_user_id' => $user->id,
            'invited_phone' => $normalizedPhone,
            'status' => OrderItemSeatStatus::Invited,
        ]);

        if ($orderItem->order?->status === OrderStatus::Paid) {
            $this->activateSeat($seat);
        }

        return $seat;
    }

    public function updateSeatAssignment(
        OrderItemSeat $seat,
        string $phone,
        ?string $name = null,
        ?string $email = null
    ): OrderItemSeat {
        $normalizedPhone = $this->normalizePhone($phone);
        if ($normalizedPhone === '') {
            throw new RuntimeException(__('web.checkout.invite_phone_required'));
        }

        $seat->loadMissing('orderItem.order.user');
        $orderItem = $seat->orderItem;

        if (! $orderItem) {
            throw new RuntimeException(__('web.checkout.edit_unavailable'));
        }

        $phoneChanged = $normalizedPhone !== $seat->invited_phone;
        if ($phoneChanged) {
            $phoneTaken = $orderItem->seats()
                ->where('id', '!=', $seat->id)
                ->where('invited_phone', $normalizedPhone)
                ->whereIn('status', [OrderItemSeatStatus::Invited, OrderItemSeatStatus::Enrolled])
                ->exists();

            if ($phoneTaken) {
                throw new RuntimeException(__('web.checkout.invite_duplicate_phone'));
            }
        }

        $normalizedEmail = $this->normalizeEmail($email);
        if ($normalizedEmail !== null) {
            $existingUserByEmail = User::query()->where('email', $normalizedEmail)->first();
            if ($existingUserByEmail && $existingUserByEmail->id !== $seat->invited_user_id) {
                $emailTaken = $orderItem->seats()
                    ->where('id', '!=', $seat->id)
                    ->where('invited_user_id', $existingUserByEmail->id)
                    ->whereIn('status', [OrderItemSeatStatus::Invited, OrderItemSeatStatus::Enrolled])
                    ->exists();

                if ($emailTaken) {
                    throw new RuntimeException(__('web.checkout.invite_duplicate_email'));
                }
            }
        }

        $purchaserName = $orderItem->order?->user?->name;

        $user = $this->findOrCreateUserByPhone(
            $normalizedPhone,
            $name,
            $email,
            $orderItem->title_snapshot,
            data_get($orderItem->metadata_snapshot, 'image_path'),
            $purchaserName,
        );

        $seat->update([
            'invited_user_id' => $user->id,
            'invited_phone' => $normalizedPhone,
            'status' => OrderItemSeatStatus::Invited,
        ]);

        return $seat;
    }

    public function updateOrderItemQuantity(OrderItem $orderItem, int $quantity): void
    {
        $quantity = max(1, $quantity);

        $orderItem->loadMissing('seats', 'order');

        $assignedInvites = $orderItem->seats
            ->whereIn('status', [OrderItemSeatStatus::Invited, OrderItemSeatStatus::Enrolled])
            ->where('invited_user_id', '!=', $orderItem->order?->user_id)
            ->count();

        if (($quantity - 1) < $assignedInvites) {
            throw new RuntimeException(__('web.checkout.invite_remove_extra'));
        }

        $currentSeatCount = $orderItem->seats->count();

        DB::transaction(function () use ($orderItem, $quantity, $currentSeatCount) {
            $orderItem->update(['quantity' => $quantity]);

            if ($quantity > $currentSeatCount) {
                $additional = $quantity - $currentSeatCount;
                $rows = [];
                for ($i = 0; $i < $additional; $i++) {
                    $rows[] = [
                        'order_id' => $orderItem->order_id,
                        'order_item_id' => $orderItem->id,
                        'course_id' => $this->resolveCourseId($orderItem),
                        'course_group_id' => $this->resolveCourseGroupId($orderItem),
                        'purchaser_user_id' => $orderItem->order?->user_id,
                        'invited_user_id' => null,
                        'invited_phone' => null,
                        'status' => OrderItemSeatStatus::Unassigned,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $orderItem->seats()->insert($rows);
            }

            if ($quantity < $currentSeatCount) {
                $removeCount = $currentSeatCount - $quantity;
                $seatIds = $orderItem->seats()
                    ->where('status', OrderItemSeatStatus::Unassigned)
                    ->limit($removeCount)
                    ->pluck('id')
                    ->all();

                if (count($seatIds) < $removeCount) {
                    throw new RuntimeException(__('web.checkout.invite_remove_extra'));
                }

                OrderItemSeat::query()->whereIn('id', $seatIds)->delete();
            }
        });

        if ($orderItem->order) {
            $this->recalculateOrderTotals($orderItem->order);
        }
    }

    public function activateSeatsForOrder(Order $order): void
    {
        $order->loadMissing('items.seats', 'items.purchasable');
        $totalSeats = $order->items->sum(fn (OrderItem $item) => $item->seats->count());

        if ($totalSeats === 0) {
            $this->enrollments->activateForOrder($order);

            return;
        }

        foreach ($order->items as $item) {
            foreach ($item->seats as $seat) {
                if ($seat->status !== OrderItemSeatStatus::Invited) {
                    continue;
                }

                if (! $seat->invited_user_id) {
                    continue;
                }

                $this->activateSeat($seat);
            }
        }
    }

    public function cancelSeatsForOrder(Order $order): void
    {
        OrderItemSeat::query()
            ->where('order_id', $order->id)
            ->where('status', '!=', OrderItemSeatStatus::Canceled)
            ->update(['status' => OrderItemSeatStatus::Canceled]);
    }

    private function activateSeat(OrderItemSeat $seat): void
    {
        $seat->loadMissing('orderItem.purchasable');
        $orderItem = $seat->orderItem;

        if (! $orderItem || ! $seat->invited_user_id) {
            return;
        }

        if ($orderItem->purchasable instanceof \App\Models\Bundle) {
            $orderItem->loadMissing('purchasable.courses');
        }

        $isInvited = $seat->invited_user_id !== $seat->purchaser_user_id;
        $this->enrollments->activateForItem($orderItem, $seat->invited_user_id, $isInvited);
        $seat->update(['status' => OrderItemSeatStatus::Enrolled]);
    }

    private function recalculateOrderTotals(Order $order): void
    {
        $order->loadMissing('items', 'coupon');
        $totals = $this->pricing->calculateOrderTotals($order);

        $order->update([
            'subtotal_amount' => $totals['subtotal'],
            'discount_amount' => $totals['discount'],
            'total_amount' => $totals['total'],
        ]);
    }

    private function purchasableKey(string $type, int $id): string
    {
        return $type.':'.$id;
    }

    /**
     * @param  array<int, string>  $phones
     * @return array<int, string>
     */
    private function normalizePhones(array $phones): array
    {
        $normalized = array_filter(array_map([$this, 'normalizePhone'], $phones));

        return array_values(array_unique($normalized));
    }

    private function normalizePhone(string $phone): string
    {
        return trim($phone);
    }

    private function findOrCreateUserByPhone(
        string $phone,
        ?string $name = null,
        ?string $email = null,
        ?string $courseTitle = null,
        ?string $courseImageUrl = null,
        ?string $purchaserName = null,
    ): User {
        $normalizedEmail = $this->normalizeEmail($email);

        $existingByPhone = User::query()->where('phone', $phone)->first();
        if ($existingByPhone) {
            $this->sendInviteNotification($existingByPhone, false, null, $courseTitle, $courseImageUrl, $purchaserName, 'seat_assign.invite_failed');

            return $existingByPhone;
        }

        if ($normalizedEmail !== null) {
            $existingByEmail = User::query()->where('email', $normalizedEmail)->first();
            if ($existingByEmail) {
                if (blank($existingByEmail->phone)) {
                    $existingByEmail->update(['phone' => $phone]);
                }

                $this->sendInviteNotification($existingByEmail, false, null, $courseTitle, $courseImageUrl, $purchaserName, 'seat_assign.invite_failed');

                return $existingByEmail;
            }
        }

        $displayName = $name !== null && $name !== '' ? $name : __('web.account.guest');

        $hasRealEmail = $normalizedEmail !== null;
        $emailValue = $normalizedEmail ?? $this->generateUniqueInviteEmail($phone);
        $plainPassword = $hasRealEmail ? Str::random(10) : null;

        $user = User::query()->create([
            'name' => $displayName,
            'email' => $emailValue,
            'password' => Hash::make($plainPassword ?? Str::random(32)),
            'phone' => $phone,
            'status' => User::STATUS_ACTIVE,
        ]);

        $user->assignRole(Role::findOrCreate('student', 'web'));

        if ($hasRealEmail) {
            $this->sendInviteNotification($user, true, $plainPassword, $courseTitle, $courseImageUrl, $purchaserName, 'seat_assign.credentials_failed');
        }

        return $user;
    }

    private function sendInviteNotification(
        User $user,
        bool $isNewAccount,
        ?string $plainPassword,
        ?string $courseTitle,
        ?string $courseImageUrl,
        ?string $purchaserName,
        string $logKey,
    ): void {
        $hasRealEmail = ! str_ends_with($user->email, '@example.test');
        if (! $hasRealEmail) {
            return;
        }

        try {
            $user->notify(new StudentCredentialsNotification(
                plainPassword: $plainPassword,
                isNewAccount: $isNewAccount,
                courseTitle: $courseTitle,
                courseImageUrl: $courseImageUrl,
                enrollmentDate: now()->format('Y-m-d H:i'),
                invitedBy: $purchaserName,
            ));
        } catch (\Throwable $e) {
            Log::channel('lms')->warning($logKey, [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $normalized = Str::lower(trim($email));

        return $normalized !== '' ? $normalized : null;
    }

    private function findOrCreateUserByEmail(
        string $email,
        ?string $courseTitle = null,
        ?string $courseImageUrl = null,
        ?string $purchaserName = null,
        ?string $phone = null,
    ): User {
        $normalized = Str::lower(trim($email));

        $existing = User::query()->where('email', $normalized)->first();
        if ($existing) {
            if ($phone !== null && blank($existing->phone)) {
                $existing->update(['phone' => $phone]);
            }

            try {
                $existing->notify(new StudentCredentialsNotification(
                    plainPassword: null,
                    isNewAccount: false,
                    courseTitle: $courseTitle,
                    courseImageUrl: $courseImageUrl,
                    enrollmentDate: now()->format('Y-m-d H:i'),
                    invitedBy: $purchaserName,
                ));
            } catch (\Throwable $e) {
                Log::channel('lms')->warning('gift_seat.invite_email_failed', [
                    'email' => $normalized,
                    'error' => $e->getMessage(),
                ]);
            }

            return $existing;
        }

        $plainPassword = Str::random(10);

        $user = User::query()->create([
            'name' => __('web.account.guest'),
            'email' => $normalized,
            'phone' => $phone,
            'password' => Hash::make($plainPassword),
            'status' => User::STATUS_ACTIVE,
        ]);

        $user->assignRole(Role::findOrCreate('student', 'web'));

        try {
            $user->notify(new StudentCredentialsNotification(
                plainPassword: $plainPassword,
                isNewAccount: true,
                courseTitle: $courseTitle,
                courseImageUrl: $courseImageUrl,
                enrollmentDate: now()->format('Y-m-d H:i'),
                invitedBy: $purchaserName,
            ));
        } catch (\Throwable $e) {
            Log::channel('lms')->warning('gift_seat.credentials_email_failed', [
                'email' => $normalized,
                'error' => $e->getMessage(),
            ]);
        }

        return $user;
    }

    private function generateUniqueInviteEmail(string $phone): string
    {
        $slug = Str::of($phone)->lower()->replaceMatches('/[^a-z0-9]/', '')->value();
        if ($slug === '') {
            $slug = Str::lower(Str::random(12));
        }

        $candidate = 'invitee+'.$slug.'@example.test';
        $counter = 1;

        while (User::query()->where('email', $candidate)->exists()) {
            $candidate = 'invitee+'.$slug.$counter.'@example.test';
            $counter++;
        }

        return $candidate;
    }

    /**
     * Reserve one seat for the purchaser (activated immediately, since there is no
     * pending-to-paid transition left to trigger activation) and leave the rest of
     * the quantity as unassigned seats for later invitation, matching the shape
     * checkout produces via createSeatsForOrder().
     */
    public function createSeatsForItem(OrderItem $item, int $quantity, int $purchaserUserId): void
    {
        $item->loadMissing('purchasable');

        $reservedSeat = $item->seats()->create([
            'order_id' => $item->order_id,
            'course_id' => $this->resolveCourseId($item),
            'course_group_id' => $this->resolveCourseGroupId($item),
            'purchaser_user_id' => $purchaserUserId,
            'invited_user_id' => $purchaserUserId,
            'invited_phone' => null,
            'status' => OrderItemSeatStatus::Invited,
        ]);

        $this->enrollments->activateForItem($item, $purchaserUserId, false);
        $reservedSeat->update(['status' => OrderItemSeatStatus::Enrolled]);

        $remaining = max(0, $quantity - 1);

        if ($remaining === 0) {
            return;
        }

        $seats = [];
        for ($i = 0; $i < $remaining; $i++) {
            $seats[] = [
                'order_id' => $item->order_id,
                'order_item_id' => $item->id,
                'course_id' => $this->resolveCourseId($item),
                'course_group_id' => $this->resolveCourseGroupId($item),
                'purchaser_user_id' => $purchaserUserId,
                'invited_user_id' => null,
                'invited_phone' => null,
                'status' => OrderItemSeatStatus::Unassigned,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $item->seats()->insert($seats);
    }

    /**
     * Keep a seat's denormalized course_id/course_group_id in step with its
     * OrderItem after an admin corrects the item's purchasable/group.
     */
    public function syncSeatToItem(OrderItemSeat $seat, OrderItem $item): void
    {
        $item->loadMissing('purchasable');

        $seat->update([
            'course_id' => $this->resolveCourseId($item),
            'course_group_id' => $this->resolveCourseGroupId($item),
        ]);
    }

    private function resolveCourseId(OrderItem $orderItem): ?int
    {
        if ($orderItem->purchasable instanceof \App\Models\CourseGroup) {
            return $orderItem->purchasable->course_id;
        }

        return null;
    }

    private function resolveCourseGroupId(OrderItem $orderItem): ?int
    {
        if ($orderItem->purchasable instanceof \App\Models\CourseGroup) {
            return $orderItem->purchasable->id;
        }

        return null;
    }
}
