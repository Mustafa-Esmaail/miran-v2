<?php

namespace App\Filament\Admin\Resources\Orders\RelationManagers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.order_payments');
    }

    private static function providerLabel(string $provider): string
    {
        return match (strtolower($provider)) {
            'bank_transfer', 'bank' => __('admin.labels.bank_transfer_provider'),
            'tamara' => 'Tamara',
            'cod', 'cash_on_delivery' => __('admin.labels.cod'),
            default => ucfirst($provider),
        };
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('provider')
                ->label(__('admin.labels.provider'))
                ->options([
                    'bank_transfer' => __('admin.labels.bank_transfer_provider'),
                    'tamara' => 'Tamara',
                    'cod' => __('admin.labels.cod'),
                ])
                ->required(),
            TextInput::make('provider_reference')
                ->label(__('admin.labels.provider_reference'))
                ->maxLength(255),
            Select::make('status')
                ->label(__('admin.labels.status'))
                ->options([
                    PaymentStatus::Pending->value => __('admin.options.pending'),
                    PaymentStatus::Authorized->value => __('admin.options.authorized'),
                    PaymentStatus::Paid->value => __('admin.options.paid'),
                    PaymentStatus::Failed->value => __('admin.options.failed'),
                    PaymentStatus::Refunded->value => __('admin.options.refunded'),
                ])
                ->required(),
            TextInput::make('amount')
                ->label(__('admin.labels.amount'))
                ->numeric()
                ->minValue(0)
                ->required(),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('provider')
                ->label(__('admin.labels.provider'))
                ->formatStateUsing(fn (string $state): string => self::providerLabel($state))
                ->badge()
                ->color(fn (string $state): string => match (strtolower($state)) {
                    'bank_transfer', 'bank' => 'info',
                    'tamara' => 'warning',
                    'cod', 'cash_on_delivery' => 'gray',
                    default => 'secondary',
                })
                ->searchable(),
            TextColumn::make('provider_reference')->label(__('admin.labels.reference'))->toggleable(),
            TextColumn::make('status')->label(__('admin.labels.status'))
                ->formatStateUsing(function ($state) {
                    $value = $state instanceof \BackedEnum ? $state->value : $state;
                    $key = 'admin.options.'.$value;

                    return __($key) !== $key ? __($key) : (string) $value;
                })
                ->badge()
                ->color(fn ($state): string => match ($state instanceof PaymentStatus ? $state->value : (string) $state) {
                    'paid' => 'success',
                    'authorized' => 'info',
                    'pending' => 'warning',
                    'failed' => 'danger',
                    'refunded' => 'gray',
                    default => 'secondary',
                })
                ->sortable(),
            TextColumn::make('amount')->label(__('admin.labels.amount'))->sortable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
        ])->actions([
            EditAction::make()->label(__('admin.actions.edit')),
            DeleteAction::make()->label(__('admin.actions.delete'))
                ->visible(fn (): bool => $this->getOwnerRecord()->status !== OrderStatus::Paid),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.order_payments.heading'))
            ->emptyStateDescription(__('admin.empty_states.order_payments.description'));
    }
}
