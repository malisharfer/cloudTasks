<?php

namespace App\Resources\TaskResource\Pages;

use App\Enums\RecurrenceType;
use App\Models\Department;
use App\Models\Task;
use App\Resources\TaskResource;
use App\Services\ReccurenceEvents;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Get;
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
                ->schema([
                    TextInput::make('name')
                        ->required(),
                    Forms\Components\TimePicker::make('start_hour')
                        ->seconds(false)
                        ->required(),
                    TextInput::make('duration')
                        ->required(),
                    Select::make('parallel_weight')
                        ->options(fn (): array => collect(range(0, 12))->mapWithKeys(fn ($number) => [(string) ($number / 4) => (string) ($number / 4)])->toArray())
                        ->required(),
                    TextInput::make('type')
                        ->required(),
                    ColorPicker::make('color')
                        ->required(),
                    Checkbox::make('is_alert'),
                    Select::make('department_name')
                        ->options(Department::all()->mapWithKeys(function ($department) {
                            return [$department->name => $department->name];
                        })),
                ]),
            Step::make('Recurrence')
                ->schema([
                    Select::make('recurrence.type')
                        ->options(RecurrenceType::class)
                        ->live()
                        ->required(),
                    Select::make('recurrence.days_in_week')
                        ->multiple()
                        ->options(
                            [
                                'Sunday' => 'Sunday',
                                'Monday' => 'Monday',
                                'Tuesday' => 'Tuesday',
                                'Wednesday' => 'Wednesday',
                                'Thursday' => 'Thursday',
                                'Friday' => 'Friday',
                                'Saturday' => 'Saturday',
                            ]
                        )
                        ->visible(fn (Get $get): bool => $get('recurrence.type') === 'Weekly')
                        ->default(null)
                        ->required(),
                    Select::make('recurrence.dates_in_month')
                        ->label(fn (Get $get) => $get('recurrence.type') === 'Monthly' ? 'Date' : 'Dates')
                        ->placeholder('Select from dates')
                        ->multiple(fn (Get $get): bool => $get('recurrence.type') === 'Custom')
                        ->options(array_combine(range(1, 31), range(1, 31)))
                        ->visible(fn (Get $get): bool => $get('recurrence.type') === 'Monthly' || $get('recurrence.type') === 'Custom')
                        ->default(null)
                        ->required(),
                    Fieldset::make('Dates')
                        ->schema([
                            DatePicker::make('recurrence.start_date'),
                            DatePicker::make('recurrence.end_date'),
                        ])->visible(fn (Get $get): bool => $get('recurrence.type') === 'OneTime'),
                ]),
        ];
    }
}
