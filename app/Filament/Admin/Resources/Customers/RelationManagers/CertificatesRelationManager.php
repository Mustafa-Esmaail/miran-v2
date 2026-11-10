<?php

namespace App\Filament\Admin\Resources\Customers\RelationManagers;

use App\Enums\CertificateStatus;
use App\Models\Certificate;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CertificatesRelationManager extends RelationManager
{
    protected static string $relationship = 'certificates';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.user_certificates');
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
                    ->state(fn (Certificate $record): string => $record->courseGroup?->course?->getTranslation('title', app()->getLocale()) ?? $record->course?->getTranslation('title', app()->getLocale()) ?? '—'),
                TextColumn::make('courseGroup.title')
                    ->label(__('admin.labels.group'))
                    ->state(fn (Certificate $record): string => $record->courseGroup?->getTranslation('title', app()->getLocale()) ?? '—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('admin.labels.status'))
                    ->formatStateUsing(function ($state) {
                        $value = $state instanceof \BackedEnum ? $state->value : $state;
                        $key = 'admin.options.'.$value;

                        return __($key) !== $key ? __($key) : (string) $value;
                    })
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof CertificateStatus ? $state->value : (string) $state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),
                TextColumn::make('approved_at')->label(__('admin.labels.approved_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    CertificateStatus::Pending->value => __('admin.options.pending'),
                    CertificateStatus::Approved->value => __('admin.options.approved'),
                    CertificateStatus::Rejected->value => __('admin.options.rejected'),
                ])->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.certificates.heading'))
            ->emptyStateDescription(__('admin.empty_states.certificates.description'));
    }
}
