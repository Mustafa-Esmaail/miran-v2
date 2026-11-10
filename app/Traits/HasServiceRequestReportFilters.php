<?php

namespace App\Traits;

use App\Models\Bundle;
use App\Models\Course;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared filter bar (employee, date range, course, bundle, status) and
 * per-status column set for the CRM service-request report pages.
 */
trait HasServiceRequestReportFilters
{
    use CrmRoleScope;

    /**
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    protected function serviceRequestReportFilterComponents(): array
    {
        return [
            Select::make('employee_id')
                ->label(__('admin.labels.employee'))
                ->options(fn (): array => $this->visibleEmployeesQuery()
                    ->get()
                    ->pluck('name', 'user_id')
                    ->all())
                ->searchable()
                ->placeholder(__('admin.labels.all')),
            DatePicker::make('date_from')->label(__('admin.labels.from')),
            DatePicker::make('date_to')->label(__('admin.labels.until')),
            Select::make('course_id')
                ->label(__('admin.labels.course'))
                ->options(fn (): array => Course::query()
                    ->get()
                    ->mapWithKeys(fn (Course $course): array => [$course->id => $course->getTranslation('title', app()->getLocale())])
                    ->all())
                ->searchable()
                ->placeholder(__('admin.labels.all')),
            Select::make('bundle_id')
                ->label(__('admin.labels.bundle'))
                ->options(fn (): array => Bundle::query()
                    ->get()
                    ->mapWithKeys(fn (Bundle $bundle): array => [$bundle->id => $bundle->getTranslation('title', app()->getLocale())])
                    ->all())
                ->searchable()
                ->placeholder(__('admin.labels.all')),
            Select::make('status')
                ->label(__('admin.labels.status'))
                ->options(fn (): array => ServiceRequestStatus::query()
                    ->active()
                    ->orderBy('sort_order')
                    ->get()
                    ->mapWithKeys(fn (ServiceRequestStatus $status): array => [$status->slug => $status->getTranslatedName()])
                    ->all())
                ->searchable()
                ->placeholder(__('admin.labels.all')),
        ];
    }

    /**
     * Employees visible to the current CRM user (self / team / all), matching
     * the same role scoping already applied to ServiceRequest rows.
     */
    protected function visibleEmployeesQuery(): Builder
    {
        return $this->scopeEmployees(Employee::query()->whereNotNull('user_id'), $this->currentEmployee());
    }

    /**
     * Base ServiceRequest query with role-based row security plus every
     * selected report filter applied.
     *
     * @param  array<string, mixed>|null  $filters  defaults to the page's own `$filters`; widgets pass their `$pageFilters`
     */
    protected function filteredServiceRequestQuery(?array $filters = null): Builder
    {
        $filters ??= $this->filters ?? [];

        return $this->scopeLeads(ServiceRequest::query(), $this->currentEmployee())
            ->when($filters['employee_id'] ?? null, fn (Builder $q, string $userId): Builder => $q->where('assigned_to', $userId))
            ->when($filters['date_from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date))
            ->when($filters['course_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('course_id', $id))
            ->when($filters['bundle_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('bundle_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $q, string $status): Builder => $q->where('status', $status));
    }

    /**
     * One numeric column per ServiceRequestStatus value, plus a trailing Total.
     *
     * @return array<int, TextColumn>
     */
    protected function statusCountColumns(): array
    {
        $columns = ServiceRequestStatus::query()
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (ServiceRequestStatus $status): TextColumn => TextColumn::make($status->slug)
                ->label($status->getTranslatedName())
                ->numeric()
                ->alignEnd()
                ->toggleable())
            ->all();

        $columns[] = TextColumn::make('total')
            ->label(__('admin.labels.total'))
            ->numeric()
            ->alignEnd()
            ->weight('bold');

        return $columns;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, int>  $countsByStatus  status value => count, e.g. from ->pluck('aggregate', 'status')
     * @return array<string, int>
     */
    protected function pivotStatusCounts(\Illuminate\Support\Collection $countsByStatus): array
    {
        $row = [];
        $total = 0;

        foreach (ServiceRequestStatus::query()->active()->orderBy('sort_order')->get() as $status) {
            $count = (int) ($countsByStatus->get($status->slug) ?? 0);
            $row[$status->slug] = $count;
            $total += $count;
        }

        $row['total'] = $total;

        return $row;
    }
}
