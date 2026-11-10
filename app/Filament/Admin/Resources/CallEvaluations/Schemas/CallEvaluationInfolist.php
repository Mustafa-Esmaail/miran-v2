<?php

namespace App\Filament\Admin\Resources\CallEvaluations\Schemas;

use App\Filament\Admin\Resources\CrmActivities\CrmActivityResource;
use App\Filament\Admin\Resources\ServiceRequests\ServiceRequestResource;
use App\Models\CallEvaluation;
use App\Models\CallQualityCriterion;
use App\Support\CallQuality\CallScore;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class CallEvaluationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $criteria = CallEvaluationForm::criteria();

        return $schema->components([
            Section::make(__('admin.call_quality.sections.call'))
                ->schema([
                    TextEntry::make('id')
                        ->label(__('admin.labels.id'))
                        ->prefix('#'),
                    TextEntry::make('call_date')
                        ->label(__('admin.call_quality.fields.call_date'))
                        ->date('Y-m-d'),
                    TextEntry::make('employee.name')
                        ->label(__('admin.call_quality.fields.employee'))
                        ->placeholder('—'),
                    TextEntry::make('employee.team.name')
                        ->label(__('admin.call_quality.fields.team'))
                        ->placeholder('—'),
                    TextEntry::make('call_phone')
                        ->label(__('admin.call_quality.fields.call_phone'))
                        ->extraAttributes(['dir' => 'ltr'])
                        ->copyable(),
                    TextEntry::make('evaluator.name')
                        ->label(__('admin.call_quality.fields.evaluator'))
                        ->placeholder('—'),
                    TextEntry::make('service_request_id')
                        ->label(__('admin.call_quality.fields.lead'))
                        ->formatStateUsing(fn (CallEvaluation $record): ?string => CallEvaluationForm::leadLabel($record->serviceRequest))
                        ->url(fn (CallEvaluation $record): ?string => $record->service_request_id
                            ? ServiceRequestResource::getUrl('view', ['record' => $record->service_request_id])
                            : null)
                        ->color('primary')
                        ->placeholder('—'),
                    TextEntry::make('crm_activity_id')
                        ->label(__('admin.call_quality.fields.activity'))
                        ->formatStateUsing(fn (CallEvaluation $record): ?string => CallEvaluationForm::activityLabel($record->crmActivity))
                        ->url(fn (CallEvaluation $record): ?string => $record->crm_activity_id
                            ? CrmActivityResource::getUrl('view', ['record' => $record->crm_activity_id])
                            : null)
                        ->color('primary')
                        ->placeholder('—'),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make(__('admin.call_quality.sections.stages'))
                ->schema([
                    ...$criteria->map(fn (CallQualityCriterion $criterion): Grid => Grid::make(['default' => 1, 'lg' => 12])
                        ->schema([
                            TextEntry::make('score_'.$criterion->key)
                                ->label(CallEvaluationForm::criterionLabel($criterion))
                                ->state(fn (CallEvaluation $record): ?int => isset($record->scores[$criterion->key]) ? (int) $record->scores[$criterion->key] : null)
                                ->formatStateUsing(fn (int $state): string => CallEvaluationForm::scoreOptions()[$state] ?? (string) $state)
                                ->badge()
                                ->color(fn (?int $state): string => CallEvaluationForm::scoreColor($state))
                                ->placeholder('—')
                                ->columnSpan(['default' => 1, 'lg' => 5]),
                            TextEntry::make('note_'.$criterion->key)
                                ->label(__('admin.call_quality.fields.criterion_note'))
                                ->state(fn (CallEvaluation $record): ?string => $record->notes[$criterion->key] ?? null)
                                ->placeholder('—')
                                ->columnSpan(['default' => 1, 'lg' => 7]),
                        ]))->all(),
                    View::make(CallEvaluationForm::SUMMARY_VIEW)
                        ->viewData(fn (CallEvaluation $record): array => [
                            'summary' => CallScore::calculate($record->scores ?? [], $criteria),
                            'criteriaCount' => $criteria->count(),
                        ]),
                ])
                ->columnSpanFull(),
            Section::make(__('admin.call_quality.sections.general_notes'))
                ->schema([
                    TextEntry::make('general_notes')
                        ->hiddenLabel()
                        ->placeholder('—'),
                ])
                ->columnSpanFull(),
            Section::make(__('admin.call_quality.sections.review'))
                ->schema([
                    TextEntry::make('discussed_at')
                        ->label(__('admin.call_quality.fields.discussed'))
                        ->formatStateUsing(fn (CallEvaluation $record): ?string => CallEvaluationForm::stamp($record->discussedBy, $record->discussed_at))
                        ->placeholder(__('admin.call_quality.helpers.not_yet')),
                    TextEntry::make('approved_at')
                        ->label(__('admin.call_quality.fields.approved'))
                        ->formatStateUsing(fn (CallEvaluation $record): ?string => CallEvaluationForm::stamp($record->approvedBy, $record->approved_at))
                        ->placeholder(__('admin.call_quality.helpers.not_yet')),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
