<?php

namespace App\Filament\Admin\Resources\CallEvaluations\Pages;

use App\Filament\Admin\Resources\CallEvaluations\Actions\CallEvaluationReviewActions;
use App\Filament\Admin\Resources\CallEvaluations\CallEvaluationResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewCallEvaluation extends ViewRecord
{
    protected static string $resource = CallEvaluationResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.view');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.pages.view_title', ['label' => $this->getRecordTitle()]);
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            ...CallEvaluationReviewActions::make(),
            EditAction::make()->label(__('admin.actions.edit')),
        ];
    }
}
