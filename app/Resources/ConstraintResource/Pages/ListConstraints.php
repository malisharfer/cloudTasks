<?php

namespace App\Resources\ConstraintResource\Pages;

use App\Filament\Widgets\CalendarWidget;
use App\Models\Constraint;
use App\Resources\ConstraintResource;
use Filament\Resources\Pages\ListRecords;

class ListConstraints extends ListRecords
{
    protected static string $resource = ConstraintResource::class;

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
                'type' => 'my_soldiers',
            ]),
        ];
    }
}
