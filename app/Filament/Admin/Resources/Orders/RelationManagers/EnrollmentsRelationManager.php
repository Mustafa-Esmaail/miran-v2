<?php

namespace App\Filament\Admin\Resources\Orders\RelationManagers;

use App\Enums\EnrollmentStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.order_enrollments');
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
            TextColumn::make('user.name')->label(__('admin.labels.user'))->searchable(),
            TextColumn::make('course.title')->label(__('admin.labels.course'))->toggleable(),
            TextColumn::make('courseGroup.title')->label(__('admin.labels.course_group'))->toggleable(),
            IconColumn::make('is_invited')->label(__('admin.labels.is_invited_seat'))->boolean(),
            TextColumn::make('status')->label(__('admin.labels.status'))
                ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '—')
                ->badge()
                ->color(fn ($state): string => match ($state?->value) {
                    EnrollmentStatus::Active->value => 'success',
                    EnrollmentStatus::Pending->value, EnrollmentStatus::PendingCertificate->value => 'warning',
                    EnrollmentStatus::Cancelled->value => 'danger',
                    default => 'gray',
                })
                ->sortable(),
            TextColumn::make('activated_at')->label(__('admin.labels.activated_at'))->dateTime('Y-m-d H:i')->placeholder('—')->toggleable(),
        ])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading(__('admin.empty_states.order_enrollments.heading'))
            ->emptyStateDescription(__('admin.empty_states.order_enrollments.description'));
    }
}
