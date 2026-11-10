<?php

namespace App\Livewire\Dashboard;

use App\Enums\OrderItemSeatStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemSeat;
use App\Services\OrderSeatService;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Livewire\Component;

class OrderSeats extends Component
{
    public int $orderId;

    /**
     * @var array<int, int>
     */
    public array $quantityUpdates = [];

    /**
     * @var array<int, array{phone:string, name:?string, email:?string}>
     */
    public array $inviteForm = [];

    /**
     * @var array<int, bool>  seat IDs currently in edit mode
     */
    public array $editingSeats = [];

    /**
     * @var array<int, array{phone:string, name:string, email:string}>
     */
    public array $editForm = [];

    public ?string $message = null;

    public ?string $error = null;

    public function mount(int $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function updateQuantity(int $orderItemId, OrderSeatService $seats): void
    {
        $orderItem = $this->getOrderItem($orderItemId);
        if (! $orderItem) {
            return;
        }

        if ($orderItem->order?->status !== OrderStatus::Pending) {
            $this->error = __('web.checkout.edit_unavailable');

            return;
        }

        $quantity = (int) ($this->quantityUpdates[$orderItemId] ?? $orderItem->quantity);

        try {
            $seats->updateOrderItemQuantity($orderItem, $quantity);
            $this->message = __('web.checkout.order_updated');
        } catch (\RuntimeException $exception) {
            $this->error = $exception->getMessage();
        }
    }

    public function assignSeat(int $orderItemId, OrderSeatService $seats): void
    {
        $orderItem = $this->getOrderItem($orderItemId);
        if (! $orderItem) {
            return;
        }

        $form = $this->inviteForm[$orderItemId] ?? ['phone' => '', 'name' => null, 'email' => null];
        $validated = Validator::make($form, [
            'phone' => ['required', 'string', 'min:6', 'max:30', 'regex:/^[0-9+\\-\\s()]+$/'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ])->validate();

        try {
            $seats->assignSeatToPhone(
                $orderItem,
                $validated['phone'],
                $validated['name'] ?? null,
                $validated['email'] ?? null
            );
        } catch (\RuntimeException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        $this->inviteForm[$orderItemId] = ['phone' => '', 'name' => null, 'email' => null];
        $this->message = __('web.checkout.invite_sent');
    }

    public function removeInvite(int $seatId): void
    {
        $seat = OrderItemSeat::query()
            ->whereKey($seatId)
            ->with('orderItem.order')
            ->first();

        if (! $seat || $seat->orderItem?->order?->user_id !== auth()->id()) {
            return;
        }

        if ($seat->orderItem->order->status !== OrderStatus::Pending) {
            $this->error = __('web.checkout.edit_unavailable');

            return;
        }

        if ($seat->status !== OrderItemSeatStatus::Invited) {
            return;
        }

        if ($seat->invited_user_id !== null && $seat->invited_user_id === $seat->purchaser_user_id) {
            return;
        }

        $seat->update([
            'invited_user_id' => null,
            'invited_phone' => null,
            'status' => OrderItemSeatStatus::Unassigned,
        ]);

        unset($this->editingSeats[$seatId], $this->editForm[$seatId]);
    }

    public function startEditSeat(int $seatId): void
    {
        $seat = OrderItemSeat::query()
            ->whereKey($seatId)
            ->with('orderItem.order', 'invitedUser')
            ->first();

        if (! $seat || $seat->orderItem?->order?->user_id !== auth()->id()) {
            return;
        }

        if ($seat->orderItem->order->status !== OrderStatus::Pending) {
            $this->error = __('web.checkout.edit_unavailable');

            return;
        }

        if ($seat->status !== OrderItemSeatStatus::Invited) {
            return;
        }

        if ($seat->invited_user_id !== null && $seat->invited_user_id === $seat->purchaser_user_id) {
            return;
        }

        $email = $seat->invitedUser?->email ?? '';
        $isPlaceholder = str_ends_with($email, '@example.test');

        $this->editForm[$seatId] = [
            'phone' => $seat->invited_phone ?? '',
            'name' => $seat->invitedUser?->name ?? '',
            'email' => $isPlaceholder ? '' : $email,
        ];
        $this->editingSeats[$seatId] = true;
        $this->error = null;
    }

    public function cancelEditSeat(int $seatId): void
    {
        unset($this->editingSeats[$seatId], $this->editForm[$seatId]);
    }

    public function updateSeat(int $seatId, OrderSeatService $seats): void
    {
        $seat = OrderItemSeat::query()
            ->whereKey($seatId)
            ->with('orderItem.order')
            ->first();

        if (! $seat || $seat->orderItem?->order?->user_id !== auth()->id()) {
            return;
        }

        if ($seat->orderItem->order->status !== OrderStatus::Pending) {
            $this->error = __('web.checkout.edit_unavailable');

            return;
        }

        $form = $this->editForm[$seatId] ?? ['phone' => '', 'name' => '', 'email' => ''];

        $validated = Validator::make($form, [
            'phone' => ['required', 'string', 'min:6', 'max:30', 'regex:/^[0-9+\\-\\s()]+$/'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ])->validate();

        try {
            $seats->updateSeatAssignment(
                $seat,
                $validated['phone'],
                $validated['name'] ?? null,
                $validated['email'] ?? null
            );
        } catch (\RuntimeException $exception) {
            $this->error = $exception->getMessage();

            return;
        }

        unset($this->editingSeats[$seatId], $this->editForm[$seatId]);
        $this->message = __('web.checkout.invite_sent');
    }

    public function render(): View
    {
        $order = Order::query()
            ->where('user_id', auth()->id())
            ->with(['items.seats.invitedUser'])
            ->findOrFail($this->orderId);

        $this->syncInputs($order);

        $items = $order->items->map(function (OrderItem $item) {
            return [
                'id' => $item->id,
                'title' => $item->title_snapshot,
                'quantity' => $item->quantity,
                'assigned_count' => $item->assigned_seats_count,
                'available_count' => $item->available_seats_count,
                'status' => $item->order?->status?->value,
                'seats' => $item->seats->map(function (OrderItemSeat $seat) {
                    $inviteeEmail = $seat->invitedUser?->email;
                    $isPlaceholder = $inviteeEmail !== null && str_ends_with($inviteeEmail, '@example.test');
                    return [
                        'id' => $seat->id,
                        'phone' => $seat->invited_phone,
                        'invited_name' => $seat->invitedUser?->name,
                        'invited_email' => $isPlaceholder ? null : $inviteeEmail,
                        'status' => $seat->status->value,
                        'status_label' => $this->translateSeatStatus($seat->status->value),
                        'is_purchaser' => $seat->invited_user_id !== null && $seat->invited_user_id === $seat->purchaser_user_id,
                    ];
                })->all(),
            ];
        })->all();

        return view('livewire.dashboard.order-seats', [
            'orderStatus' => $order->status->value,
            'orderStatusLabel' => $this->translateOrderStatus($order->status->value),
            'items' => $items,
        ]);
    }

    private function translateOrderStatus(string $status): string
    {
        $label = __('web.orders.status_values.'.$status);

        return $label !== 'web.orders.status_values.'.$status ? $label : $status;
    }

    private function translateSeatStatus(string $status): string
    {
        $label = __('web.checkout.seat_status_values.'.$status);

        return $label !== 'web.checkout.seat_status_values.'.$status ? $label : $status;
    }

    private function syncInputs(Order $order): void
    {
        $itemIds = $order->items->pluck('id')->all();

        foreach ($order->items as $item) {
            if (! array_key_exists($item->id, $this->quantityUpdates)) {
                $this->quantityUpdates[$item->id] = $item->quantity;
            }
            if (! array_key_exists($item->id, $this->inviteForm)) {
                $this->inviteForm[$item->id] = ['phone' => '', 'name' => null, 'email' => null];
            }
        }

        foreach (array_keys($this->quantityUpdates) as $itemId) {
            if (! in_array($itemId, $itemIds, true)) {
                unset($this->quantityUpdates[$itemId], $this->inviteForm[$itemId]);
            }
        }
    }

    private function getOrderItem(int $orderItemId): ?OrderItem
    {
        return OrderItem::query()
            ->whereKey($orderItemId)
            ->whereHas('order', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->with('order')
            ->first();
    }
}
