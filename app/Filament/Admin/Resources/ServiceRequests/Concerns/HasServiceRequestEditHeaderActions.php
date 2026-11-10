<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Concerns;

use App\Enums\ServiceRequestActivityType;
use App\Services\ServiceRequestActivityService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;

trait HasServiceRequestEditHeaderActions
{
    use HasLeadConversionActions;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->leadConversionActions(),
            Action::make('add_note')
                ->label(__('admin.actions.add_note'))
                ->form([

                    Textarea::make('note')
                        ->label(__('admin.labels.note'))
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data, ServiceRequestActivityService $activities): void {
                    $record = $this->getRecord();
                    $activities->record(
                        $record,
                        ServiceRequestActivityType::NoteAdded,
                        null,
                        null,
                        $data['note'],
                        [],
                        auth()->id(),
                    );
                }),
            DeleteAction::make(),
        ];
    }
}
