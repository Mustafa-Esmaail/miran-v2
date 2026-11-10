<?php

namespace App\Filament\Admin\Resources\Companies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
                TextColumn::make('name')->label(__('admin.labels.name'))->searchable()->limit(50),
                TextColumn::make('industry')->label(__('admin.labels.industry'))->toggleable(),
                TextColumn::make('size')
                    ->label(__('admin.labels.company_size'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state) : '—')
                    ->color(fn ($state) => match ($state) {
                        'enterprise' => 'danger',
                        'large' => 'warning',
                        'medium' => 'info',
                        'small', 'micro' => 'gray',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('accountManager.name')->label(__('admin.labels.account_manager'))->toggleable(),
                TextColumn::make('deals_count')
                    ->label(__('admin.labels.deals'))
                    ->counts('deals')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('size')
                    ->label(__('admin.labels.company_size'))
                    ->options([
                        'micro' => __('admin.options.micro'),
                        'small' => __('admin.options.small'),
                        'medium' => __('admin.options.medium'),
                        'large' => __('admin.options.large'),
                        'enterprise' => __('admin.options.enterprise'),
                    ])->searchable(),
            ])
            ->recordActions([
                ViewAction::make()->label(__('admin.pages.view')),
                EditAction::make()->label(__('admin.actions.edit')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
