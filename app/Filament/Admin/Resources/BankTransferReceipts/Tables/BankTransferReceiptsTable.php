<?php

namespace App\Filament\Admin\Resources\BankTransferReceipts\Tables;

use App\Enums\BankTransferStatus;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\BankTransferReceipt;
use App\Services\BankTransferService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class BankTransferReceiptsTable
{
    private static function canManageTrashed(): bool
    {
        return auth('admin')->user()?->hasRole(['admin', 'super-admin']) ?? false;
    }

    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('order.order_no')->label(__('admin.labels.order'))->searchable()->sortable(),
            TextColumn::make('user.name')->label(__('admin.labels.user'))->searchable(),
            TextColumn::make('user.email')->label(__('admin.labels.email'))->searchable()->toggleable(),
            TextColumn::make('status')->label(__('admin.labels.status'))
                ->formatStateUsing(function ($state) {
                    $value = $state instanceof \BackedEnum ? $state->value : $state;
                    $key = 'admin.options.'.$value;

                    return __($key) !== $key ? __($key) : (string) $value;
                })
                ->badge()
                ->color(function ($state): string {
                    $value = $state instanceof BankTransferStatus ? $state->value : (string) $state;

                    return match ($value) {
                        BankTransferStatus::Approved->value => 'success',
                        BankTransferStatus::Submitted->value => 'warning',
                        BankTransferStatus::Rejected->value => 'danger',
                        BankTransferStatus::Replaced->value => 'gray',
                        default => 'secondary',
                    };
                })
                ->sortable(),
            TextColumn::make('reviewed_at')->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            TextColumn::make('updated_at')->label(__('admin.labels.updated_at'))->since()->toggleable(),
            TextColumn::make('deletedBy.name')->label(__('admin.labels.deleted_by'))->toggleable(isToggledHiddenByDefault: true)->placeholder('—'),
        ])->actions([
            ViewAction::make()->label(__('admin.pages.view')),
            Action::make('preview_receipt')
                ->label(__('admin.labels.preview'))
                ->icon('heroicon-o-eye')
                ->color('info')
                ->visible(fn (): bool => auth('admin')->user()?->can('bank_receipts.view') ?? false)
                ->disabled(fn (BankTransferReceipt $record): bool => ! $record->receipt_path || ! filled(config('filesystems.disks.s3.bucket')))
                ->url(function (BankTransferReceipt $record): string {
                    if (! $record->receipt_path || ! filled(config('filesystems.disks.s3.bucket'))) {
                        return '#';
                    }
                    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                    $disk = Storage::disk('s3');

                    return $disk->temporaryUrl($record->receipt_path, now()->addMinutes(15));
                })
                ->openUrlInNewTab(),
            Action::make('approve')
                ->label(__('admin.labels.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => auth('admin')->user()?->can('bank_receipts.update') ?? false)
                ->action(function (BankTransferReceipt $record, BankTransferService $service) {
                    $service->approveReceipt($record->id, auth('admin')->id());
                })
                ->requiresConfirmation()
                ->modalHeading(__('admin.modals.bank_receipt.approve_heading'))
                ->modalDescription(__('admin.modals.bank_receipt.approve_description')),
            Action::make('reject')
                ->label(__('admin.labels.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => auth('admin')->user()?->can('bank_receipts.update') ?? false)
                ->action(function (BankTransferReceipt $record, BankTransferService $service) {
                    $service->rejectReceipt($record->id, auth('admin')->id());
                })
                ->requiresConfirmation()
                ->modalHeading(__('admin.modals.bank_receipt.reject_heading'))
                ->modalDescription(__('admin.modals.bank_receipt.reject_description')),
            Action::make('replace')
                ->label(__('admin.actions.replace'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => auth('admin')->user()?->can('bank_receipts.replace') ?? false)
                ->schema([
                    FileUpload::make('receipt_path')
                        ->label(__('admin.labels.receipt_file'))
                        ->disk('s3')
                        ->directory('bank-receipts')
                        ->visibility('private')
                        ->required(),
                    Textarea::make('notes')
                        ->label(__('admin.labels.review_notes'))
                        ->rows(3),
                ])
                ->modalHeading(__('admin.modals.bank_receipt.replace_heading'))
                ->modalDescription(__('admin.modals.bank_receipt.replace_description'))
                ->action(function (array $data, BankTransferReceipt $record, BankTransferService $service): void {
                    $service->replaceReceipt($record->id, auth('admin')->id(), $data['receipt_path'], $data['notes'] ?? null);
                }),
            DeleteAction::make()->label(__('admin.actions.delete'))
                ->visible(fn (BankTransferReceipt $record): bool => $record->status === BankTransferStatus::Submitted),
            RestoreAction::make()->visible(fn (): bool => self::canManageTrashed()),
            ForceDeleteAction::make()->visible(fn (): bool => self::canManageTrashed()),
        ])->filters([
            SelectFilter::make('status')
                ->options(fn (): array => BankTransferReceipt::query()
                    ->distinct()
                    ->get(['status'])
                    ->mapWithKeys(fn (BankTransferReceipt $receipt): array => [
                        $receipt->status->value => $receipt->status->value,
                    ])
                    ->all())->searchable(),
            SelectFilter::make('order_source')
                ->label(__('admin.labels.order_source'))
                ->options([
                    OrderSource::Site->value => __('admin.options.order_source_site'),
                    OrderSource::Store->value => __('admin.options.order_source_store'),
                    OrderSource::Sales->value => __('admin.options.order_source_sales'),
                    OrderSource::Admin->value => __('admin.options.order_source_admin'),
                ])
                ->searchable()
                ->query(function (Builder $query, array $data): Builder {
                    $value = $data['value'] ?? null;

                    return $value
                        ? $query->whereHas('order', fn (Builder $q) => $q->where('source', $value))
                        : $query;
                }),
            SelectFilter::make('order_status')
                ->label(__('admin.labels.status'))
                ->options([
                    OrderStatus::Pending->value => __('admin.options.pending'),
                    OrderStatus::Paid->value => __('admin.options.paid'),
                    OrderStatus::Rejected->value => __('admin.options.rejected'),
                    OrderStatus::Refunded->value => __('admin.options.refunded'),
                    OrderStatus::Cancelled->value => __('admin.options.cancelled'),
                ])
                ->searchable()
                ->query(function (Builder $query, array $data): Builder {
                    $value = $data['value'] ?? null;

                    return $value
                        ? $query->whereHas('order', fn (Builder $q) => $q->where('status', $value))
                        : $query;
                }),
            SelectFilter::make('payment_status')
                ->label(__('admin.labels.payment_status'))
                ->options([
                    PaymentStatus::Pending->value => __('admin.options.pending'),
                    PaymentStatus::Authorized->value => __('admin.options.authorized'),
                    PaymentStatus::Paid->value => __('admin.options.paid'),
                    PaymentStatus::Failed->value => __('admin.options.failed'),
                    PaymentStatus::Refunded->value => __('admin.options.refunded'),
                ])
                ->searchable()
                ->query(function (Builder $query, array $data): Builder {
                    $value = $data['value'] ?? null;

                    return $value
                        ? $query->whereHas('order.payments', fn (Builder $q) => $q->where('provider', 'bank_transfer')->where('status', $value))
                        : $query;
                }),
            Filter::make('created_at')
                ->label(__('admin.labels.created_at'))
                ->schema([
                    DatePicker::make('created_from')
                        ->label(__('admin.labels.from')),
                    DatePicker::make('created_until')
                        ->label(__('admin.labels.until')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['created_from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
                        ->when($data['created_until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date));
                }),
            TrashedFilter::make()->visible(fn (): bool => self::canManageTrashed()),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
            RestoreBulkAction::make()->visible(fn (): bool => self::canManageTrashed()),
            ForceDeleteBulkAction::make()->visible(fn (): bool => self::canManageTrashed()),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.bank_transfer_receipts.heading'))
            ->emptyStateDescription(__('admin.empty_states.bank_transfer_receipts.description'));
    }
}
