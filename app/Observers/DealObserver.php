<?php

namespace App\Observers;

use App\Enums\WorkflowTrigger;
use App\Models\Deal;
use App\Services\WorkflowRuleService;

class DealObserver
{
    public function created(Deal $deal): void
    {

        app(WorkflowRuleService::class)->run(WorkflowTrigger::DealCreated, $deal);
    }
    

    public function updated(Deal $deal): void
    {
        if ($deal->wasChanged('stage')) {
            app(WorkflowRuleService::class)->run(
                WorkflowTrigger::DealStageChanged,
                $deal,
                ['new_stage' => $deal->stage?->value],
            );
        }
    }
}
