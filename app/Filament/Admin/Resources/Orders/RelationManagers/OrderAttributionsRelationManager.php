<?php

namespace App\Filament\Admin\Resources\Orders\RelationManagers;

use App\Enums\CommissionLedgerStatus;
use App\Enums\OrderAttributionType;
use App\Filament\Admin\Resources\ServiceRequests\ServiceRequestResource;
use App\Models\OrderAttribution;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrderAttributionsRelationManager extends RelationManager
{
    protected static string $relationship = 'attributions';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.order_attributions');
    }

    public function canCreate(): bool
    {
        return false;
    }

    public function canEdit(Model $record): bool
    {
        return false;
    }

    public function canDelete(Model $record): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('attribution_type')->label(__('admin.labels.type'))
                ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '—')
                ->badge()
                ->color(fn ($state): string => match ($state?->value) {
                    OrderAttributionType::Lead->value => 'info',
                    OrderAttributionType::Manual->value => 'warning',
                    OrderAttributionType::CouponCommission->value => 'success',
                    default => 'gray',
                }),
            TextColumn::make('salesUser.name')->label(__('admin.labels.sales_person'))->searchable(),
            TextColumn::make('serviceRequest.id')->label(__('admin.labels.service_request'))
                ->formatStateUsing(fn (?string $state): string => $state ? '#'.$state : '—')
                ->url(fn (OrderAttribution $record): ?string => $record->service_request_id
                    ? ServiceRequestResource::getUrl('edit', ['record' => $record->service_request_id])
                    : null)
                ->color('primary'),
            TextColumn::make('credited_amount')->label(__('admin.labels.credited_amount'))->sortable(),
            TextColumn::make('commissionLedger.status')->label(__('admin.labels.status'))
                ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '—')
                ->badge()
                ->color(fn ($state): string => match ($state?->value) {
                    CommissionLedgerStatus::Paid->value => 'success',
                    CommissionLedgerStatus::Approved->value => 'info',
                    CommissionLedgerStatus::Pending->value => 'warning',
                    default => 'gray',
                }),
            TextColumn::make('commissionLedger.commission_amount')->label(__('admin.labels.commission_amount'))->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.order_attributions.heading'))
            ->emptyStateDescription(__('admin.empty_states.order_attributions.description'));
    }
}
