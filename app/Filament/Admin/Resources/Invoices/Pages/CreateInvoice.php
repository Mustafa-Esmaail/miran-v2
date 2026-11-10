<?php

namespace App\Filament\Admin\Resources\Invoices\Pages;

use App\Enums\InvoiceStatus;
use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Models\Order;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = InvoiceStatus::Pending->value;
        $data['created_by'] = auth()->id();
        $data['amount'] = Order::query()->find($data['order_id'] ?? null)?->total_amount ?? 0;

        return $data;
    }
}
