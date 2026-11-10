<?php

namespace App\Filament\Admin\Resources\BankTransferReceipts;

use App\Filament\Admin\Resources\BankTransferReceipts\Schemas\BankTransferReceiptForm;
use App\Filament\Admin\Resources\BankTransferReceipts\Tables\BankTransferReceiptsTable;
use App\Models\BankTransferReceipt;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BankTransferReceiptResource extends Resource
{
    protected static ?string $model = BankTransferReceipt::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.bank_receipts');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.bank_transfer_receipt');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.bank_transfer_receipts');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.commerce');
    }

    public static function form(Schema $schema): Schema
    {
        return BankTransferReceiptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankTransferReceiptsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['order', 'user']);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery();

        if (auth('admin')->user()?->hasRole(['admin', 'super-admin'])) {
            $query->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBankTransferReceipts::route('/'),
            'view' => Pages\ViewBankTransferReceipt::route('/{record}'),
        ];
    }
}
