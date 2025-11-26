<?php

namespace App\Resources\TaskResource\Pages;

use App\Resources\TaskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('New task')),
        ];
    }
}
