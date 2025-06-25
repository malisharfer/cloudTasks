<?php

namespace App\Resources\TaskResource\Pages;

use App\Models\Shift;
use App\Models\Task;
use App\Resources\TaskResource;
use App\Services\RecurringEvents;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Get;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;

class CreateTask extends CreateRecord
{
    use HasWizard;

    protected static string $resource = TaskResource::class;

    protected function getRedirectUrl(): string
    {
        $resource = static::getResource();

        return $resource::getUrl('index');
    }

    protected function afterCreate(): void
    {
        self::recurring();
        if ((empty($this->data['soldier_type'])) || (empty($this->data['soldier_id']) && $this->data['soldier_type'] != 'me')) {
            return;
        }
        $shift_for_assignment = Shift::latest()->first();
        $soldierId = ($this->data['soldier_type'] === 'me') ? auth()->user()->userable_id : $this->data['soldier_id'];
        $shift_for_assignment->soldier_id = $soldierId;
        $shift_for_assignment->save();
    }

    protected static function recurring()
    {
        $task = Task::latest()->first();
        if ($task->recurring['type'] == 'Daily range') {
            $recurringEvents = new RecurringEvents;
            $recurringEvents->dailyRangeTask($task);
        }
        if ($task->recurring['type'] == 'One time') {
            $recurringEvents = new RecurringEvents;
            $recurringEvents->oneTimeTask($task);
        }
    }

    public static function getSteps(): array
    {
        return [
            Step::make('Details')
                ->label(__('Details'))
                ->schema([
                    Section::make()->schema(TaskResource::getTaskDetails())->columns(),
                ]),
            Step::make('Recurring')
                ->label(__('Recurring'))
                ->schema([
                    Section::make()->schema(TaskResource::getRecurring())->columns(),
                ]),
            Step::make('Additional_details')
                ->label(__('Additional settings'))
                ->schema([
                    Section::make()->schema(TaskResource::additionalDetails())->columns(),
                ]),
            Step::make('Assign_soldier')
                ->label(__('Soldier assignment'))
                ->schema([
                    Section::make()->schema(TaskResource::assignSoldier())->columns(),
                ])
                ->visible(
                    fn (Get $get) => $get('recurring.type') == 'One time'
                            && $get('recurring.date')
                ),
        ];
    }
}
