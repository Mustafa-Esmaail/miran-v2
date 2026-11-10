<?php

namespace App\Filament\Admin\Resources\Orders\RelationManagers;

use App\Enums\BankTransferStatus;
use App\Models\BankTransferReceipt;
use App\Services\BankTransferService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class BankTransferReceiptsRelationManager extends RelationManager
{
    protected static string $relationship = 'bankTransferReceipts';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.order_bank_receipts');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('status')
                ->label(__('admin.labels.status'))
                ->options([
                    BankTransferStatus::Submitted->value => __('admin.options.submitted'),
                    BankTransferStatus::Approved->value => __('admin.options.approved'),
                    BankTransferStatus::Rejected->value => __('admin.options.rejected'),
                    BankTransferStatus::Replaced->value => __('admin.options.replaced'),
                ])
                ->required(),
            Textarea::make('review_notes')
                ->label(__('admin.labels.review_notes'))
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('user.name')->label(__('admin.labels.user'))->searchable(),
            TextColumn::make('status')
                ->label(__('admin.labels.status'))
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
            TextColumn::make('review_notes')->label(__('admin.labels.review_notes'))->limit(40)->toggleable(),
            TextColumn::make('reviewed_at')->label(__('admin.labels.reviewed_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
        ])->actions([
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
                ->visible(fn (BankTransferReceipt $record): bool => $record->status === BankTransferStatus::Submitted && (auth('admin')->user()?->can('bank_receipts.update') ?? false))
                ->requiresConfirmation()
                ->modalHeading(__('admin.modals.bank_receipt.approve_heading'))
                ->modalDescription(__('admin.modals.bank_receipt.approve_description'))
                ->action(function (BankTransferReceipt $record, BankTransferService $service): void {
                    $service->approveReceipt($record->id, auth('admin')->id());
                }),
            Action::make('reject')
                ->label(__('admin.labels.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (BankTransferReceipt $record): bool => $record->status === BankTransferStatus::Submitted && (auth('admin')->user()?->can('bank_receipts.update') ?? false))
                ->requiresConfirmation()
                ->modalHeading(__('admin.modals.bank_receipt.reject_heading'))
                ->modalDescription(__('admin.modals.bank_receipt.reject_description'))
                ->action(function (BankTransferReceipt $record, BankTransferService $service): void {
                    $service->rejectReceipt($record->id, auth('admin')->id());
                }),
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
            EditAction::make()->label(__('admin.actions.edit')),
            DeleteAction::make()->label(__('admin.actions.delete'))
                ->visible(fn (BankTransferReceipt $record): bool => $record->status === BankTransferStatus::Submitted),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.bank_transfer_receipts.heading'))
            ->emptyStateDescription(__('admin.empty_states.bank_transfer_receipts.description'));
    }
}
