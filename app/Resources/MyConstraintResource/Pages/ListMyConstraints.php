<?php

namespace App\Resources\MyConstraintResource\Pages;

use App\Filament\Widgets\CalendarWidget;
use App\Models\Constraint;
use App\Resources\MyConstraintResource;
use Filament\Resources\Pages\ListRecords;

class ListMyConstraints extends ListRecords
{
    protected static string $resource = MyConstraintResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CalendarWidget::make([
                'model' => Constraint::class,
                'keys' => collect([
                    'id',
                    'constraint_name',
                    'start_date',
                    'end_date',
                    'constraint_color',
                ]),
                'type' => 'my',
            ]),
        ];
    }
}
