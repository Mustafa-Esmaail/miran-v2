<?php

namespace App\Filament\Admin\Resources\CallEvaluations\Pages;

use App\Filament\Admin\Resources\CallEvaluations\CallEvaluationResource;
use App\Filament\Admin\Resources\CallEvaluations\Schemas\CallEvaluationForm;
use App\Models\CallEvaluation;
use App\Models\User;
use App\Services\CallQualityService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;

class EditCallEvaluation extends EditRecord
{
    protected static string $resource = CallEvaluationResource::class;

    /**
     * Review toggles submitted with the form; null when the toggle was not shown.
     *
     * @var array{discussed: ?bool, approved: ?bool}
     */
    protected array $reviewState = ['discussed' => null, 'approved' => null];

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label(__('admin.pages.view')),
            DeleteAction::make()->label(__('admin.actions.delete')),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $discussed = Arr::pull($data, 'is_discussed');
        $approved = Arr::pull($data, 'is_approved');

        $this->reviewState = [
            'discussed' => $discussed === null ? null : (bool) $discussed,
            'approved' => $approved === null ? null : (bool) $approved,
        ];

        unset($data['evaluator_id']);

        /** @var CallEvaluation $record */
        $record = $this->getRecord();

        return CallEvaluationForm::normalizeScores($data, $record);
    }

    protected function afterSave(): void
    {
        $user = auth('admin')->user();

        if (! $user instanceof User) {
            return;
        }

        /** @var CallEvaluation $record */
        $record = $this->getRecord();
        $service = app(CallQualityService::class);

        if ($this->reviewState['discussed'] !== null
            && $this->reviewState['discussed'] !== $record->isDiscussed()
            && $service->canDiscuss($user, $record)) {
            $service->setDiscussed($record, $this->reviewState['discussed'], $user);
        }

        if ($this->reviewState['approved'] !== null
            && $this->reviewState['approved'] !== $record->isApproved()
            && $service->canApprove($user)) {
            $service->setApproved($record, $this->reviewState['approved'], $user);
        }
    }
}
