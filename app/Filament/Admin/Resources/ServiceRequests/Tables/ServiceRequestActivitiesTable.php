<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Tables;

use App\Enums\ServiceRequestActivityType;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ServiceRequestActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
                TextColumn::make('serviceRequest.id')
                    ->label(__('admin.labels.service_request'))
                    ->sortable()
                    ->toggleable()
                    ->headerFilter(
                        SelectFilter::make('service_request_id')
                            ->relationship('serviceRequest', 'id')
                            ->searchable()
                            ->preload()
                            ->native(false)
                    ),
                TextColumn::make('type')
                    ->label(__('admin.labels.activity_type'))
                    ->formatStateUsing(function ($state): string {
                        $value = $state instanceof \BackedEnum ? $state->value : (string) $state;
                        $key = 'admin.options.'.$value;

                        return __($key) !== $key ? __($key) : $value;
                    })
                    ->badge()
                    ->headerFilter(
                        SelectFilter::make('type')
                            ->options(collect(ServiceRequestActivityType::cases())->mapWithKeys(
                                fn (ServiceRequestActivityType $type): array => [$type->value => __('admin.options.'.$type->value)]
                            ))
                            ->searchable()
                            ->native(false)
                    )
                    ->sortable(),
                TextColumn::make('actor.name')
                    ->label(__('admin.labels.actor'))
                    ->searchable()
                    ->toggleable()
                    ->headerFilter(
                        SelectFilter::make('actor_user_id')
                            ->relationship('actor', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                    ),
                TextColumn::make('from_value')->label(__('admin.labels.from_value'))->toggleable(),
                TextColumn::make('to_value')->label(__('admin.labels.to_value'))->toggleable(),
                TextColumn::make('note')->label(__('admin.labels.note'))->limit(60)->wrap(),
                TextColumn::make('created_at')
                    ->label(__('admin.labels.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()->label(__('admin.pages.view')),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.service_request_activities.heading'))
            ->emptyStateDescription(__('admin.empty_states.service_request_activities.description'));
    }
}
