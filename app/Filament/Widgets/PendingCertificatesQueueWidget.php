<?php

namespace App\Filament\Widgets;

use App\Enums\CertificateStatus;
use App\Filament\Admin\Resources\Certificates\CertificateResource;
use App\Models\Certificate;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingCertificatesQueueWidget extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.queues.pending_certificates.heading');
    }

    public static function canView(): bool
    {
        return auth('admin')->user()?->can('certificates.viewAny') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Certificate::query()
                    ->with(['user', 'course'])
                    ->where('status', CertificateStatus::Pending)
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('user.email')
                    ->label(__('admin.labels.trainee'))
                    ->searchable(),
                TextColumn::make('course.slug')
                    ->label(__('admin.labels.course'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('admin.dashboard_widgets.queues.columns.requested'))
                    ->since(),
            ])
            ->actions([
                Action::make('edit')
                    ->label(__('admin.actions.view'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (Certificate $record): string => CertificateResource::getUrl('edit', ['record' => $record])),
                Action::make('approve')
                    ->label(__('admin.actions.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (): bool => auth('admin')->user()?->can('certificates.update') ?? false)
                    ->requiresConfirmation()
                    ->action(function (Certificate $record): void {
                        $record->update([
                            'status' => CertificateStatus::Approved,
                            'approved_by' => Filament::auth()->id(),
                            'approved_at' => CarbonImmutable::now(),
                        ]);
                    }),
                Action::make('reject')
                    ->label(__('admin.actions.reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (): bool => auth('admin')->user()?->can('certificates.update') ?? false)
                    ->requiresConfirmation()
                    ->action(function (Certificate $record): void {
                        $record->update([
                            'status' => CertificateStatus::Rejected,
                            'approved_by' => Filament::auth()->id(),
                            'approved_at' => CarbonImmutable::now(),
                        ]);
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('admin.dashboard_widgets.queues.pending_certificates.empty'));
    }
}
