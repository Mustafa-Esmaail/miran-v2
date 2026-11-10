<?php

namespace App\Filament\Admin\Resources\WorkflowRules\Pages;

use App\Filament\Admin\Resources\WorkflowRules\WorkflowRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Leek\FilamentHeaderFilters\Concerns\HasHeaderFilters;

class ListWorkflowRules extends ListRecords
{
    use HasHeaderFilters;

    protected static string $resource = WorkflowRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('admin.actions.create')),
        ];
    }
}
