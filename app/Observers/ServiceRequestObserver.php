<?php

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Enums\ServiceRequestActivityType;
use App\Enums\WorkflowTrigger;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use App\Models\Team;
use App\Models\User;
use App\Services\AssignmentRuleService;
use App\Services\GoogleSheetsService;
use App\Services\NotificationService;
use App\Services\OrderAttributionService;
use App\Services\ServiceRequestActivityService;
use App\Services\WorkflowRuleService;
use App\Support\SheetRowContext;
use BackedEnum;
use Illuminate\Support\Facades\Log;

class ServiceRequestObserver
{
    public function created(ServiceRequest $request): void
    {
        if ($request->user_id === null) {
            $user = null;

            if (filled($request->email)) {
                $user = User::query()->where('email', $request->email)->first();
            }

            if (! $user && filled($request->phone)) {
                $user = User::query()->where('phone', $request->phone)->first();
            }

            if ($user) {
                $request->updateQuietly(['user_id' => $user->id]);
            }
        }

        app(ServiceRequestActivityService::class)->record(
            $request,
            ServiceRequestActivityType::Created,
            null,
            null,
            null,
            [],
            auth()->id(),
        );

        app(AssignmentRuleService::class)->apply($request);
        app(WorkflowRuleService::class)->run(WorkflowTrigger::LeadCreated, $request);
        app(NotificationService::class)->notifyServiceRequestCreated($request);

        $this->syncToSheet($request);
    }

    private function syncToSheet(ServiceRequest $request): void
    {
        try {
            $this->doSyncToSheet($request);
        } catch (\Throwable $e) {
            Log::channel('google_sheets')->error('SheetSync: unexpected exception in observer.', [
                'service_request_id' => $request->id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function doSyncToSheet(ServiceRequest $request): void
    {
        $logger = Log::channel('google_sheets');
        $context = [
            'service_request_id' => $request->id,
            'phone' => $request->phone,
            'source' => $request->source?->value,
        ];

        if ($request->status === ServiceRequestStatus::spamBucket()?->slug) {
            $logger->info('SheetSync: skipped — request flagged as spam.', $context);

            return;
        }

        $type = $request->type;

        if (! $type) {
            $logger->warning('SheetSync: skipped — no type assigned.', $context);

            return;
        }

        $context['type_slug'] = $type->slug;
        $context['sheet_id'] = $type->sheet_id;

        if (! $type->sync_to_sheets) {
            $logger->info('SheetSync: skipped — sync_to_sheets disabled for type.', $context);

            return;
        }

        if (blank($type->sheet_id)) {
            $logger->warning('SheetSync: skipped — sheet_id is empty for type.', $context);

            return;
        }

        $logger->info('SheetSync: starting sync.', $context);

        $sheetsService = app(GoogleSheetsService::class);
        $row = $sheetsService->buildRowForType($request, app(SheetRowContext::class)->landingSlug());

        if (empty($row)) {
            $logger->warning('SheetSync: skipped — no row builder matched type slug.', $context);

            return;
        }

        $context['row'] = $row;
        $logger->info('SheetSync: row built, appending to sheet.', $context);

        $synced = $type->slug === 'tlbat'
            ? $sheetsService->appendRowFromColumnA($type->sheet_id, $row)
            : $sheetsService->appendRow($type->sheet_id, $row);

        if ($synced) {
            $request->updateQuietly(['sheet_synced_at' => now()]);
            $logger->info('SheetSync: row appended successfully.', $context);
        } else {
            $logger->error('SheetSync: failed to append row — check google_sheets log for details.', $context);
        }
    }

    public function updating(ServiceRequest $request): void
    {
        if ($request->isDirty('converted_order_id') && $request->converted_at === null) {
            $request->converted_at = now();
        }
    }

    private function stringifyValue(mixed $value): string
    {
        return $value instanceof BackedEnum ? (string) $value->value : (string) ($value ?? '');
    }

    public function updated(ServiceRequest $request): void
    {
        $original = $request->getOriginal();
        $activity = app(ServiceRequestActivityService::class);
        $actorId = auth()->id();

        if ($request->wasChanged('status')) {
            $activity->record(
                $request,
                ServiceRequestActivityType::StatusChanged,
                $this->stringifyValue($original['status'] ?? null),
                $this->stringifyValue($request->status),
                null,
                [],
                $actorId,
            );

            app(WorkflowRuleService::class)->run(
                WorkflowTrigger::LeadStatusChanged,
                $request,
                ['new_status' => $request->status],
            );
        }

        if ($request->wasChanged('assigned_to')) {
            $activity->record(
                $request,
                ServiceRequestActivityType::AssignedChanged,
                (string) ($original['assigned_to'] ?? ''),
                (string) ($request->assigned_to ?? ''),
                null,
                ['field' => 'assigned_to'],
                $actorId,
            );

            app(WorkflowRuleService::class)->run(WorkflowTrigger::LeadAssigned, $request);
        }

        if ($request->wasChanged('team_id')) {
            $activity->record(
                $request,
                ServiceRequestActivityType::TeamTransferred,
                Team::query()->find($original['team_id'] ?? null)?->name ?? '',
                $request->team?->name ?? '',
                null,
                [],
                $actorId,
            );
        }

        if ($request->wasChanged('converted_order_id')) {
            $activity->record(
                $request,
                ServiceRequestActivityType::Converted,
                (string) ($original['converted_order_id'] ?? ''),
                (string) ($request->converted_order_id ?? ''),
                null,
                [],
                $actorId,
            );

            $order = $request->convertedOrder;
            if ($order && $order->status === OrderStatus::Paid) {
                app(OrderAttributionService::class)->createForOrderIfEligible($order);
            }
        }
    }
}
