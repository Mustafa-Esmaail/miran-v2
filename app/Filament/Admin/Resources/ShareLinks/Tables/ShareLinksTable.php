<?php

namespace App\Filament\Admin\Resources\ShareLinks\Tables;

use App\Filament\Admin\Resources\ShareLinks\Schemas\ShareLinkForm;
use App\Filament\Admin\Resources\ShareLinks\ShareLinkResource;
use App\Models\Employee;
use App\Models\ShareLink;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShareLinksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
                TextColumn::make('employee.name')
                    ->label(__('admin.models.employee'))
                    ->formatStateUsing(fn (?string $state, ShareLink $record): string => collect([$record->employee?->employee_code, $state])->filter()->implode(' - '))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'employee',
                        fn (Builder $employee): Builder => $employee
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('employee_code', 'like', "%{$search}%")
                    )),
                TextColumn::make('target_label')
                    ->label(__('admin.labels.target'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('full_url')
                    ->label(__('admin.labels.full_url'))
                    ->copyable()
                    ->copyableState(fn (ShareLink $record): string => $record->full_url)
                    ->copyMessage(__('admin.messages.copied'))
                    ->limit(60)
                    ->tooltip(fn (ShareLink $record): string => $record->full_url),
                static::urlCopyColumn(),
                TextColumn::make('target_type')
                    ->label(__('admin.labels.target_type'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'course' => 'info',
                        'bundle' => 'warning',
                        'category' => 'success',
                        'homepage' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => __('admin.options.'.$state)),
                TextColumn::make('createdBy.name')
                    ->label(__('admin.labels.created_by'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('admin.labels.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('employee_id')
                    ->label(__('admin.models.employee'))
                    ->options(fn (): array => ShareLinkResource::employeeScope()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (Employee $e): array => [$e->id => "{$e->name} ({$e->employee_code})"])
                        ->all()
                    )
                    ->searchable(),
                static::targetFilter(),
            ])
            ->recordActions([
                ViewAction::make()->label(__('admin.pages.view')),
                DeleteAction::make()->label(__('admin.actions.delete')),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.share_links.heading'))
            ->emptyStateDescription(__('admin.empty_states.share_links.description'));
    }

    /**
     * Icon-only cell that copies the full share URL, matching the phone copy
     * button in the service requests table.
     */
    public static function urlCopyColumn(): TextColumn
    {
        return TextColumn::make('full_url_copy')
            ->label('')
            ->state(fn (ShareLink $record): string => (string) $record->full_url)
            ->formatStateUsing(fn (): string => '')
            ->icon('heroicon-o-clipboard-document')
            ->iconColor('gray')
            ->extraAttributes(['class' => 'fi-ta-icon-btn fi-ta-icon-btn-neutral'])
            ->copyable()
            ->copyableState(fn (ShareLink $record): string => (string) $record->full_url)
            ->copyMessage(__('admin.messages.copied'));
    }

    /**
     * Target type, then (for course / bundle / category) the specific record.
     */
    public static function targetFilter(): Filter
    {
        return Filter::make('target')
            ->schema([
                Select::make('target_type')
                    ->label(__('admin.labels.target_type'))
                    ->options(ShareLinkForm::targetTypeOptions())
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('target_id', null)),
                Select::make('target_id')
                    ->label(__('admin.labels.target'))
                    ->options(fn (Get $get): array => ShareLinkForm::targetOptions($get('target_type')))
                    ->searchable()
                    ->visible(fn (Get $get): bool => ShareLinkForm::hasSpecificTarget($get('target_type'))),
            ])
            ->columns(2)
            ->columnSpan(2)
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when($data['target_type'] ?? null, fn (Builder $q, string $type): Builder => $q->where('target_type', $type))
                ->when(
                    ShareLinkForm::hasSpecificTarget($data['target_type'] ?? null) && filled($data['target_id'] ?? null),
                    fn (Builder $q): Builder => $q->where('target_id', $data['target_id'])
                ))
            ->indicateUsing(function (array $data): array {
                $type = $data['target_type'] ?? null;

                if (! $type) {
                    return [];
                }

                $label = ShareLinkForm::targetTypeOptions()[$type] ?? $type;

                if (ShareLinkForm::hasSpecificTarget($type) && filled($data['target_id'] ?? null)) {
                    $label .= ': '.(ShareLinkForm::targetOptions($type)[$data['target_id']] ?? $data['target_id']);
                }

                return [Indicator::make($label)];
            });
    }
}
