<?php

namespace App\Filament\Admin\Resources\CourseDiscounts\Tables;

use App\Models\Discount;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CourseDiscountsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('courses_list')
                ->label(__('admin.labels.courses'))
                ->state(function (Discount $record) {
                    $names = $record->courses->map(fn ($course) => $course->getTranslation('title', app()->getLocale()));

                    return $names->implode(', ');
                })
                ->limit(60)
                ->tooltip(fn (Discount $record) => $record->courses->map(fn ($course) => $course->getTranslation('title', app()->getLocale()))->implode(', ')),
            TextColumn::make('type')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    Discount::TYPE_PERCENTAGE => 'info',
                    Discount::TYPE_FIXED => 'success',
                    default => 'secondary',
                })
                ->sortable(),
            TextColumn::make('amount')
                ->formatStateUsing(fn ($state, Discount $record) => $record->type === Discount::TYPE_PERCENTAGE ? $state.'%' : 'SAR '.number_format((float) $state, 2))
                ->sortable(),
            TextColumn::make('max_amount')->label(__('admin.labels.limit'))->sortable()->toggleable(),
            TextColumn::make('courses_count')->label(__('admin.labels.courses'))->counts('courses')->sortable(),
            IconColumn::make('is_active')->label(__('admin.labels.active'))->boolean(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            TextColumn::make('updated_at')->label(__('admin.labels.updated_at'))->since()->toggleable(),
        ])->filters([
            TernaryFilter::make('is_active')
                ->label(__('admin.labels.active'))
                ->trueLabel(__('admin.options.active'))
                ->falseLabel(__('admin.options.inactive')),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.course_discounts.heading'))
            ->emptyStateDescription(__('admin.empty_states.course_discounts.description'));
    }
}
