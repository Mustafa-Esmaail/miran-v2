<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\CourseGroup;
use App\Models\ServiceRequest;
use App\Support\Purchasables\PurchasableResolver;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\Url;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected array $orderItemData = [];

    /**
     * Set by the lead's "Create order" action; the new order is linked back to it.
     */
    #[Url(as: 'service_request')]
    public ?int $serviceRequestId = null;

    protected function afterFill(): void
    {
        $lead = $this->sourceLead();

        if ($lead === null) {
            return;
        }

        $prefill = array_filter([
            'user_id' => $lead->user_id,
            'purchasable_type' => $lead->course_group_id ? CourseGroup::class : null,
            'purchasable_id' => $lead->course_group_id,
        ]);

        if ($lead->courseGroup !== null) {
            $prefill['price_amount'] = app(PurchasableResolver::class)->getPriceAmount($lead->courseGroup);
        }

        $this->form->fill([...$this->data, ...$prefill]);
    }

    private function sourceLead(): ?ServiceRequest
    {
        if (! $this->serviceRequestId) {
            return null;
        }

        $lead = ServiceRequest::query()->with('courseGroup.course.discounts')->find($this->serviceRequestId);

        if ($lead === null || $lead->converted_order_id !== null || ! (auth('admin')->user()?->can('update', $lead) ?? false)) {
            return null;
        }

        return $lead;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $purchasableType = $data['purchasable_type'] ?? null;
        $purchasableId = (int) ($data['purchasable_id'] ?? 0);
        $quantity = max(1, (int) ($data['quantity'] ?? 1));
        $resolver = app(PurchasableResolver::class);
        $purchasable = ($purchasableType && $purchasableId)
            ? $resolver->resolvePurchasable($purchasableType, $purchasableId)
            : null;
        $price = (int) ($data['price_amount'] ?? ($purchasable ? $resolver->getPriceAmount($purchasable) : 0));

        $metadata = $purchasable ? $resolver->buildMetadataSnapshot($purchasable) : ['type_label' => 'unknown', 'subtitle' => '', 'image_path' => null];

        $this->orderItemData = [
            'purchasable_type' => $purchasableType,
            'purchasable_id' => $purchasableId,
            'title_snapshot' => $purchasable ? $resolver->getDisplayLabel($purchasable) : '',
            'price_amount' => $price,
            'currency' => config('lms.currency'),
            'metadata_snapshot' => $metadata,
            'quantity' => $quantity,
        ];

        $subtotal = $price * $quantity;
        $data['order_no'] = 'ORD-'.now()->format('YmdHis').'-'.random_int(1000, 9999);
        $data['currency'] = config('lms.currency');
        $data['subtotal_amount'] = $subtotal;
        $data['discount_amount'] = 0;
        $data['total_amount'] = $subtotal;
        $data['status'] = $data['status'] ?? OrderStatus::Pending->value;
        $data['source'] = OrderSource::Admin->value;

        unset($data['purchasable_type'], $data['purchasable_id'], $data['quantity'], $data['price_amount']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record && ($this->orderItemData['purchasable_id'] ?? 0)) {
            $this->record->items()->create($this->orderItemData);
        }

        $this->sourceLead()?->update(['converted_order_id' => $this->record->getKey()]);
    }

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }
}
