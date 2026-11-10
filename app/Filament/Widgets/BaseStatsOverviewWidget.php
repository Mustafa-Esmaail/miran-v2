<?php

namespace App\Filament\Widgets;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Widgets\StatsOverviewWidget;

abstract class BaseStatsOverviewWidget extends StatsOverviewWidget
{
    public function getSectionContentComponent(): Component
    {
        return Section::make()
            ->heading($this->getHeading())
            ->description($this->getDescription())
            ->schema($this->getCachedStats())
            ->columns($this->getColumns())
            ->contained($this->isContained())
            ->gridContainer();
    }

    protected function isContained(): bool
    {
        if (! property_exists($this, 'contained')) {
            return true;
        }

        return (bool) $this->contained;
    }
}
