<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class FailedPaymentsQueueWidget extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.queues.failed_payments.heading');
    }

    public static function canView(): bool
    {
        return auth('admin')->user()?->can('payments.viewAny') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->with(['order.user'])
                    ->where('status', PaymentStatus::Failed)
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('order.order_no')
                    ->label(__('admin.labels.order'))
                    ->searchable(),
                TextColumn::make('order.user.email')
                    ->label(__('admin.labels.user'))
                    ->searchable(),
                TextColumn::make('provider')
                    ->label(__('admin.labels.provider')),
                TextColumn::make('amount')
                    ->label(__('admin.labels.total'))
                    ->formatStateUsing(fn ($state): string => 'SAR '.number_format((float) $state, 2)),
                TextColumn::make('created_at')
                    ->label(__('admin.dashboard_widgets.queues.columns.failed_at'))
                    ->since(),
            ])
            ->actions([
                Action::make('view_payments')
                    ->label(__('admin.dashboard_widgets.queues.failed_payments.all_failed'))
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn (): string => PaymentResource::getUrl('index', [
                        'tableFilters' => ['status' => ['value' => PaymentStatus::Failed->value]],
                    ])),
                Action::make('view_order')
                    ->label(__('admin.dashboard_widgets.queues.failed_payments.view_order'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (Payment $record): string => $record->order ? OrderResource::getUrl('edit', ['record' => $record->order]) : OrderResource::getUrl('index'))
                    ->visible(fn (Payment $record): bool => $record->order !== null),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('admin.dashboard_widgets.queues.failed_payments.empty'));
    }
}
