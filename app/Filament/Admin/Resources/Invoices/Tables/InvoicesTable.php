<?php

namespace App\Filament\Admin\Resources\Invoices\Tables;

use App\Enums\InvoiceRecipientType;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class InvoicesTable
{
    private static function canManageTrashed(): bool
    {
        return auth('admin')->user()?->hasRole(['admin', 'super-admin']) ?? false;
    }

    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('invoice_number')->label(__('admin.labels.invoice_number'))->searchable()->sortable(),
            TextColumn::make('order.order_no')->label(__('admin.labels.order'))->searchable()->sortable(),
            TextColumn::make('recipient')
                ->label(__('admin.labels.recipient'))
                ->getStateUsing(fn (Invoice $record): ?string => $record->recipient_type === InvoiceRecipientType::Company ? $record->company_legal_name : $record->recipient_name)
                ->toggleable(),
            TextColumn::make('amount')
                ->label(__('admin.labels.amount'))
                ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2))
                ->sortable(),
            TextColumn::make('status')
                ->label(__('admin.labels.status'))
                ->formatStateUsing(fn (InvoiceStatus $state): string => __('admin.options.'.$state->value))
                ->badge()
                ->color(fn (InvoiceStatus $state): string => match ($state) {
                    InvoiceStatus::Submitted => 'success',
                    InvoiceStatus::Pending => 'warning',
                    InvoiceStatus::Rejected => 'danger',
                    InvoiceStatus::Draft => 'gray',
                })
                ->sortable(),
            TextColumn::make('issued_at')->label(__('admin.labels.issued_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            TextColumn::make('deletedBy.name')->label(__('admin.labels.deleted_by'))->toggleable(isToggledHiddenByDefault: true)->placeholder('—'),
        ])->actions([
            ViewAction::make()->label(__('admin.pages.view')),
            EditAction::make()->label(__('admin.actions.edit')),
            Action::make('preview_invoice')
                ->label(__('admin.labels.preview'))
                ->icon('heroicon-o-eye')
                ->color('info')
                ->visible(fn (): bool => auth('admin')->user()?->can('invoices.view') ?? false)
                ->disabled(fn (Invoice $record): bool => ! $record->invoice_path || ! filled(config('filesystems.disks.s3.bucket')))
                ->url(function (Invoice $record): string {
                    if (! $record->invoice_path || ! filled(config('filesystems.disks.s3.bucket'))) {
                        return '#';
                    }
                    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                    $disk = Storage::disk('s3');

                    return $disk->temporaryUrl($record->invoice_path, now()->addMinutes(15));
                })
                ->openUrlInNewTab(),
            DeleteAction::make()->label(__('admin.actions.delete')),
            RestoreAction::make()->visible(fn (): bool => self::canManageTrashed()),
            ForceDeleteAction::make()->visible(fn (): bool => self::canManageTrashed()),
        ])->filters([
            SelectFilter::make('status')
                ->options([
                    InvoiceStatus::Draft->value => __('admin.options.draft'),
                    InvoiceStatus::Pending->value => __('admin.options.pending'),
                    InvoiceStatus::Rejected->value => __('admin.options.rejected'),
                    InvoiceStatus::Submitted->value => __('admin.options.submitted'),
                ]),
            SelectFilter::make('recipient_type')
                ->label(__('admin.labels.recipient_type'))
                ->options([
                    InvoiceRecipientType::Person->value => __('admin.options.person'),
                    InvoiceRecipientType::Company->value => __('admin.options.company'),
                ]),
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
            ->emptyStateHeading(__('admin.empty_states.invoices.heading'))
            ->emptyStateDescription(__('admin.empty_states.invoices.description'));
    }
}
