<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Schemas;

use App\Enums\ServiceRequestPriority;
use App\Enums\ServiceRequestStatusOutcome;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\ServiceRequest;
use App\Services\ServiceRequestService;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;

class ServiceRequestInfolist
{
    /**
     * @param  bool  $showCompanyFields  When true, shows the company name/city entries (B2B requests only).
     */
    public static function configure(Schema $schema, bool $showCompanyFields = false): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.lead_view.summary'))
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'sm' => 2, 'xl' => 4])
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('admin.labels.name'))
                            ->weight('semibold')
                            ->placeholder('—'),
                        TextEntry::make('phone')
                            ->label(__('admin.labels.phone'))
                            ->icon(Heroicon::OutlinedPhone)
                            ->color('primary')
                            ->url(fn (ServiceRequest $record): ?string => filled($record->phone)
                                ? 'tel:'.ServiceRequestService::normalizePhone((string) $record->phone)
                                : null)
                            ->extraAttributes(['dir' => 'ltr'])
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('email')
                            ->label(__('admin.labels.email'))
                            ->icon(Heroicon::OutlinedEnvelope)
                            ->url(fn (ServiceRequest $record): ?string => filled($record->email) ? 'mailto:'.$record->email : null)
                            ->placeholder('—'),
                        TextEntry::make('source')
                            ->label(__('admin.labels.source'))
                            ->formatStateUsing(fn ($state): string => self::optionLabel($state))
                            ->badge()
                            ->color('gray')
                            ->placeholder('—'),
                        TextEntry::make('company_name')
                            ->label(__('admin.labels.company_name'))
                            ->visible($showCompanyFields)
                            ->placeholder('—'),
                        TextEntry::make('city')
                            ->label(__('admin.labels.city'))
                            ->visible($showCompanyFields)
                            ->placeholder('—'),
                        TextEntry::make('type_name')
                            ->label(__('admin.labels.type'))
                            ->state(fn (ServiceRequest $record): ?string => $record->type?->getTranslatedName())
                            ->placeholder('—'),
                        TextEntry::make('status')
                            ->label(__('admin.labels.status'))
                            ->formatStateUsing(fn ($state, ServiceRequest $record): string => self::statusLabel($record))
                            ->badge()
                            ->color(fn (ServiceRequest $record): string|array => filled($record->statusModel?->color)
                                ? Color::hex((string) $record->statusModel->color)
                                : 'gray')
                            ->placeholder('—'),
                        TextEntry::make('assignedTo.name')
                            ->label(__('admin.labels.assigned_to'))
                            ->icon(Heroicon::OutlinedUser)
                            ->placeholder(__('admin.lead_view.unassigned')),
                        TextEntry::make('team.name')
                            ->label(__('admin.labels.team'))
                            ->icon(Heroicon::OutlinedUserGroup)
                            ->placeholder('—'),
                        TextEntry::make('priority')
                            ->label(__('admin.labels.priority'))
                            ->formatStateUsing(fn ($state): string => self::optionLabel($state))
                            ->badge()
                            ->color(fn ($state): string => match ($state instanceof BackedEnum ? $state->value : (string) $state) {
                                ServiceRequestPriority::High->value => 'danger',
                                ServiceRequestPriority::Normal->value => 'info',
                                default => 'gray',
                            })
                            ->placeholder('—'),
                        TextEntry::make('status_date')
                            ->label(__('admin.labels.follow_up'))
                            ->dateTime('Y-m-d H:i')
                            ->icon(fn (ServiceRequest $record): Heroicon => self::isFollowUpOverdue($record)
                                ? Heroicon::OutlinedExclamationTriangle
                                : Heroicon::OutlinedCalendarDays)
                            ->color(fn (ServiceRequest $record): ?string => self::isFollowUpOverdue($record) ? 'danger' : null)
                            ->hint(fn (ServiceRequest $record): ?string => self::isFollowUpOverdue($record) ? __('admin.lead_view.overdue') : null)
                            ->hintColor('danger')
                            ->placeholder('—'),
                        TextEntry::make('course_name')
                            ->label(__('admin.labels.course'))
                            ->state(fn (ServiceRequest $record): ?string => $record->course?->getTranslation('title', app()->getLocale())
                                ?? $record->bundle?->getTranslation('title', app()->getLocale()))
                            ->placeholder('—'),
                        TextEntry::make('course_group_name')
                            ->label(__('admin.models.course_group'))
                            ->state(fn (ServiceRequest $record): ?string => $record->courseGroup?->getTranslation('title', app()->getLocale()))
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label(__('admin.labels.created_at'))
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('—'),
                        TextEntry::make('converted_order')
                            ->label(__('admin.labels.converted_order'))
                            ->state(fn (ServiceRequest $record): ?string => $record->convertedOrder?->order_no
                                ?? ($record->converted_order_id !== null ? '#'.$record->converted_order_id : null))
                            ->url(fn (ServiceRequest $record): ?string => $record->converted_order_id !== null
                                ? OrderResource::getUrl('view', ['record' => $record->converted_order_id])
                                : null)
                            ->icon(Heroicon::OutlinedShoppingBag)
                            ->color('success')
                            ->visible(fn (ServiceRequest $record): bool => $record->converted_order_id !== null),
                        TextEntry::make('converted_at')
                            ->label(__('admin.labels.converted_at'))
                            ->dateTime('Y-m-d H:i')
                            ->visible(fn (ServiceRequest $record): bool => $record->converted_order_id !== null)
                            ->placeholder('—'),
                        TextEntry::make('message')
                            ->label(__('admin.labels.message'))
                            ->columnSpanFull()
                            ->prose()
                            ->placeholder('—'),
                    ]),
                Section::make(__('admin.lead_view.timeline'))
                    ->description(__('admin.lead_view.timeline_description'))
                    ->icon(Heroicon::OutlinedClock)
                    ->columnSpanFull()
                    ->schema([
                        ViewEntry::make('timeline')
                            ->label(__('admin.lead_view.timeline'))
                            ->hiddenLabel()
                            ->view('filament.resources.service-requests.timeline.timeline'),
                    ]),
            ]);
    }

    /**
     * Mirrors ServiceRequest::scopeFollowUpOverdue(): a past follow-up date on a lead whose
     * status is still open (outcome open, no outcome, or no status row).
     */
    public static function isFollowUpOverdue(ServiceRequest $record): bool
    {
        if ($record->status_date === null || ! $record->status_date->lt(today()->startOfDay())) {
            return false;
        }

        $outcome = $record->statusModel?->outcome;

        return $outcome === null || $outcome === ServiceRequestStatusOutcome::Open;
    }

    private static function statusLabel(ServiceRequest $record): string
    {
        $status = $record->statusModel;

        if ($status === null) {
            return (string) $record->status;
        }

        $stageName = $status->stage?->getTranslatedName();

        return $stageName
            ? "{$stageName} ({$status->getTranslatedName()})"
            : $status->getTranslatedName();
    }

    private static function optionLabel(mixed $state): string
    {
        $value = $state instanceof BackedEnum ? (string) $state->value : (string) $state;
        $key = 'admin.options.'.$value;

        return __($key) !== $key ? __($key) : $value;
    }
}
