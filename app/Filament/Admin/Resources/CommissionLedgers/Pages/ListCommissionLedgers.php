<?php

namespace App\Filament\Admin\Resources\CommissionLedgers\Pages;

use App\Filament\Admin\Resources\CommissionLedgers\CommissionLedgerResource;
use Filament\Resources\Pages\ListRecords;
use Leek\FilamentHeaderFilters\Concerns\HasHeaderFilters;

class ListCommissionLedgers extends ListRecords
{
    use HasHeaderFilters;

    protected static string $resource = CommissionLedgerResource::class;

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }
}
