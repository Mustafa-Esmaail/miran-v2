<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Concerns;

use App\Filament\Admin\Resources\CallEvaluations\CallEvaluationResource;
use App\Models\ServiceRequest;
use App\Services\CallQualityService;
use Filament\Actions\Action;

/**
 * "Evaluate call" on a lead: opens the call-evaluation form pre-filled for this lead.
 */
trait HasCallEvaluationActions
{
    /**
     * @return array<int, Action>
     */
    protected function callEvaluationActions(): array
    {
        return [
            Action::make('evaluate_call')
                ->label(__('admin.call_quality.lead.evaluate_call'))
                ->icon('heroicon-o-star')
                ->color('gray')
                ->visible(fn (): bool => $this->canEvaluateLeadCall())
                ->url(fn (): string => CallEvaluationResource::getUrl('create', ['service_request' => $this->getRecord()->getKey()])),
        ];
    }

    private function canEvaluateLeadCall(): bool
    {
        /** @var ServiceRequest $lead */
        $lead = $this->getRecord();

        return filled($lead->phone) && app(CallQualityService::class)->canEvaluate(auth('admin')->user());
    }
}
