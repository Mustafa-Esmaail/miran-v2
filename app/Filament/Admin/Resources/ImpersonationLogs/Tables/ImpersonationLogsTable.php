<?php

namespace App\Filament\Admin\Resources\ImpersonationLogs\Tables;

use App\Enums\ImpersonationEventType;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ImpersonationLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
                TextColumn::make('admin.name')
                    ->label(__('admin.labels.admin_user'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('student.name')
                    ->label(__('admin.labels.student'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('event_type')
                    ->label(__('admin.labels.event_type'))
                    ->formatStateUsing(function ($state): string {
                        $value = $state instanceof \BackedEnum ? $state->value : (string) $state;
                        $key = 'admin.options.'.$value;

                        return __($key) !== $key ? __($key) : $value;
                    })
                    ->badge()
                    ->sortable(),
                TextColumn::make('route_name')->label(__('admin.labels.route_name'))->toggleable(),
                TextColumn::make('url')->label(__('admin.labels.url'))->limit(60)->toggleable()->wrap(),
                TextColumn::make('ip_address')->label(__('admin.labels.ip_address'))->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('admin.labels.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->options(collect(ImpersonationEventType::cases())->mapWithKeys(
                        fn (ImpersonationEventType $type): array => [$type->value => __('admin.options.'.$type->value)]
                    ))
                    ->native(false),
                SelectFilter::make('admin_id')
                    ->label(__('admin.labels.admin_user'))
                    ->relationship('admin', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('student_id')
                    ->label(__('admin.labels.student'))
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make()->label(__('admin.pages.view')),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.impersonation_logs.heading'))
            ->emptyStateDescription(__('admin.empty_states.impersonation_logs.description'));
    }
}
