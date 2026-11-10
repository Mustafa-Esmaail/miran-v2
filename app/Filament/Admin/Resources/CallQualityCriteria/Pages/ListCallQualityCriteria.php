<?php

namespace App\Filament\Admin\Resources\CallQualityCriteria\Pages;

use App\Filament\Admin\Resources\CallQualityCriteria\CallQualityCriterionResource;
use App\Models\CallEvaluation;
use App\Support\CallQuality\CallQualityLevels;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCallQualityCriteria extends ListRecords
{
    protected static string $resource = CallQualityCriterionResource::class;

    /**
     * Levels whose minimum % is editable, highest first.
     *
     * @var array<int, string>
     */
    public const EDITABLE_LEVELS = ['excellent', 'very_good', 'good', 'acceptable'];

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->levelThresholdsAction(),
            CreateAction::make()->label(__('admin.actions.create')),
        ];
    }

    protected function levelThresholdsAction(): Action
    {
        return Action::make('levelThresholds')
            ->label(__('admin.call_quality.thresholds.action'))
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->color('gray')
            ->modalHeading(__('admin.call_quality.thresholds.heading'))
            ->modalDescription(__('admin.call_quality.thresholds.description'))
            ->modalSubmitActionLabel(__('admin.call_quality.thresholds.save'))
            ->modalWidth('lg')
            ->fillForm(fn (): array => array_intersect_key(CallQualityLevels::thresholds(), array_flip(self::EDITABLE_LEVELS)))
            ->schema([
                $this->thresholdInput('excellent')->maxValue(100),
                $this->thresholdInput('very_good')->lt('excellent'),
                $this->thresholdInput('good')->lt('very_good'),
                $this->thresholdInput('acceptable')->lt('good'),
            ])
            ->action(function (array $data): void {
                CallQualityLevels::save(array_intersect_key($data, array_flip(self::EDITABLE_LEVELS)));
                CallEvaluation::recalculateAll();

                Notification::make()
                    ->success()
                    ->title(__('admin.call_quality.thresholds.saved'))
                    ->send();
            });
    }

    protected function thresholdInput(string $level): TextInput
    {
        return TextInput::make($level)
            ->label(CallQualityLevels::label($level))
            ->numeric()
            ->integer()
            ->minValue(0)
            ->maxValue(100)
            ->suffix('%')
            ->required()
            ->inlineLabel();
    }
}
