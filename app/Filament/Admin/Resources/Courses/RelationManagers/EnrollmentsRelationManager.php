<?php

namespace App\Filament\Admin\Resources\Courses\RelationManagers;

use App\Enums\EnrollmentStatus;
use App\Filament\Admin\Resources\Enrollments\EnrollmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.course_enrollments');
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('user.email')->label(__('admin.labels.trainee'))->searchable(),
            TextColumn::make('courseGroup.title')->label(__('admin.labels.group')),
            TextColumn::make('status')->label(__('admin.labels.status'))
                ->formatStateUsing(function ($state) {
                    $value = $state instanceof \BackedEnum ? $state->value : $state;
                    $key = 'admin.options.'.$value;

                    return __($key) !== $key ? __($key) : (string) $value;
                })
                ->badge()
                ->color(function ($state): string {
                    $value = $state instanceof EnrollmentStatus ? $state->value : (string) $state;

                    return match ($value) {
                        EnrollmentStatus::Active->value => 'success',
                        EnrollmentStatus::Pending->value => 'warning',
                        EnrollmentStatus::Cancelled->value => 'danger',
                        default => 'secondary',
                    };
                }),
        ])->actions([
            EditAction::make()->label(__('admin.actions.edit'))
                ->modal(false)
                ->url(fn ($record) => EnrollmentResource::getUrl('edit', ['record' => $record])),
        ])
            ->recordUrl(fn ($record) => EnrollmentResource::getUrl('edit', ['record' => $record]))
            ->emptyStateHeading(__('admin.empty_states.course_enrollments.heading'))
            ->emptyStateDescription(__('admin.empty_states.course_enrollments.description'));
    }
}
