<?php

namespace App\Filament\Admin\Resources\Customers\RelationManagers;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.user_enrollments');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
                TextColumn::make('course_title')
                    ->label(__('admin.labels.course'))
                    ->state(fn (Enrollment $record): string => $record->courseGroup?->course?->getTranslation('title', app()->getLocale()) ?? $record->courseGroup?->course?->slug ?? '—'),
                TextColumn::make('courseGroup.title')
                    ->label(__('admin.labels.group'))
                    ->state(fn (Enrollment $record): string => $record->courseGroup?->getTranslation('title', app()->getLocale()) ?? '—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('admin.labels.status'))
                    ->formatStateUsing(function ($state) {
                        $value = $state instanceof \BackedEnum ? $state->value : $state;
                        $key = 'admin.options.'.$value;

                        return __($key) !== $key ? __($key) : (string) $value;
                    })
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof EnrollmentStatus ? $state->value : (string) $state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        'pending_certificate' => 'info',
                        default => 'secondary',
                    })
                    ->sortable(),
                TextColumn::make('activated_at')->label(__('admin.labels.activated_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    EnrollmentStatus::Pending->value => __('admin.options.pending'),
                    EnrollmentStatus::Active->value => __('admin.options.active'),
                    EnrollmentStatus::Cancelled->value => __('admin.options.cancelled'),
                    EnrollmentStatus::PendingCertificate->value => __('admin.options.pending_certificate'),
                ])->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.enrollments.heading'))
            ->emptyStateDescription(__('admin.empty_states.enrollments.description'));
    }
}
