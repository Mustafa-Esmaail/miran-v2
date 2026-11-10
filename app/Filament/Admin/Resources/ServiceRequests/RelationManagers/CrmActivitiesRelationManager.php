<?php

namespace App\Filament\Admin\Resources\ServiceRequests\RelationManagers;

use App\Enums\CrmActivityOutcome;
use App\Enums\CrmActivityType;
use App\Filament\Admin\Resources\CallEvaluations\CallEvaluationResource;
use App\Models\CallEvaluation;
use App\Models\CrmActivity;
use App\Models\Employee;
use App\Services\CallQualityService;
use App\Support\CallQuality\CallQualityLevels;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class CrmActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'crmActivities';

    /** @var Collection<int, CallEvaluation>|null evaluations of this lead's activities the viewer may see, keyed by crm_activity_id */
    protected ?Collection $visibleCallEvaluations = null;

    /** @var Collection<int, int>|null ids of this lead's activities that already have an evaluation (any evaluator) */
    protected ?Collection $ratedActivityIds = null;

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.crm_activities');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('type')
                ->label(__('admin.labels.type'))
                ->options(collect(CrmActivityType::cases())->mapWithKeys(
                    fn (CrmActivityType $t) => [$t->value => __('admin.options.'.$t->value)]
                ))
                ->required(),
            Select::make('outcome')
                ->label(__('admin.labels.outcome'))
                ->options(collect(CrmActivityOutcome::cases())->mapWithKeys(
                    fn (CrmActivityOutcome $o) => [$o->value => __('admin.options.'.$o->value)]
                ))
                ->nullable(),
            TextInput::make('subject')
                ->label(__('admin.labels.subject'))
                ->required()
                ->maxLength(190)
                ->columnSpanFull(),
            Textarea::make('body')
                ->label(__('admin.labels.body'))
                ->rows(3)
                ->columnSpanFull(),
            DateTimePicker::make('scheduled_at')
                ->label(__('admin.labels.scheduled_at'))
                ->seconds(false),
            DateTimePicker::make('completed_at')
                ->label(__('admin.labels.completed_at'))
                ->seconds(false),
            Select::make('employee_id')
                ->label(__('admin.labels.logged_by'))
                ->options(fn () => Employee::query()
                    ->whereNotNull('user_id')
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->default(fn () => Employee::query()->where('user_id', auth()->id())->value('id'))
                ->searchable()
                ->preload(),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label(__('admin.labels.type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '—')
                    ->color(fn ($state) => match ($state?->value) {
                        'call' => 'info',
                        'whatsapp' => 'success',
                        'email' => 'primary',
                        'meeting' => 'warning',
                        'visit' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('subject')->label(__('admin.labels.subject'))->limit(50),
                TextColumn::make('outcome')
                    ->label(__('admin.labels.outcome'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '—')
                    ->color(fn ($state) => match ($state?->value) {
                        'interested' => 'success',
                        'reached', 'callback' => 'info',
                        'no_answer' => 'warning',
                        'not_interested' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('call_evaluation')
                    ->label(__('admin.call_quality.lead.evaluation_column'))
                    ->badge()
                    ->state(fn (CrmActivity $record): ?string => $this->evaluationBadge($record))
                    ->color(fn (CrmActivity $record): string => CallQualityLevels::color($this->visibleCallEvaluations()->get($record->getKey())?->level))
                    ->url(fn (CrmActivity $record): ?string => $this->evaluationUrl($record))
                    ->placeholder('—')
                    ->visible(fn (): bool => auth('admin')->user()?->can('call_evaluations.viewAny') ?? false),
                TextColumn::make('employee.name')->label(__('admin.labels.logged_by'))->toggleable(),
                TextColumn::make('scheduled_at')->label(__('admin.labels.scheduled_at'))->dateTime('Y-m-d H:i')->toggleable(),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->headerActions([
                CreateAction::make()->label(__('admin.actions.log_activity'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_at'] = now();

                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('evaluate_call')
                    ->label(__('admin.call_quality.lead.evaluate_call'))
                    ->icon('heroicon-o-star')
                    ->color('gray')
                    ->visible(fn (CrmActivity $record): bool => $this->canEvaluateActivity($record))
                    ->url(fn (CrmActivity $record): string => CallEvaluationResource::getUrl('create', [
                        'service_request' => $this->getOwnerRecord()->getKey(),
                        'crm_activity' => $record->getKey(),
                    ])),
                EditAction::make()->label(__('admin.actions.edit')),
                DeleteAction::make()->label(__('admin.actions.delete')),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.crm_activities.heading'))
            ->emptyStateDescription(__('admin.empty_states.crm_activities.description'));
    }

    private function canEvaluateActivity(CrmActivity $activity): bool
    {
        return $activity->type === CrmActivityType::Call
            && ! $this->ratedActivityIds()->contains($activity->getKey())
            && app(CallQualityService::class)->canEvaluate(auth('admin')->user());
    }

    private function evaluationBadge(CrmActivity $activity): ?string
    {
        $evaluation = $this->visibleCallEvaluations()->get($activity->getKey());

        if ($evaluation === null) {
            return null;
        }

        return __('admin.call_quality.lead.rated_badge', [
            'percent' => number_format((float) $evaluation->score_percent, 0).'%',
            'level' => CallQualityLevels::label($evaluation->level),
        ]);
    }

    private function evaluationUrl(CrmActivity $activity): ?string
    {
        $evaluation = $this->visibleCallEvaluations()->get($activity->getKey());

        if ($evaluation === null || ! (auth('admin')->user()?->can('view', $evaluation) ?? false)) {
            return null;
        }

        return CallEvaluationResource::getUrl('view', ['record' => $evaluation]);
    }

    /**
     * One query per request for every activity row of this lead.
     *
     * @return Collection<int, int>
     */
    private function ratedActivityIds(): Collection
    {
        return $this->ratedActivityIds ??= CallEvaluation::query()
            ->whereIn('crm_activity_id', $this->getOwnerRecord()->crmActivities()->select('id'))
            ->pluck('crm_activity_id');
    }

    /**
     * One query per request, scoped to the employees the viewer may see.
     *
     * @return Collection<int, CallEvaluation>
     */
    private function visibleCallEvaluations(): Collection
    {
        if ($this->visibleCallEvaluations !== null) {
            return $this->visibleCallEvaluations;
        }

        $user = auth('admin')->user();

        if (! ($user?->can('call_evaluations.viewAny') ?? false)) {
            return $this->visibleCallEvaluations = collect();
        }

        return $this->visibleCallEvaluations = app(CallQualityService::class)
            ->scopeEvaluations(CallEvaluation::query(), $user)
            ->whereIn('crm_activity_id', $this->getOwnerRecord()->crmActivities()->select('id'))
            ->get(['id', 'employee_id', 'crm_activity_id', 'score_percent', 'level'])
            ->keyBy('crm_activity_id');
    }
}
