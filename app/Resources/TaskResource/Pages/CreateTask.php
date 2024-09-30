<?php

namespace App\Resources\TaskResource\Pages;

use App\Models\Task;
use App\Resources\TaskResource;
use App\Services\ReccurenceEvents;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;

class CreateTask extends CreateRecord
{
    use HasWizard;

    protected static string $resource = TaskResource::class;

    protected function afterCreate(): void
    {
        $task = Task::latest()->first();
        $reccurenceEvents = new ReccurenceEvents;
        $reccurenceEvents->oneTimeTask($task);
    }

    public static function getSteps(): array
    {
        return [
            Step::make('Details')
                ->label(__('Details'))
                ->schema([
                    Section::make()->schema(TaskResource::getTaskDetails())->columns(),
                ]),
            Step::make('Recurrence')
                ->label(__('Recurrence'))
                ->schema([
                    Section::make()->schema(TaskResource::getRecurrence())->columns(),
                ]),
        ];
    }
}
