<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderActivityLog;
use App\Services\OrderApprovalService;
use App\Services\PricingService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected ?OrderStatus $originalStatus = null;

    /** @var array<string, mixed> */
    protected array $beforeSnapshot = [];

    private const LOGGED_ATTRIBUTES = [
        'user_id',
        'coupon_id',
        'subtotal_amount',
        'discount_amount',
        'total_amount',
        'status',
        'source',
        'referred_by_employee_id',
        'notes',
    ];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $status = $data['status'] ?? null;
        $this->originalStatus = $status instanceof OrderStatus ? $status : OrderStatus::tryFrom((string) $status);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Filament's relationship-bound coupon_id Select mutates $this->record in place
        // as soon as the form state resolves, before this hook runs. Comparing against a
        // fresh DB copy (rather than $this->record) is the only reliable way to know what
        // actually changed.
        $original = Order::query()->find($this->record->getKey());

        $this->beforeSnapshot = $this->normalizeAttributes($original->only(self::LOGGED_ATTRIBUTES));

        $newCouponId = $data['coupon_id'] ?? null;

        if ((string) ($newCouponId ?? '') !== (string) ($original->coupon_id ?? '')) {
            $record = clone $original;
            $record->coupon_id = $newCouponId;

            $totals = app(PricingService::class)->calculateOrderTotals($record);

            $data['subtotal_amount'] = $totals['subtotal'];
            $data['discount_amount'] = $totals['discount'];
            $data['total_amount'] = $totals['total'];
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $currentStatus = $this->record->status instanceof OrderStatus
            ? $this->record->status
            : OrderStatus::tryFrom((string) $this->record->status);

        if ($this->originalStatus !== OrderStatus::Paid && $currentStatus === OrderStatus::Paid) {
            app(OrderApprovalService::class)->approve($this->record);
        }

        $this->syncEnrollmentsClient();
        $this->logOrderActivity();
    }

    private function syncEnrollmentsClient(): void
    {
        $previousUserId = $this->beforeSnapshot['user_id'] ?? null;

        if ((string) $previousUserId === (string) $this->record->user_id) {
            return;
        }

        Enrollment::query()
            ->whereIn('order_item_id', $this->record->items()->pluck('id'))
            ->update(['user_id' => $this->record->user_id]);
    }

    private function logOrderActivity(): void
    {
        $after = $this->normalizeAttributes($this->record->only(self::LOGGED_ATTRIBUTES));

        $diff = [];

        foreach ($after as $key => $value) {
            $before = $this->beforeSnapshot[$key] ?? null;

            if ($before !== $value) {
                $diff[$key] = ['old' => $before, 'new' => $value];
            }
        }

        if ($diff === []) {
            return;
        }

        OrderActivityLog::query()->create([
            'order_id' => $this->record->id,
            'user_id' => Filament::auth()->user()?->id,
            'diff' => $diff,
            'notes' => $this->record->notes,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalizeAttributes(array $attributes): array
    {
        return array_map(
            fn (mixed $value): mixed => $value instanceof \BackedEnum ? $value->value : $value,
            $attributes,
        );
    }

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }
}
