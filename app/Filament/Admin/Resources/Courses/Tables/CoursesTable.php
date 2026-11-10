<?php

namespace App\Filament\Admin\Resources\Courses\Tables;

use App\Enums\CourseLevel;
use App\Enums\DeliveryType;
use App\Models\Category;
use App\Models\Course;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CoursesTable
{
    private static function canManageTrashed(): bool
    {
        return auth('admin')->user()?->hasRole(['admin', 'super-admin']) ?? false;
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
                TextColumn::make('title')
                    ->label(__('admin.labels.title'))
                    ->state(fn (Course $record) => $record->getTranslation('title', app()->getLocale()))
                    ->searchable(),
                TextColumn::make('slug')->searchable()->toggleable(),
                TextColumn::make('category.name')
                    ->label(__('admin.labels.category'))
                    ->formatStateUsing(fn ($state, Course $record) => $record->category?->getTranslation('name', app()->getLocale()) ?? '-')
                    ->sortable()->searchable(),
                TextColumn::make('price')->label(__('admin.labels.price'))->money('SAR')->sortable(),
                TextColumn::make('is_free')
                    ->label(__('admin.labels.is_free'))
                    ->formatStateUsing(fn (bool $state): string => $state ? __('admin.options.yes') : __('admin.options.no'))
                    ->toggleable(),
                IconColumn::make('is_featured')
                    ->label(__('admin.labels.is_featured'))
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('sort_order')
                    ->label(__('admin.labels.sort_order'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('final_price')
                    ->label(__('admin.labels.final_price'))
                    ->state(fn (Course $record) => $record->final_price)
                    ->money('SAR')
                    ->sortable(),
                TextColumn::make('delivery_type')
                    ->badge()
                    ->label(__('admin.labels.delivery_type'))
                    ->color(function ($state): string {
                        $value = $state instanceof DeliveryType ? $state->value : (string) $state;

                        return match ($value) {
                            DeliveryType::Online->value => 'success',
                            DeliveryType::Offline->value => 'warning',
                            DeliveryType::Hybrid->value => 'info',
                            DeliveryType::RemoteLive->value => 'primary',
                            default => 'secondary',
                        };
                    })
                    ->sortable(),
                TextColumn::make('course_level')
                    ->label(__('admin.labels.level'))
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        $value = $state instanceof CourseLevel ? $state->value : (string) $state;

                        return match ($value) {
                            CourseLevel::Beginner->value => __('web.courses.level_beginner'),
                            CourseLevel::Intermediate->value => __('web.courses.level_intermediate'),
                            CourseLevel::Advanced->value => __('web.courses.level_advanced'),
                            CourseLevel::Expert->value => __('web.courses.level_expert'),
                            default => $value,
                        };
                    })
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('duration_hours')->label(__('admin.labels.hours'))->sortable(),
                TextColumn::make('duration_group')->label(__('admin.labels.duration'))->toggleable(),
                TextColumn::make('groups_count')->label(__('admin.labels.groups'))->counts('groups')->sortable(),
                TextColumn::make('curriculum_sections_count')->label(__('admin.labels.curriculum_sections'))->counts('curriculumSections')->sortable()->toggleable(),
                TextColumn::make('lessons_count')->label(__('admin.labels.lessons'))->counts('lessons')->sortable()->toggleable(),
                TextColumn::make('enrollments_count')->label(__('admin.labels.enrollments'))->counts('enrollments')->sortable()->toggleable(),
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
                            'published' => 'success',
                            'draft' => 'gray',
                            'archived' => 'danger',
                            default => 'secondary',
                        };
                    })
                    ->sortable(),
                TextColumn::make('start_date')->date()->sortable()->toggleable(),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
                TextColumn::make('updated_at')->label(__('admin.labels.updated_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                SelectFilter::make('status')->options([
                    'draft' => __('admin.options.draft'),
                    'published' => __('admin.options.published'),
                    'archived' => __('admin.options.archived'),
                ])->searchable(),
                SelectFilter::make('delivery_type')->options([
                    DeliveryType::Online->value => __('admin.options.online'),
                    DeliveryType::Offline->value => __('admin.options.offline'),
                    DeliveryType::Hybrid->value => __('admin.options.hybrid'),
                    DeliveryType::RemoteLive->value => __('admin.options.remote_live'),
                ])->searchable(),
                SelectFilter::make('category_id')
                    ->relationship('category', 'slug')
                    ->getOptionLabelFromRecordUsing(fn (Category $record): string => $record->getTranslation('name', app()->getLocale()))
                    ->label(__('admin.labels.category'))
                    ->searchable(),
                TernaryFilter::make('is_featured')->label(__('admin.labels.is_featured')),
                TrashedFilter::make()->visible(fn (): bool => self::canManageTrashed()),
            ])->actions([
                \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
                \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
                RestoreAction::make()->visible(fn (): bool => self::canManageTrashed()),
                ForceDeleteAction::make()->visible(fn (): bool => self::canManageTrashed()),
            ])->headerActions([
                CreateAction::make()->label(__('admin.actions.create')),
            ])->groupedBulkActions([
                DeleteBulkAction::make()->label(__('admin.actions.delete_bulk'))->authorizeIndividualRecords('delete'),
                RestoreBulkAction::make()->visible(fn (): bool => self::canManageTrashed())->authorizeIndividualRecords('restore'),
                ForceDeleteBulkAction::make()->visible(fn (): bool => self::canManageTrashed())->authorizeIndividualRecords('forceDelete'),
            ])
            ->defaultSort('sort_order')
            ->emptyStateHeading(__('admin.empty_states.courses.heading'))
            ->emptyStateDescription(__('admin.empty_states.courses.description'));
    }
}
