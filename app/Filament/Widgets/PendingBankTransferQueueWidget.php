<?php

namespace App\Filament\Widgets;

use App\Enums\BankTransferStatus;
use App\Filament\Admin\Resources\BankTransferReceipts\BankTransferReceiptResource;
use App\Models\BankTransferReceipt;
use App\Services\BankTransferService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingBankTransferQueueWidget extends BaseWidget
{
    protected ?string $pollingInterval = '20s';

    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.queues.pending_bank_receipts.heading');
    }

    public static function canView(): bool
    {
        return auth('admin')->user()?->can('bank_receipts.viewAny') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                BankTransferReceipt::query()
                    ->with(['order', 'user'])
                    ->where('status', BankTransferStatus::Submitted)
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('order.order_no')
                    ->label(__('admin.labels.order'))
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label(__('admin.labels.user'))
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? $state->value : (string) $state),
                TextColumn::make('created_at')
                    ->label(__('admin.labels.submitted'))
                    ->since(),
            ])
            ->actions([
                Action::make('edit')
                    ->label(__('admin.actions.view'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (BankTransferReceipt $record): string => BankTransferReceiptResource::getUrl('index')),
                Action::make('approve')
                    ->label(__('admin.actions.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (): bool => auth('admin')->user()?->can('bank_receipts.update') ?? false)
                    ->requiresConfirmation()
                    ->action(function (BankTransferReceipt $record, BankTransferService $service): void {
                        $service->approveReceipt($record->id, (int) Filament::auth()->id());
                    }),
                Action::make('reject')
                    ->label(__('admin.actions.reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (): bool => auth('admin')->user()?->can('bank_receipts.update') ?? false)
                    ->requiresConfirmation()
                    ->action(function (BankTransferReceipt $record, BankTransferService $service): void {
                        $service->rejectReceipt($record->id, (int) Filament::auth()->id());
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('admin.dashboard_widgets.queues.pending_bank_receipts.empty'));
    }
}
