<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Bundle;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Purchasables\PurchasableResolver;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class OrderItemChangeService
{
    public function __construct(
        private readonly PurchasableResolver $resolver,
        private readonly EnrollmentService $enrollments,
        private readonly OrderSeatService $seats,
    ) {}

    /**
     * @param  array<string, mixed>  $formData
     * @return array<string, mixed>
     */
    public function updateItem(OrderItem $item, array $formData): array
    {
        $order = $item->order;

        if ($order->status !== OrderStatus::Paid) {
            throw new InvalidArgumentException('Only items on a paid order can be corrected.');
        }

        if ($item->seats()->count() > 1) {
            throw new InvalidArgumentException('Items with more than one assigned seat cannot be edited here.');
        }

        $before = [
            'purchasable_type' => $item->purchasable_type,
            'purchasable_id' => $item->purchasable_id,
            'price_amount' => $item->price_amount,
            'metadata_snapshot' => $item->metadata_snapshot,
        ];

        $duplicate = $order->items()
            ->where('id', '!=', $item->id)
            ->where('purchasable_type', $formData['purchasable_type'])
            ->where('purchasable_id', (int) $formData['purchasable_id'])
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'purchasable_id' => __('admin.validation.order_item_already_exists'),
            ]);
        }

        $payload = $this->resolveItemPayload($formData);

        $item->update([
            'purchasable_type' => $payload['purchasable_type'],
            'purchasable_id' => $payload['purchasable_id'],
            'title_snapshot' => $payload['title_snapshot'],
            'price_amount' => $payload['price_amount'],
            'metadata_snapshot' => $payload['metadata_snapshot'],
        ]);

        $this->recalculateOrderTotals($order);

        $seat = $item->seats()->first();
        if ($seat) {
            $this->seats->syncSeatToItem($seat, $item->fresh());
        }
        $this->enrollments->syncForItemChange($item->fresh(), $order->user_id);

        $diff = [
            'purchasable_type' => ['old' => $before['purchasable_type'], 'new' => $payload['purchasable_type']],
            'purchasable_id' => ['old' => $before['purchasable_id'], 'new' => $payload['purchasable_id']],
            'price_amount' => ['old' => $before['price_amount'], 'new' => $payload['price_amount']],
        ];

        $oldGroups = $before['metadata_snapshot']['selected_course_groups'] ?? null;
        $newGroups = $payload['metadata_snapshot']['selected_course_groups'] ?? null;

        if ($oldGroups !== $newGroups) {
            $diff['course_groups'] = ['old' => $oldGroups, 'new' => $newGroups];
        }

        return $diff;
    }

    /**
     * @param  array<string, mixed>  $formData
     * @return array<string, mixed>
     */
    public function addItem(Order $order, array $formData): array
    {
        if ($order->status !== OrderStatus::Paid) {
            throw new InvalidArgumentException('Items can only be added to a paid order through this action.');
        }

        $purchasableType = $formData['purchasable_type'];
        $purchasableId = (int) $formData['purchasable_id'];

        $duplicate = $order->items()
            ->where('purchasable_type', $purchasableType)
            ->where('purchasable_id', $purchasableId)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'purchasable_id' => __('admin.validation.order_item_already_exists'),
            ]);
        }

        $payload = $this->resolveItemPayload($formData);
        $quantity = max(1, (int) ($formData['quantity'] ?? 1));

        $item = $order->items()->create([
            'purchasable_type' => $payload['purchasable_type'],
            'purchasable_id' => $payload['purchasable_id'],
            'title_snapshot' => $payload['title_snapshot'],
            'price_amount' => $payload['price_amount'],
            'currency' => $order->currency,
            'metadata_snapshot' => $payload['metadata_snapshot'],
            'quantity' => $quantity,
        ]);

        $this->recalculateOrderTotals($order);

        if ($quantity > 1) {
            $this->seats->createSeatsForItem($item->fresh(), $quantity, $order->user_id);
        } else {
            $this->enrollments->activateForItem($item->fresh(), $order->user_id, false);
        }

        return [
            'purchasable_type' => ['old' => null, 'new' => $payload['purchasable_type']],
            'purchasable_id' => ['old' => null, 'new' => $payload['purchasable_id']],
            'price_amount' => ['old' => null, 'new' => $payload['price_amount']],
            'quantity' => ['old' => null, 'new' => $quantity],
        ];
    }

    /**
     * @param  array<string, mixed>  $formData
     * @return array<string, mixed>
     */
    private function resolveItemPayload(array $formData): array
    {
        $purchasableType = $formData['purchasable_type'];
        $purchasableId = (int) $formData['purchasable_id'];
        $purchasable = $this->resolver->resolvePurchasable($purchasableType, $purchasableId);

        $metadata = $this->resolver->buildMetadataSnapshot($purchasable);

        if ($purchasable instanceof Bundle) {
            $selectedGroups = collect($formData['bundle_course_groups'] ?? [])
                ->mapWithKeys(fn ($groupId, $courseId): array => [(int) $courseId => (int) $groupId])
                ->filter(fn (int $groupId, int $courseId): bool => $courseId > 0 && $groupId > 0);

            $metadata['selected_course_groups'] = $selectedGroups->all();
            $metadata['selected_course_group_ids'] = $selectedGroups->values()->all();
        }

        return [
            'purchasable_type' => $purchasableType,
            'purchasable_id' => $purchasableId,
            'title_snapshot' => $this->resolver->getDisplayLabel($purchasable),
            'price_amount' => (int) $formData['price_amount'],
            'metadata_snapshot' => $metadata,
        ];
    }

    private function recalculateOrderTotals(Order $order): void
    {
        $order->refresh();
        $subtotal = (int) $order->items()->get()->sum(fn (OrderItem $orderItem): int => $orderItem->price_amount * $orderItem->quantity);

        $order->update([
            'subtotal_amount' => $subtotal,
            'total_amount' => $subtotal - $order->discount_amount,
        ]);
    }
}
