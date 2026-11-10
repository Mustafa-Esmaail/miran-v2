<?php

namespace App\Filament\Admin\Resources\ServiceRequests\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.service_request_activities');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('type')
                ->label(__('admin.labels.activity_type'))
                ->formatStateUsing(function ($state) {
                    $value = $state instanceof \BackedEnum ? $state->value : (string) $state;
                    $key = 'admin.options.'.$value;

                    return __($key) !== $key ? __($key) : $value;
                })
                ->badge()
                ->sortable(),
            TextColumn::make('actor.name')->label(__('admin.labels.actor'))->toggleable(),
            TextColumn::make('from_value')->label(__('admin.labels.from_value'))->toggleable(),
            TextColumn::make('to_value')->label(__('admin.labels.to_value'))->toggleable(),
            TextColumn::make('note')->label(__('admin.labels.note'))->limit(60)->wrap(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.service_request_activities.heading'))
            ->emptyStateDescription(__('admin.empty_states.service_request_activities.description'));
    }
}
