<?php

namespace App\Filament\Admin\Resources\AssignmentRules\Pages;

use App\Filament\Admin\Resources\AssignmentRules\AssignmentRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Leek\FilamentHeaderFilters\Concerns\HasHeaderFilters;

class ListAssignmentRules extends ListRecords
{
    use HasHeaderFilters;

    protected static string $resource = AssignmentRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('admin.actions.create')),
        ];
    }
}
