<?php

namespace App\Filament\Admin\Resources\Deals\Pages;

use App\Enums\DealStage;
use App\Filament\Admin\Resources\Deals\DealResource;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Employee;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;

class ListDeals extends KanbanBoard
{
    protected static string $resource = DealResource::class;

    protected static string $model = Deal::class;

    protected static string $statusEnum = DealStage::class;

    protected static string $recordStatusAttribute = 'stage';

    protected static string $recordView = 'filament.resources.deals.kanban.record';

    protected static string $statusView = 'filament.resources.deals.kanban.status';

    protected string $view = 'filament.resources.deals.kanban.board';

    public bool $disableEditModal = true;

    public string $search = '';

    public string $employeeId = '';

    public string $companyId = '';

    public string $pipeline = 'all';

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }

    protected function statuses(): Collection
    {
        return collect(DealStage::cases())
            ->map(fn (DealStage $stage): array => [
                'id' => $stage->value,
                'title' => __('admin.options.'.$stage->value),
                'limit' => $this->getColumnLimit($stage),
            ]);
    }

    protected function getEloquentQuery(): Builder
    {
        $query = DealResource::getEloquentQuery();

        if ($this->search !== '') {
            $search = '%'.$this->search.'%';
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery
                    ->where('title', 'like', $search)
                    ->orWhereHas('company', fn (Builder $companyQuery) => $companyQuery->where('name', 'like', $search));
            });
        }

        if ($this->employeeId !== '') {
            $query->where('employee_id', (int) $this->employeeId);
        }

        if ($this->companyId !== '') {
            $query->where('company_id', (int) $this->companyId);
        }

        if ($this->pipeline === 'open') {
            $query->whereNotIn('stage', [DealStage::ClosedWon->value, DealStage::ClosedLost->value]);
        }

        if ($this->pipeline === 'closed') {
            $query->whereIn('stage', [DealStage::ClosedWon->value, DealStage::ClosedLost->value]);
        }

        return $query;
    }

    public function onStatusChanged(int|string $recordId, string $status, array $fromOrderedIds, array $toOrderedIds): void
    {
        $limit = $this->getColumnLimit(DealStage::from($status));

        if ($limit !== null && count($toOrderedIds) > $limit) {
            Notification::make()
                ->title(__('admin.notifications.limit_reached'))
                ->body(__('admin.notifications.kanban_column_limit_reached', ['limit' => $limit]))
                ->warning()
                ->send();

            return;
        }

        parent::onStatusChanged($recordId, $status, $fromOrderedIds, $toOrderedIds);
    }

    private function getColumnLimit(DealStage $stage): ?int
    {
        return match ($stage) {
            DealStage::Prospecting => 25,
            DealStage::Qualification => 20,
            DealStage::Proposal => 20,
            DealStage::Negotiation => 15,
            DealStage::ClosedWon, DealStage::ClosedLost => null,
        };
    }

    /**
     * Sum of the stored (×100) values of the deals shown in a column.
     *
     * @param  array<int, Deal>  $records
     */
    public function columnValueTotal(array $records): int
    {
        return (int) collect($records)->sum(fn (Deal $deal): int => (int) ($deal->value ?? 0));
    }

    public function formatDealValue(int $storedValue): string
    {
        return 'SAR '.number_format($storedValue / 100, 0);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            ...parent::getViewData(),
            'employees' => Employee::query()->orderBy('name')->pluck('name', 'id')->all(),
            'companies' => Company::query()->orderBy('name')->pluck('name', 'id')->all(),
            'pipelineOptions' => [
                'all' => __('admin.labels.all_courses'),
                'open' => __('admin.options.open'),
                'closed' => __('admin.options.closed'),
            ],
        ];
    }
}
