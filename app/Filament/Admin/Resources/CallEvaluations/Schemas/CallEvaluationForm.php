<?php

namespace App\Filament\Admin\Resources\CallEvaluations\Schemas;

use App\Models\CallEvaluation;
use App\Models\CallQualityCriterion;
use App\Models\CrmActivity;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\CallQualityService;
use App\Support\CallQuality\CallScore;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CallEvaluationForm
{
    public const SUMMARY_VIEW = 'filament.resources.call-evaluations.score-summary';

    public static function configure(Schema $schema): Schema
    {
        $criteria = self::criteria();

        return $schema->components([
            Section::make(__('admin.call_quality.sections.call'))
                ->schema([
                    Select::make('employee_id')
                        ->label(__('admin.call_quality.fields.employee'))
                        ->options(fn (?CallEvaluation $record): array => self::employeeOptions($record?->employee_id))
                        ->searchable()
                        ->required(),
                    DatePicker::make('call_date')
                        ->label(__('admin.call_quality.fields.call_date'))
                        ->default(fn (): string => now()->toDateString())
                        ->required(),
                    Select::make('service_request_id')
                        ->label(__('admin.call_quality.fields.lead'))
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search): array => self::searchLeads($search))
                        ->getOptionLabelUsing(fn (mixed $value): ?string => self::leadLabel(ServiceRequest::query()->find($value)))
                        ->live()
                        ->afterStateUpdated(function (mixed $state, Set $set): void {
                            self::applyLeadPrefill($state, $set);
                        }),
                    Select::make('crm_activity_id')
                        ->label(__('admin.call_quality.fields.activity'))
                        ->options(fn (Get $get, ?CallEvaluation $record): array => self::activityOptions($get('service_request_id'), $record?->getKey()))
                        ->placeholder(__('admin.call_quality.placeholders.activity'))
                        ->live()
                        ->afterStateUpdated(function (mixed $state, Set $set): void {
                            self::applyActivityPrefill($state, $set);
                        }),
                    TextInput::make('call_phone')
                        ->label(__('admin.call_quality.fields.call_phone'))
                        ->tel()
                        ->required()
                        ->maxLength(32)
                        ->extraInputAttributes(['dir' => 'ltr']),
                    TextEntry::make('evaluator_name')
                        ->label(__('admin.call_quality.fields.evaluator'))
                        ->state(fn (?CallEvaluation $record): ?string => $record?->evaluator?->name ?? auth('admin')->user()?->name),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make(__('admin.call_quality.sections.stages'))
                ->description(__('admin.call_quality.helpers.stages'))
                ->schema([
                    ...$criteria->map(fn (CallQualityCriterion $criterion): Grid => self::criterionRow($criterion))->all(),
                    View::make(self::SUMMARY_VIEW)
                        ->viewData(fn (Get $get): array => [
                            'summary' => CallScore::calculate((array) ($get('scores') ?? []), $criteria),
                            'criteriaCount' => $criteria->count(),
                        ]),
                ])
                ->columnSpanFull(),
            Section::make(__('admin.call_quality.sections.general_notes'))
                ->schema([
                    Textarea::make('general_notes')
                        ->label(__('admin.call_quality.fields.general_notes'))
                        ->rows(4)
                        ->maxLength(5000),
                ])
                ->columnSpanFull(),
            Section::make(__('admin.call_quality.sections.review'))
                ->schema([
                    Toggle::make('is_discussed')
                        ->label(__('admin.call_quality.fields.discussed'))
                        ->afterStateHydrated(fn (Toggle $component, ?CallEvaluation $record): Toggle => $component->state($record?->isDiscussed() ?? false))
                        ->helperText(fn (?CallEvaluation $record): ?string => self::stamp($record?->discussedBy, $record?->discussed_at))
                        ->visible(fn (?CallEvaluation $record): bool => $record !== null && self::service()->canDiscuss(self::user(), $record)),
                    Toggle::make('is_approved')
                        ->label(__('admin.call_quality.fields.approved'))
                        ->afterStateHydrated(fn (Toggle $component, ?CallEvaluation $record): Toggle => $component->state($record?->isApproved() ?? false))
                        ->helperText(fn (?CallEvaluation $record): ?string => self::stamp($record?->approvedBy, $record?->approved_at))
                        ->visible(fn (?CallEvaluation $record): bool => $record !== null && self::service()->canApprove(self::user())),
                ])
                ->columns(2)
                ->visibleOn('edit')
                ->columnSpanFull(),
        ]);
    }

    /**
     * @return Collection<int, CallQualityCriterion>
     */
    public static function criteria(): Collection
    {
        return CallQualityCriterion::query()->active()->ordered()->get();
    }

    public static function criterionLabel(CallQualityCriterion $criterion): string
    {
        $weight = rtrim(rtrim(number_format((float) $criterion->weight, 2, '.', ''), '0'), '.');

        return $criterion->name.' ('.$weight.'%)';
    }

    /**
     * @return array<int, string>
     */
    public static function scoreOptions(): array
    {
        return collect(range(1, CallScore::MAX_SCORE))
            ->mapWithKeys(fn (int $score): array => [$score => $score.' · '.__('admin.call_quality.score_labels.'.$score)])
            ->all();
    }

    public static function scoreColor(int|string|null $score): string
    {
        return match ((int) $score) {
            1 => 'danger',
            2 => 'warning',
            3 => 'gray',
            4 => 'primary',
            5 => 'success',
            default => 'gray',
        };
    }

    /**
     * Employees the current user may evaluate: those they can see, without their own
     * employee row unless they hold call_evaluations.view_all. The record's current
     * employee is always kept so an existing evaluation stays editable.
     *
     * @return array<int, string>
     */
    public static function employeeOptions(?int $keepEmployeeId = null, bool $includeOwn = false): array
    {
        $user = self::user();
        $visibleIds = self::service()->visibleEmployeeIds($user);
        $excludeOwn = ! $includeOwn && ! ($user?->can('call_evaluations.view_all') ?? false);

        return Employee::query()
            ->when($visibleIds !== null, fn ($query) => $query->whereIn('id', $visibleIds))
            ->when($excludeOwn && $user !== null, fn ($query) => $query->where(
                fn ($inner) => $inner->whereNull('user_id')->orWhere('user_id', '!=', $user->getKey())
            ))
            ->when($keepEmployeeId !== null, fn ($query) => $query->orWhere('id', $keepEmployeeId))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Unrated call activities of the lead (plus the one already linked to this evaluation).
     *
     * @return array<int, string>
     */
    public static function activityOptions(mixed $leadId, ?int $exceptEvaluationId = null): array
    {
        $lead = filled($leadId) ? ServiceRequest::query()->find($leadId) : null;

        if ($lead === null) {
            return [];
        }

        return self::service()->unratedCallActivities($lead, $exceptEvaluationId)
            ->mapWithKeys(fn (CrmActivity $activity): array => [$activity->getKey() => self::activityLabel($activity)])
            ->all();
    }

    public static function activityLabel(?CrmActivity $activity): ?string
    {
        if ($activity === null) {
            return null;
        }

        return collect([
            $activity->type ? __('admin.options.'.$activity->type->value) : null,
            ($activity->completed_at ?? $activity->created_at)?->format('Y-m-d H:i'),
            $activity->subject,
            $activity->employee?->name,
        ])->filter(fn (?string $part): bool => filled($part))->implode(' · ');
    }

    public static function leadLabel(?ServiceRequest $lead): ?string
    {
        if ($lead === null) {
            return null;
        }

        return '#'.$lead->getKey().' - '.collect([$lead->name, $lead->phone])->filter()->implode(' · ');
    }

    /**
     * @return array<int, string>
     */
    public static function searchLeads(string $search): array
    {
        $search = trim($search);
        $term = '%'.$search.'%';
        $id = ltrim($search, '#');

        return ServiceRequest::query()
            ->where(fn ($query) => $query
                ->where('name', 'like', $term)
                ->orWhere('phone', 'like', $term)
                ->when(ctype_digit($id), fn ($inner) => $inner->orWhere('id', (int) $id)))
            ->latest('id')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (ServiceRequest $lead): array => [$lead->getKey() => (string) self::leadLabel($lead)])
            ->all();
    }

    /**
     * Keep only active criteria scores (1–5) and non-empty notes; scores and notes of
     * criteria that are no longer active are carried over from the existing record.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeScores(array $data, ?CallEvaluation $existing = null): array
    {
        $keys = self::criteria()->pluck('key');
        $scores = (array) ($data['scores'] ?? []);
        $notes = (array) ($data['notes'] ?? []);

        $data['scores'] = collect($existing?->scores ?? [])
            ->except($keys->all())
            ->merge($keys->mapWithKeys(fn (string $key): array => [$key => (int) ($scores[$key] ?? 0)])
                ->filter(fn (int $score): bool => $score >= 1 && $score <= CallScore::MAX_SCORE))
            ->all();

        $data['notes'] = collect($existing?->notes ?? [])
            ->except($keys->all())
            ->merge($keys->mapWithKeys(fn (string $key): array => [$key => trim((string) ($notes[$key] ?? ''))])
                ->filter(fn (string $note): bool => $note !== ''))
            ->all();

        return $data;
    }

    public static function stamp(?User $by, ?Carbon $at): ?string
    {
        if ($at === null) {
            return null;
        }

        return __('admin.call_quality.helpers.stamp', [
            'name' => $by?->name ?? '—',
            'date' => $at->format('Y-m-d H:i'),
        ]);
    }

    private static function criterionRow(CallQualityCriterion $criterion): Grid
    {
        return Grid::make(['default' => 1, 'lg' => 12])
            ->schema([
                ToggleButtons::make('scores.'.$criterion->key)
                    ->label(self::criterionLabel($criterion))
                    ->options(self::scoreOptions())
                    ->colors(collect(range(1, CallScore::MAX_SCORE))->mapWithKeys(fn (int $score): array => [$score => self::scoreColor($score)])->all())
                    ->inline()
                    ->required()
                    ->live()
                    ->validationAttribute($criterion->name)
                    ->columnSpan(['default' => 1, 'lg' => 7]),
                TextInput::make('notes.'.$criterion->key)
                    ->label(__('admin.call_quality.fields.criterion_note'))
                    ->maxLength(1000)
                    ->columnSpan(['default' => 1, 'lg' => 5]),
            ]);
    }

    private static function applyLeadPrefill(mixed $leadId, Set $set): void
    {
        $set('crm_activity_id', null);

        $lead = filled($leadId) ? ServiceRequest::query()->find($leadId) : null;

        if ($lead === null) {
            return;
        }

        $prefill = self::service()->prefillFor($lead);

        $set('call_phone', $prefill['call_phone']);
        $set('crm_activity_id', $prefill['crm_activity_id']);
        $set('call_date', $prefill['call_date']);

        if ($prefill['employee_id'] !== null && array_key_exists($prefill['employee_id'], self::employeeOptions())) {
            $set('employee_id', $prefill['employee_id']);
        }
    }

    private static function applyActivityPrefill(mixed $activityId, Set $set): void
    {
        $activity = filled($activityId) ? CrmActivity::query()->find($activityId) : null;

        if ($activity === null) {
            return;
        }

        if ($activity->employee_id !== null) {
            $set('employee_id', $activity->employee_id);
        }

        $set('call_date', ($activity->completed_at ?? $activity->created_at ?? now())->toDateString());
    }

    private static function service(): CallQualityService
    {
        return app(CallQualityService::class);
    }

    private static function user(): ?User
    {
        $user = auth('admin')->user();

        return $user instanceof User ? $user : null;
    }
}
