<?php

namespace App\Resources\ChartResource\Pages;

use App\Filament\Widgets\ChartWidget;
use App\Resources\ChartResource;
use Filament\Resources\Pages\ListRecords;

class ListCharts extends ListRecords
{
    protected static string $resource = ChartResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ChartWidget::make(),
        ];
    }
}
