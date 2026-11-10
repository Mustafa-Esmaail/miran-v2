<?php

namespace App\Filament\Admin\Resources\CallQualityCriteria\Tables;

use App\Models\CallQualityCriterion;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

class CallQualityCriteriaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->paginated(false)
            ->description(fn (): View => view('filament.resources.call-quality-criteria.weight-summary', [
                'activeWeightTotal' => self::activeWeightTotal(),
            ]))
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.call_quality.criteria.name'))
                    ->state(fn (CallQualityCriterion $record): string => (string) $record->getTranslation('name', app()->getLocale()))
                    ->weight('medium'),
                TextColumn::make('weight')
                    ->label(__('admin.call_quality.criteria.weight'))
                    ->formatStateUsing(fn (float|int|string|null $state): string => self::formatWeight((float) $state).'%')
                    ->extraAttributes(['dir' => 'ltr'])
                    ->alignEnd(),
                ToggleColumn::make('is_active')
                    ->label(__('admin.call_quality.criteria.is_active')),
                TextColumn::make('key')
                    ->label(__('admin.call_quality.criteria.key'))
                    ->badge()
                    ->color('gray'),
            ])
            ->recordActions([
                EditAction::make()->label(__('admin.pages.edit')),
                DeleteAction::make(),
            ]);
    }

    public static function activeWeightTotal(): float
    {
        return (float) CallQualityCriterion::query()->active()->sum('weight');
    }

    public static function formatWeight(float $weight): string
    {
        return rtrim(rtrim(number_format($weight, 2, '.', ''), '0'), '.');
    }
}
