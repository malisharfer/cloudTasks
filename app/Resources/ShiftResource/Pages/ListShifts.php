<?php

namespace App\Resources\ShiftResource\Pages;

use App\Filament\Widgets\CalendarWidget;
use App\Models\Shift;
use App\Resources\ShiftResource;
use Filament\Resources\Pages\ListRecords;

class ListShifts extends ListRecords
{
    protected static string $resource = ShiftResource::class;

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
                'type' => 'my_soldiers',
            ]),
        ];
    }
}
