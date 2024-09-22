<?php

namespace App\Resources\MyShiftResource\Pages;

use App\Filament\Widgets\CalendarWidget;
use App\Models\Shift;
use App\Resources\MyShiftResource;
use Filament\Resources\Pages\ListRecords;

class ListMyShift extends ListRecords
{
    protected static string $resource = MyShiftResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CalendarWidget::make([
                'model' => Shift::class,
                'keys' => collect([
                    'id',
                    'task_name',
                    'start_date',
                    'end_date',
                    'task_color',
                ]),
                'type' => 'my',
            ]),
        ];
    }
}
