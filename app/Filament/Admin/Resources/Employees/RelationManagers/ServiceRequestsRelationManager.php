<?php

namespace App\Filament\Admin\Resources\Employees\RelationManagers;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServiceRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'user';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.employee_service_requests');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([])->columns(1);
    }

    public function getTableQuery(): Builder
    {
        $owner = $this->getOwnerRecord();
        $userId = $owner->user_id;

        return ServiceRequest::query()
            ->where('assigned_to', $userId);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
                TextColumn::make('name')->label(__('admin.fields.name'))->searchable()->placeholder('—'),
                TextColumn::make('phone')->label(__('admin.fields.phone'))->searchable(),
                TextColumn::make('email')->label(__('admin.fields.email'))->searchable()->toggleable()->placeholder('—'),
                TextColumn::make('source')
                    ->label(__('admin.labels.source'))
                    ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? $state->value : (string) $state)
                    ->badge()
                    ->toggleable(),
                TextColumn::make('type.name')->label(__('admin.labels.type'))->toggleable()->placeholder('—'),
                TextColumn::make('status')
                    ->label(__('admin.labels.status'))
                    ->formatStateUsing(fn ($state) => self::statusesBySlug()->get($state)?->getTranslatedName() ?? (string) $state)
                    ->badge()
                    ->color(fn ($state) => Color::hex(self::statusesBySlug()->get($state)?->color ?? '#6b7280'))
                    ->sortable(),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(fn () => ServiceRequestStatus::query()
                        ->active()
                        ->orderBy('sort_order')
                        ->get()
                        ->mapWithKeys(fn (ServiceRequestStatus $status) => [$status->slug => $status->getTranslatedName()])
                    )
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.service_requests.heading'))
            ->emptyStateDescription(__('admin.empty_states.service_requests.description'));
    }

    /**
     * @return \Illuminate\Support\Collection<string, ServiceRequestStatus>
     */
    private static function statusesBySlug(): \Illuminate\Support\Collection
    {
        static $statuses = null;

        return $statuses ??= ServiceRequestStatus::query()->get()->keyBy('slug');
    }
}
