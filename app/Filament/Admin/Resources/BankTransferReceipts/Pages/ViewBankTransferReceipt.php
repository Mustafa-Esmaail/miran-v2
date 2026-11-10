<?php

namespace App\Filament\Admin\Resources\BankTransferReceipts\Pages;

use App\Filament\Admin\Resources\BankTransferReceipts\BankTransferReceiptResource;
use Filament\Resources\Pages\ViewRecord;

class ViewBankTransferReceipt extends ViewRecord
{
    protected static string $resource = BankTransferReceiptResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.view');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.view_title', ['label' => $this->getRecordTitle()]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
