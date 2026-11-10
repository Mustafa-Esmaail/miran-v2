<?php

namespace App\Filament\Admin\Resources\Orders\Tables;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\OrderApprovalService;
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
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class OrdersTable
{
    private static function canManageTrashed(): bool
    {
        return auth('admin')->user()?->hasRole(['admin', 'super-admin']) ?? false;
    }

    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('order_no')->searchable(),
            TextColumn::make('user.name')
                ->label(__('admin.labels.customer'))
                ->url(fn (Order $record): ?string => $record->user_id
                    ? CustomerResource::getUrl('edit', ['record' => $record->user_id])
                    : null)
                ->badge()
                ->color('primary')
                ->searchable(),
            TextColumn::make('user.email')
                ->label(__('admin.labels.email'))
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('customer_phone')
                ->label(__('admin.labels.phone'))
                ->copyable()
                ->searchable()
                ->toggleable(),
            TextColumn::make('payment_method')
                ->label(__('admin.labels.payment_method'))
                ->state(fn (Order $record): ?string => $record->payments->last()?->provider ?? ($record->bankTransferReceipt ? 'bank_transfer' : null))
                ->formatStateUsing(fn (?string $state): string => match (strtolower((string) $state)) {
                    'bank_transfer', 'bank' => __('admin.labels.bank_transfer_provider'),
                    'tamara' => 'Tamara',
                    'cod', 'cash_on_delivery' => __('admin.labels.cod'),
                    '' => '—',
                    default => ucfirst((string) $state),
                })
                ->badge()
                ->color(fn (?string $state): string => match (strtolower((string) $state)) {
                    'bank_transfer', 'bank' => 'info',
                    'tamara' => 'warning',
                    'cod', 'cash_on_delivery' => 'gray',
                    default => 'secondary',
                })
                ->toggleable(),
            TextColumn::make('status')->label(__('admin.labels.status'))
                ->formatStateUsing(function ($state) {
                    $value = $state instanceof \BackedEnum ? $state->value : $state;
                    $key = 'admin.options.'.$value;

                    return __($key) !== $key ? __($key) : (string) $value;
                })
                ->badge()
                ->color(function ($state): string {
                    $value = is_object($state) && property_exists($state, 'value') ? $state->value : (string) $state;

                    return match ($value) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        'refunded' => 'gray',
                        'cancelled' => 'gray',
                        default => 'secondary',
                    };
                })
                ->sortable(),
            TextColumn::make('source')->label(__('admin.labels.order_source'))
                ->formatStateUsing(function ($state, Order $record) {
                    $value = $state instanceof \BackedEnum ? $state->value : $state;
                    $key = 'admin.options.order_source_'.$value;
                    $label = __($key) !== $key ? __($key) : (string) $value;

                    if ($value === 'sales' && $record->referredByEmployee) {
                        $label .= ' — '.$record->referredByEmployee->name;
                    }

                    if ($record->marketing_source) {
                        $label .= ' — '.$record->marketing_source;
                    }

                    return $label;
                })
                ->badge()
                ->color(function ($state): string {
                    $value = is_object($state) && property_exists($state, 'value') ? $state->value : (string) $state;

                    return match ($value) {
                        'sales' => 'info',
                        'admin' => 'warning',
                        'store' => 'gray',
                        default => 'secondary',
                    };
                })
                ->toggleable(),
            TextColumn::make('subtotal_amount')
                ->label(__('admin.labels.subtotal'))
                ->formatStateUsing(fn ($state, Order $record) => number_format((float) $state, 2).' '.($record->currency ?? ''))
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('discount_amount')
                ->label(__('admin.labels.discount'))
                ->formatStateUsing(fn ($state, Order $record) => number_format((float) $state, 2).' '.($record->currency ?? ''))
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('total_amount')
                ->label(__('admin.labels.total'))
                ->formatStateUsing(fn ($state, Order $record) => number_format((float) $state, 2).' '.($record->currency ?? ''))
                ->sortable(),
            TextColumn::make('currency')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('items_count')
                ->label(__('admin.labels.items'))
                ->counts('items')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('coupon.code')->label(__('admin.labels.coupon'))->toggleable(),
            TextColumn::make('referredByEmployee.name')->limit(20)->label(__('admin.labels.referred_by'))->toggleable()->placeholder('—'),
            TextColumn::make('notes')->limit(20)->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            TextColumn::make('updated_at')->label(__('admin.labels.updated_at'))->since()->toggleable(),
            TextColumn::make('deletedBy.name')->label(__('admin.labels.deleted_by'))->toggleable(isToggledHiddenByDefault: true)->placeholder('—'),
        ])->filters([
            SelectFilter::make('status')->options([
                'pending' => __('admin.options.pending'),
                'paid' => __('admin.options.paid'),
                'rejected' => __('admin.options.rejected'),
                'refunded' => __('admin.options.refunded'),
                'cancelled' => __('admin.options.cancelled'),
            ])->searchable(),
            SelectFilter::make('payment_method')
                ->label(__('admin.labels.payment_method'))
                ->options([
                    'bank_transfer' => __('admin.labels.bank_transfer_provider'),
                    'tamara' => 'Tamara',
                    'cod' => __('admin.labels.cod'),
                ])
                ->searchable()
                ->query(function (Builder $query, array $data): Builder {
                    $value = $data['value'] ?? null;
                    if (! $value) {
                        return $query;
                    }

                    if ($value === 'bank_transfer') {
                        return $query->whereHas('payments', fn (Builder $q) => $q->where('provider', 'bank_transfer'))
                            ->orWhereHas('bankTransferReceipt');
                    }

                    return $query->whereHas('payments', fn (Builder $q) => $q->where('provider', $value));
                }),
            SelectFilter::make('source')
                ->label(__('admin.labels.order_source'))
                ->options([
                    'site' => __('admin.options.order_source_site'),
                    'store' => __('admin.options.order_source_store'),
                    'sales' => __('admin.options.order_source_sales'),
                    'admin' => __('admin.options.order_source_admin'),
                ])
                ->searchable()
                ->query(function (Builder $query, array $data): Builder {
                    $value = $data['value'] ?? null;

                    return $value ? $query->where('source', OrderSource::from($value)) : $query;
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
        ])->actions([
            Action::make('preview_receipt')
                ->label(__('admin.labels.preview'))
                ->icon('heroicon-o-eye')
                ->color('info')
                ->visible(fn (): bool => auth('admin')->user()?->can('orders.view') ?? false)
                ->disabled(fn (Order $record): bool => ! $record->bankTransferReceipt?->receipt_path || ! filled(config('filesystems.disks.s3.bucket')))
                ->url(function (Order $record): string {
                    if (! $record->bankTransferReceipt?->receipt_path || ! filled(config('filesystems.disks.s3.bucket'))) {
                        return '#';
                    }
                    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                    $disk = Storage::disk('s3');

                    return $disk->temporaryUrl($record->bankTransferReceipt->receipt_path, now()->addMinutes(15));
                })
                ->openUrlInNewTab(),
            Action::make('approve')
                ->label(__('admin.labels.approve'))
                ->color('success')
                ->visible(fn (Order $record): bool => $record->status !== OrderStatus::Paid && (auth('admin')->user()?->can('orders.update') ?? false))
                ->requiresConfirmation()
                ->action(function (Order $record, OrderApprovalService $service): void {
                    $service->approve($record);
                }),
            Action::make('add_notice')
                ->label(__('admin.actions.add_notice'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('gray')
                ->visible(fn (): bool => auth('admin')->user()?->can('orders.notice.create') ?? false)
                ->schema([
                    Textarea::make('note')
                        ->label(__('admin.labels.notice'))
                        ->required()
                        ->rows(4),
                ])
                ->modalHeading(__('admin.modals.order_notice.heading'))
                ->modalDescription(__('admin.modals.order_notice.description'))
                ->action(function (array $data, Order $record): void {
                    $record->notices()->create([
                        'user_id' => auth('admin')->id(),
                        'note' => $data['note'],
                    ]);
                }),
            ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
            DeleteAction::make()->label(__('admin.actions.delete')),
            RestoreAction::make()->visible(fn (): bool => self::canManageTrashed()),
            ForceDeleteAction::make()->visible(fn (): bool => self::canManageTrashed()),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create'))->url(fn (): string => OrderResource::getUrl('create'))->hidden(),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
            RestoreBulkAction::make()->visible(fn (): bool => self::canManageTrashed()),
            ForceDeleteBulkAction::make()->visible(fn (): bool => self::canManageTrashed()),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.orders.heading'))
            ->emptyStateDescription(__('admin.empty_states.orders.description'));
    }
}
