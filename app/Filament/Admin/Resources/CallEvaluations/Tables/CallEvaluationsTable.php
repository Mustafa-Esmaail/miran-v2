<?php

namespace App\Filament\Admin\Resources\CallEvaluations\Tables;

use App\Filament\Admin\Resources\CallEvaluations\Actions\CallEvaluationReviewActions;
use App\Filament\Admin\Resources\CallEvaluations\Schemas\CallEvaluationForm;
use App\Models\CallEvaluation;
use App\Models\CallQualityCriterion;
use App\Support\CallQuality\CallQualityLevels;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CallEvaluationsTable
{
    public static function configure(Table $table): Table
    {
        $criteria = CallEvaluationForm::criteria();

        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('admin.labels.id'))
                    ->prefix('#')
                    ->sortable(),
                TextColumn::make('call_date')
                    ->label(__('admin.call_quality.fields.call_date'))
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('employee.name')
                    ->label(__('admin.call_quality.fields.employee'))
                    ->searchable(),
                TextColumn::make('employee.team.name')
                    ->label(__('admin.call_quality.fields.team'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('call_phone')
                    ->label(__('admin.call_quality.fields.call_phone'))
                    ->searchable()
                    ->extraAttributes(['dir' => 'ltr']),
                TextColumn::make('evaluator.name')
                    ->label(__('admin.call_quality.fields.evaluator'))
                    ->placeholder('—')
                    ->toggleable(),
                ...$criteria->map(fn (CallQualityCriterion $criterion): TextColumn => TextColumn::make('score_'.$criterion->key)
                    ->label($criterion->name)
                    ->state(fn (CallEvaluation $record): ?int => isset($record->scores[$criterion->key]) ? (int) $record->scores[$criterion->key] : null)
                    ->badge()
                    ->color(fn (?int $state): string => CallEvaluationForm::scoreColor($state))
                    ->placeholder('—')
                    ->alignCenter()
                    ->toggleable())->all(),
                TextColumn::make('score_percent')
                    ->label(__('admin.call_quality.fields.score_percent'))
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 1).'%')
                    ->placeholder('—')
                    ->extraAttributes(['dir' => 'ltr'])
                    ->sortable(),
                TextColumn::make('level')
                    ->label(__('admin.call_quality.fields.level'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => CallQualityLevels::label($state))
                    ->color(fn (?string $state): string => CallQualityLevels::color($state))
                    ->placeholder('—'),
                IconColumn::make('discussed_at')
                    ->label(__('admin.call_quality.fields.discussed'))
                    ->state(fn (CallEvaluation $record): bool => $record->isDiscussed())
                    ->boolean()
                    ->tooltip(fn (CallEvaluation $record): ?string => CallEvaluationForm::stamp($record->discussedBy, $record->discussed_at))
                    ->alignCenter(),
                IconColumn::make('approved_at')
                    ->label(__('admin.call_quality.fields.approved'))
                    ->state(fn (CallEvaluation $record): bool => $record->isApproved())
                    ->boolean()
                    ->tooltip(fn (CallEvaluation $record): ?string => CallEvaluationForm::stamp($record->approvedBy, $record->approved_at))
                    ->alignCenter(),
            ])
            ->filters([
                Filter::make('call_date')
                    ->schema([
                        DatePicker::make('from')->label(__('admin.call_quality.filters.date_from')),
                        DatePicker::make('until')->label(__('admin.call_quality.filters.date_until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $inner, string $date): Builder => $inner->whereDate('call_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $inner, string $date): Builder => $inner->whereDate('call_date', '<=', $date)))
                    ->indicateUsing(fn (array $data): array => array_values(array_filter([
                        filled($data['from'] ?? null) ? __('admin.call_quality.filters.date_from').': '.$data['from'] : null,
                        filled($data['until'] ?? null) ? __('admin.call_quality.filters.date_until').': '.$data['until'] : null,
                    ]))),
                SelectFilter::make('employee_id')
                    ->label(__('admin.call_quality.fields.employee'))
                    ->options(fn (): array => CallEvaluationForm::employeeOptions(includeOwn: true))
                    ->searchable(),
                SelectFilter::make('level')
                    ->label(__('admin.call_quality.fields.level'))
                    ->options(CallQualityLevels::options()),
                TernaryFilter::make('discussed_at')
                    ->label(__('admin.call_quality.fields.discussed'))
                    ->nullable(),
                TernaryFilter::make('approved_at')
                    ->label(__('admin.call_quality.fields.approved'))
                    ->nullable(),
            ])
            ->recordActions([
                ViewAction::make()->label(__('admin.pages.view')),
                EditAction::make()->label(__('admin.actions.edit')),
                ActionGroup::make(CallEvaluationReviewActions::make()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
                ]),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query->orderByDesc('call_date')->orderByDesc('id'));
    }

    /**
     * Export headings, matching the table columns (one per active criterion).
     *
     * @param  Collection<int, CallQualityCriterion>  $criteria
     * @return array<int, string>
     */
    public static function exportHeadings(Collection $criteria): array
    {
        return [
            __('admin.labels.id'),
            __('admin.call_quality.fields.call_date'),
            __('admin.call_quality.fields.employee'),
            __('admin.call_quality.fields.team'),
            __('admin.call_quality.fields.call_phone'),
            __('admin.call_quality.fields.evaluator'),
            ...$criteria->map(fn (CallQualityCriterion $criterion): string => (string) $criterion->name)->all(),
            __('admin.call_quality.fields.score_percent'),
            __('admin.call_quality.fields.level'),
            __('admin.call_quality.fields.discussed'),
            __('admin.call_quality.fields.approved'),
            __('admin.call_quality.fields.general_notes'),
        ];
    }

    /**
     * @param  Collection<int, CallQualityCriterion>  $criteria
     * @return array<int, string|int|float|null>
     */
    public static function exportRow(CallEvaluation $evaluation, Collection $criteria): array
    {
        return [
            $evaluation->getKey(),
            $evaluation->call_date?->format('Y-m-d'),
            $evaluation->employee?->name,
            $evaluation->employee?->team?->name,
            $evaluation->call_phone,
            $evaluation->evaluator?->name,
            ...$criteria->map(fn (CallQualityCriterion $criterion): ?int => isset($evaluation->scores[$criterion->key]) ? (int) $evaluation->scores[$criterion->key] : null)->all(),
            $evaluation->score_percent === null ? null : round((float) $evaluation->score_percent, 1),
            CallQualityLevels::label($evaluation->level),
            CallEvaluationForm::stamp($evaluation->discussedBy, $evaluation->discussed_at) ?? '',
            CallEvaluationForm::stamp($evaluation->approvedBy, $evaluation->approved_at) ?? '',
            $evaluation->general_notes,
        ];
    }
}
