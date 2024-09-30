<?php

namespace App\Resources;

use App\Enums\RecurrenceType;
use App\Models\Department;
use App\Models\Task;
use App\Resources\TaskResource\Pages;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Table;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-m-squares-plus';

    public static function getModelLabel(): string
    {
        return __('Task');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Tasks');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()->schema(TaskResource::getTaskDetails())->columns(),
                Section::make()->schema(TaskResource::getRecurrence())->columns(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    TextColumn::make('name')
                        ->description(__('Name'), position: 'above')
                        ->size(TextColumnSize::Large),
                    TextColumn::make('type')
                        ->description(__('Type'), position: 'above')
                        ->size(TextColumnSize::Large),
                    TextColumn::make('parallel_weight')
                        ->description(__('Parallel weight'), position: 'above')
                        ->size(TextColumnSize::Large),
                    ColorColumn::make('color')
                        ->copyable()
                        ->copyMessage('Color code copied'),
                ]),
                Panel::make([
                    Stack::make([
                        TextColumn::make('start_hour')
                            ->description(__('Start at'), position: 'above')
                            ->size(TextColumnSize::Large),
                        TextColumn::make('duration')
                            ->description(__('Duration'), position: 'above')
                            ->size(TextColumnSize::Large),
                        TextColumn::make('is_alert')
                            ->description(__('Alert'), position: 'above')
                            ->size(TextColumnSize::Large)
                            ->formatStateUsing(fn ($state) => $state ? __('Yes') : __('No')),
                        TextColumn::make('department_name')
                            ->description(__('Department'), position: 'above')
                            ->size(TextColumnSize::Large),
                    ])
                        ->space(2)
                        ->extraAttributes(['style' => 'display: flex; flex-direction: row; flex-wrap: wrap; justify-content: space-between; align-items: center;']),
                    Stack::make([
                        TextColumn::make('recurrence.type')
                            ->description(__('Recurrence type'), position: 'above')
                            ->size(TextColumnSize::Large)
                            ->formatStateUsing(function ($state) {
                                switch ($state) {
                                    case 'Daily':
                                        return __('Daily');
                                    case 'Weekly':
                                        return __('Weekly');
                                    case 'Monthly':
                                        return __('Monthly');
                                    case 'Custom':
                                        return __('Custom');
                                    case 'OneTime':
                                        return __('OneTime');
                                }
                            }),
                        TextColumn::make('recurrence.days_in_week')
                            ->description(__('Days in week'), position: 'above')
                            ->size(TextColumnSize::Large),
                        TextColumn::make('recurrence.dates_in_month')
                            ->description(__('Dates in month'), position: 'above')
                            ->size(TextColumnSize::Large),
                        TextColumn::make('recurrence.start_date')
                            ->description(__('StartDate'), position: 'above')
                            ->size(TextColumnSize::Large),
                        TextColumn::make('recurrence.end_date')
                            ->description(__('EndDate'), position: 'above')
                            ->size(TextColumnSize::Large),
                    ])
                        ->space(2)
                        ->extraAttributes(['style' => 'display: flex; flex-direction: row; flex-wrap: wrap; justify-content: center; align-items: center; border: 1px solid #e7e7e7; border-radius: 10px; padding: 10px;']),
                ])->collapsible(),

            ])
            ->filters([
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),

            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
        ];
    }

    public static function getTaskDetails(): array
    {
        return [
            TextInput::make('name')
                ->label(__('Name'))
                ->required(),
            TimePicker::make('start_hour')
                ->label(__('Start hour'))
                ->seconds(false)
                ->required(),
            TimePicker::make('duration')
                ->seconds(false)
                ->label(__('Duration'))
                ->required(),
            Select::make('parallel_weight')
                ->label(__('Parallel weight'))
                ->options(fn (): array => collect(range(0, 12))->mapWithKeys(fn ($number) => [(string) ($number / 4) => (string) ($number / 4)])->toArray())
                ->required(),
            TextInput::make('type')
                ->label(__('Type'))
                ->required(),
            ColorPicker::make('color')
                ->label(__('Color'))
                ->required(),
            Toggle::make('is_alert')
                ->label(__('Is alert')),
            Select::make('department_name')
                ->label(__('Department'))
                ->options(Department::all()->mapWithKeys(function ($department) {
                    return [$department->name => $department->name];
                })),
        ];
    }

    public static function getRecurrence(): array
    {
        return [
            ToggleButtons::make('recurrence.type')
                ->label(__('Type'))
                ->options(collect(RecurrenceType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->getLabel()]))->live()
                ->required()
                ->inline()
                ->grouped(),
            Select::make('recurrence.days_in_week')
                ->label(__('Days in week'))
                ->multiple()
                ->options(
                    [
                        '1' => __('Sunday'),
                        '2' => __('Monday'),
                        '3' => __('Tuesday'),
                        '4' => __('Wednesday'),
                        '5' => __('Thursday'),
                        '6' => __('Friday'),
                        '7' => __('Saturday'),
                    ]
                )
                ->visible(fn (Get $get): bool => $get('recurrence.type') === 'Weekly')
                ->default(null)
                ->required(),
            Select::make('recurrence.dates_in_month')
                ->label(fn (Get $get) => $get('recurrence.type') === 'Monthly' ? 'Date' : 'Dates')
                ->placeholder(__('Select from dates'))
                ->multiple(fn (Get $get): bool => $get('recurrence.type') === 'Custom')
                ->options(array_combine(range(1, 31), range(1, 31)))
                ->visible(fn (Get $get): bool => $get('recurrence.type') === 'Monthly' || $get('recurrence.type') === 'Custom')
                ->default(null)
                ->required(),
            Fieldset::make('Dates')
                ->label(__('Dates'))
                ->schema([
                    DatePicker::make('recurrence.start_date')
                        ->label(label: __('Start date')),
                    DatePicker::make('recurrence.end_date')
                        ->label(label: __('End date')),
                ])->visible(fn (Get $get): bool => $get('recurrence.type') === 'OneTime'),
        ];
    }
}
