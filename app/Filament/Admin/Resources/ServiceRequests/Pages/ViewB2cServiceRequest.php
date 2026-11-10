<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Pages;

use App\Filament\Admin\Resources\ServiceRequests\B2cServiceRequestResource;
use App\Filament\Admin\Resources\ServiceRequests\Concerns\HasCallEvaluationActions;
use App\Filament\Admin\Resources\ServiceRequests\Concerns\HasLeadConversionActions;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewB2cServiceRequest extends ViewRecord
{
    use HasCallEvaluationActions;
    use HasLeadConversionActions;

    protected static string $resource = B2cServiceRequestResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.view');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.view_title', ['label' => $this->getRecordTitle()]);
    }

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->loadMissing([
            'statusModel.stage',
            'team',
            'type',
            'convertedOrder',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ...$this->leadConversionActions(),
            ...$this->callEvaluationActions(),
            EditAction::make()->label(__('admin.actions.edit')),
        ];
    }
}
