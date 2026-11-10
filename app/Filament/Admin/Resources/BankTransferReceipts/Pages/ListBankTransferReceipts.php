<?php

namespace App\Filament\Admin\Resources\BankTransferReceipts\Pages;

use App\Filament\Admin\Resources\BankTransferReceipts\BankTransferReceiptResource;
use Filament\Resources\Pages\ListRecords;

class ListBankTransferReceipts extends ListRecords
{
    protected static string $resource = BankTransferReceiptResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }
}
