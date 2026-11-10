<?php

namespace App\Filament\Widgets;

use App\Enums\CommissionLedgerStatus;
use App\Enums\EmployeeRole;
use App\Models\CommissionLedger;
use App\Models\Deal;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class CrmTeamLeaderboardWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.crm_leaderboard_heading', ['month' => now()->format('M Y')]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->buildQuery())
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.labels.employee'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->label(__('admin.labels.role'))
                    ->formatStateUsing(fn ($state): string => $state ? __('admin.options.'.$state->value) : '—')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        EmployeeRole::GeneralManager => 'danger',
                        EmployeeRole::DepartmentManager => 'primary',
                        EmployeeRole::TeamLead => 'warning',
                        EmployeeRole::SalesPerson => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('leads_assigned')
                    ->label(__('admin.dashboard_widgets.stats.leaderboard_leads'))
                    ->getStateUsing(fn (Employee $record): int => $record->user_id
                        ? ServiceRequest::query()->where('assigned_to', $record->user_id)->count()
                        : 0
                    )
                    ->sortable(false),

                TextColumn::make('leads_converted')
                    ->label(__('admin.dashboard_widgets.stats.leaderboard_converted'))
                    ->getStateUsing(fn (Employee $record): int => $record->user_id
                        ? ServiceRequest::query()
                            ->where('assigned_to', $record->user_id)
                            ->whereNotNull('converted_at')
                            ->count()
                        : 0
                    )
                    ->sortable(false),

                TextColumn::make('win_rate')
                    ->label(__('admin.dashboard_widgets.stats.leaderboard_win_rate'))
                    ->getStateUsing(function (Employee $record): string {
                        if (! $record->user_id) {
                            return '0%';
                        }

                        $converted = ServiceRequest::query()
                            ->where('assigned_to', $record->user_id)
                            ->whereNotNull('converted_at')
                            ->count();

                        $closed = ServiceRequest::query()
                            ->where('assigned_to', $record->user_id)
                            ->whereIn('status', ServiceRequestStatus::query()->whereIn('outcome', ['won', 'lost'])->pluck('slug'))
                            ->count();

                        $total = $converted + $closed;

                        return $total > 0 ? round($converted / $total * 100).'%' : '0%';
                    })
                    ->sortable(false),

                TextColumn::make('deals_value')
                    ->label(__('admin.dashboard_widgets.stats.leaderboard_deals_won'))
                    ->getStateUsing(fn (Employee $record): string => 'SAR '.number_format(
                        Deal::query()
                            ->where('employee_id', $record->id)
                            ->where('stage', \App\Enums\DealStage::ClosedWon->value)
                            ->where('updated_at', '>=', now()->startOfMonth())
                            ->sum('value') / 100,
                        0
                    ))
                    ->sortable(false),

                TextColumn::make('commissions')
                    ->label(__('admin.dashboard_widgets.stats.leaderboard_commissions'))
                    ->getStateUsing(fn (Employee $record): string => 'SAR '.number_format(
                        CommissionLedger::query()
                            ->where('employee_id', $record->id)
                            ->whereIn('status', [CommissionLedgerStatus::Approved->value, CommissionLedgerStatus::Paid->value])
                            ->where('created_at', '>=', now()->startOfMonth())
                            ->sum('commission_amount') / 100,
                        0
                    ))
                    ->sortable(false),
            ])
            ->paginated(false)
            ->defaultSort('name');
    }

    private function buildQuery(): Builder
    {
        $all = Employee::query()->whereNotNull('role');

        $currentEmployee = Employee::query()->where('user_id', auth()->id())->first();

        // No employee record (super-admin) or GeneralManager → unrestricted view of all reps
        if (! $currentEmployee || $currentEmployee->role === EmployeeRole::GeneralManager) {
            return $all;
        }

        return match ($currentEmployee->role) {
            EmployeeRole::TeamLead => $all->where('team_id', $currentEmployee->team_id),
            EmployeeRole::DepartmentManager => $all->where('department_id', $currentEmployee->department_id),
            EmployeeRole::SalesPerson => $all->where('id', $currentEmployee->id),
            default => $all,
        };
    }
}
