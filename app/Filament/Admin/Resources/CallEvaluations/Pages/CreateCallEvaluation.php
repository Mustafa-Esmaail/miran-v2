<?php

namespace App\Filament\Admin\Resources\CallEvaluations\Pages;

use App\Filament\Admin\Resources\CallEvaluations\CallEvaluationResource;
use App\Filament\Admin\Resources\CallEvaluations\Schemas\CallEvaluationForm;
use App\Models\CrmActivity;
use App\Models\ServiceRequest;
use App\Services\CallQualityService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateCallEvaluation extends CreateRecord
{
    protected static string $resource = CallEvaluationResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->prefillFromQuery();
    }

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = CallEvaluationForm::normalizeScores($data);
        $data['evaluator_id'] = auth('admin')->id();

        return $data;
    }

    /**
     * ?service_request=<id>&crm_activity=<id>, as linked from the audit queue,
     * the lead page and the activity timeline.
     */
    private function prefillFromQuery(): void
    {
        $leadId = (int) request()->query('service_request', 0);
        $lead = $leadId > 0 ? ServiceRequest::query()->find($leadId) : null;

        if ($lead === null) {
            return;
        }

        $activityId = (int) request()->query('crm_activity', 0);
        $activity = $activityId > 0
            ? CrmActivity::query()
                ->where('entity_type', ServiceRequest::class)
                ->where('entity_id', $lead->getKey())
                ->find($activityId)
            : null;

        $service = app(CallQualityService::class);
        $prefill = $service->prefillFor($lead, $activity);

        abort_unless(
            $prefill['employee_id'] === null || $service->canSeeEmployee(auth('admin')->user(), $prefill['employee_id']),
            403,
        );

        if ($prefill['employee_id'] !== null && ! array_key_exists($prefill['employee_id'], CallEvaluationForm::employeeOptions())) {
            $prefill['employee_id'] = null;
        }

        if ($prefill['crm_activity_id'] !== null && ! array_key_exists($prefill['crm_activity_id'], CallEvaluationForm::activityOptions($lead->getKey()))) {
            $prefill['crm_activity_id'] = null;
        }

        $this->form->fill([
            ...$this->form->getRawState(),
            ...array_filter($prefill, fn (mixed $value): bool => $value !== null),
        ]);
    }
}
