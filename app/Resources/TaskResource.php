<?php

namespace App\Resources;

use App\Enums\DaysInWeek;
use App\Enums\RecurringType;
use App\Filters\NumberFilter;
use App\Models\Department;
use App\Models\Shift;
use App\Models\Task;
use App\Resources\TaskResource\Pages;
use App\Services\ManualAssignment;
use Carbon\Carbon;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
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
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                Section::make()->schema(self::getTaskDetails())->columns(),
                Section::make()->schema(self::getRecurring())->columns(),
                Section::make()->schema(self::additionalDetails())->columns(),
                Section::make()->schema(self::assignSoldier())->columns()
                    ->visible(
                        function (Get $get) {
                            return $get('recurring.type') == 'One time'
                                && $get('recurring.date');
                        }
                    ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    TextColumn::make('name')
                        ->description(__('Name'), position: 'above')
                        ->size(TextColumnSize::Small),
                    TextColumn::make('type')
                        ->description(__('Type'), position: 'above')
                        ->size(TextColumnSize::Small),
                    TextColumn::make('parallel_weight')
                        ->description(__('Parallel weight'), position: 'above')
                        ->size(TextColumnSize::Small),
                ])
                    ->extraAttributes(['style' => 'align-items: baseline;']),
                Split::make([
                    TextColumn::make('department_name')
                        ->description(__('Department'), position: 'above')
                        ->size(TextColumnSize::Small),
                    ColorColumn::make('color')
                        ->copyable()
                        ->copyMessage('Color code copied'),
                ])
                    ->extraAttributes(['style' => 'align-items: baseline;']),
                Panel::make([
                    Stack::make([
                        TextColumn::make('start_hour')
                            ->description(__('Start at'), position: 'above')
                            ->size(TextColumnSize::Small)
                            ->extraAttributes(['style' => 'margin: 5px;']),
                        TextColumn::make('duration')
                            ->description(__('Duration'), position: 'above')
                            ->size(TextColumnSize::Small)
                            ->extraAttributes(['style' => 'margin: 5px;']),
                        TextColumn::make('is_alert')
                            ->description(__('Alert'), position: 'above')
                            ->extraAttributes(['style' => 'margin: 5px;'])
                            ->size(TextColumnSize::Small)
                            ->formatStateUsing(fn ($state) => $state ? __('Yes') : __('No')),
                        TextColumn::make('is_weekend')
                            ->description(__('Is weekend'), position: 'above')
                            ->size(TextColumnSize::Small)
                            ->extraAttributes(['style' => 'margin: 5px;'])
                            ->formatStateUsing(fn ($state) => $state ? __('Yes') : __('No')),
                        TextColumn::make('is_night')
                            ->description(__('Is night'), position: 'above')
                            ->size(TextColumnSize::Small)
                            ->extraAttributes(['style' => 'margin: 5px;'])
                            ->formatStateUsing(fn ($state) => $state ? __('Yes') : __('No')),

                    ])
                        ->space(2)
                        ->extraAttributes(['style' => 'display: flex; flex-direction: row; flex-wrap: wrap; justify-content: space-between; align-items: baseline; margin-bottom:10px']),
                    Stack::make([
                        TextColumn::make('recurring.type')
                            ->description(__('Recurring type'), position: 'above')
                            ->size(TextColumnSize::Small)
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
                                    case 'One time':
                                        return __('One time');
                                    case 'Daily range':
                                        return __('Daily range');
                                }
                            }),
                        TextColumn::make('recurring.days_in_week')
                            ->description(__('Days in week'), position: 'above')
                            ->size(TextColumnSize::Small)
                            ->extraAttributes(['style' => 'margin-left: 15px;']),
                        TextColumn::make('recurring.dates_in_month')
                            ->description(__('Dates in month'), position: 'above')
                            ->size(TextColumnSize::Small)
                            ->extraAttributes(['style' => 'margin-left: 15px;']),
                        TextColumn::make('recurring.start_date')
                            ->description(__('StartDate'), position: 'above')
                            ->size(TextColumnSize::Small)
                            ->extraAttributes(['style' => 'margin-left: 15px;']),
                        TextColumn::make('recurring.end_date')
                            ->description(__('EndDate'), position: 'above')
                            ->size(TextColumnSize::Small)
                            ->extraAttributes(['style' => 'margin-left: 15px;']),
                    ])
                        ->space(2)
                        ->extraAttributes([
                            'style' => 'display: flex;
                            flex-direction: row;
                            flex-wrap: wrap;
                            justify-content: center;
                            align-items: baseline;
                            border: 1px solid #e7e7e7;
                            border-radius: 10px;
                            padding: 10px;',
                        ]),
                ])->collapsible(),

            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('Task type'))
                    ->multiple()
                    ->searchable()
                    ->options(
                        Task::all()->pluck('type', 'type')
                    )
                    ->default(null),
                SelectFilter::make('recurring.type')
                    ->label(__('Recurring type'))
                    ->multiple()
                    ->searchable()
                    ->options(collect(RecurringType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->getLabel()]))
                    ->query(function (Builder $query, array $data) {
                        return collect($data['values'])->map(function ($type) use ($query) {
                            return $query->orWhereJsonContains('recurring', $type);
                        });
                    })
                    ->default(null),
                NumberFilter::make('parallel_weight')
                    ->label(__('Parallel weight')),
                Filter::make('is_alert')
                    ->label(__('Is alert'))
                    ->query(fn (Builder $query): Builder => $query->where('is_alert', true))
                    ->toggle(),
                Filter::make('is_weekend')
                    ->label(__('Is weekend'))
                    ->query(fn (Builder $query): Builder => $query->where('is_weekend', true))
                    ->toggle(),
                Filter::make('is_night')
                    ->label(__('Is night'))
                    ->query(fn (Builder $query): Builder => $query->where('is_night', true))
                    ->toggle(),
                SelectFilter::make('department_name')
                    ->label(__('Department name'))
                    ->multiple()
                    ->searchable()
                    ->options(
                        Department::all()->pluck('name', 'name')
                    )
                    ->default(null),
            ], FiltersLayout::Modal)
            ->deferFilters()
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label(__('Filter'))
            )
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
                        ->label(__('Delete'))
                        ->modalHeading(__('Delete').' '.self::getModelLabel()),
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
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }

    public static function getTaskDetails(): array
    {
        return [
            TextInput::make('name')
                ->label(__('Name'))
                ->required(),
            Select::make('department_name')
                ->label(__('Department'))
                ->options(Department::all()->mapWithKeys(function ($department) {
                    return [$department->name => $department->name];
                })),
            TextInput::make('type')
                ->label(__('Type'))
                ->required(),
            TextInput::make('parallel_weight')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->step(0.25)
                ->label(__('Parallel weight')),
            ColorPicker::make('color')
                ->label(__('Color'))
                ->required(),
            TimePicker::make('start_hour')
                ->label(__('Start hour'))
                ->seconds(false)
                ->required(),
            TextInput::make('duration')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->label(__('Duration'))
                ->required(),
        ];
    }

    public static function getRecurring(): array
    {
        return [
            ToggleButtons::make('recurring.type')
                ->label(__('Type'))
                ->options(collect(RecurringType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->getLabel()]))->live()
                ->required()
                ->inline(),
            Select::make('recurring.days_in_week')
                ->label(__('Days in week'))
                ->multiple()
                ->options(
                    collect(DaysInWeek::cases())->mapWithKeys(fn ($type) => [$type->value => $type->getLabel()])
                )
                ->visible(fn (Get $get): bool => $get('recurring.type') === 'Weekly')
                ->default(null)
                ->required(),
            Select::make('recurring.dates_in_month')
                ->label(fn (Get $get) => $get('recurring.type') === 'Monthly' ? __('Date') : __('Dates'))
                ->placeholder(__('Select from dates'))
                ->multiple(fn (Get $get): bool => $get('recurring.type') === 'Custom')
                ->options(array_combine(range(1, 31), range(1, 31)))
                ->visible(fn (Get $get): bool => $get('recurring.type') === 'Monthly' || $get('recurring.type') === 'Custom')
                ->default(null)
                ->live()
                ->required(),
            Fieldset::make(__('Dates'))
                ->schema([
                    DatePicker::make('recurring.start_date')
                        ->label(label: __('Start date'))
                        ->required(),
                    DatePicker::make('recurring.end_date')
                        ->label(label: __('End date'))
                        ->after('recurring.start_date')
                        ->required(),
                ])->visible(fn (Get $get): bool => $get('recurring.type') === 'Daily range'),

            DatePicker::make('recurring.date')
                ->label(__('Date'))
                ->required()
                ->minDate(today())
                ->live()
                ->visible(fn (Get $get) => $get('recurring.type') === 'One time'),
        ];
    }

    public static function assignSoldier(): array
    {
        return [
            Fieldset::make(__('Soldier assignment'))
                ->schema([
                    Grid::make()
                        ->schema([
                            ToggleButtons::make('soldier_type')
                                ->label(__('Soldier type'))
                                ->reactive()
                                ->live()
                                ->inline()
                                ->options(
                                    fn (Get $get) => self::getOptions($get)
                                )
                                ->afterStateUpdated(function (callable $set) {
                                    $set('soldier_id', null);
                                }),
                            Select::make('soldier_id')
                                ->label(__('Assign soldier'))
                                ->options(
                                    function (Get $get) {
                                        return self::getSoldiers($get);
                                    }
                                )
                                ->default(null)
                                ->placeholder('Select soldier')
                                ->visible(
                                    fn (Get $get): bool => $get('soldier_type')
                                    && $get('soldier_type') != 'me'
                                ),
                        ]),
                ]),
        ];
    }

    public static function additionalDetails(): array
    {
        return [
            Toggle::make('is_alert')
                ->label(__('Is alert')),
            Toggle::make('is_weekend')
                ->label(__('Is weekend')),
            Toggle::make('is_night')
                ->label(__('Is night')),
        ];
    }

    protected static function getOptions(Get $get): array
    {
        $options = [
            'reserves' => __('Reserves'),
            'all' => __('All'),
        ];
        if ($get('department_name')) {
            $options = collect($options)
                ->put('department', '"'.$get('department_name').'" '.__('Department'))
                ->toArray();
        }
        if (self::amIAvailable($get)) {
            $options = collect($options)
                ->put('me', __('Me'))
                ->toArray();
        }
        if (current(array: array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier'])) != 'manager') {
            return collect($options)
                ->put('my_soldiers', __('My Soldiers'))
                ->toArray();
        }

        return $options;
    }

    protected static function amIAvailable($get)
    {
        $shift = self::taskDetails($get);
        $manual_assignment = new ManualAssignment($shift, 'me');

        return $manual_assignment->amIAvailable($get('type'));
    }

    protected static function getSoldiers(Get $get)
    {
        $shift = self::taskDetails($get);
        $manual_assignment = new ManualAssignment($shift, $get('soldier_type'));

        return $manual_assignment->getSoldiers($get('type'), $get('department_name'));
    }

    protected static function taskDetails(Get $get)
    {
        $task_date = Carbon::parse($get('recurring.date'));
        $task = new Task;
        $task->type = $get('type');
        $task->is_night = $get('is_night');
        $task->is_weekend = $get('is_weekend');
        $shift = new Shift;
        $shift->id = null;
        $shift->task = $task;
        $shift->start_date = Carbon::parse($task_date->format('Y-m-d').' '.$get('start_hour'));
        $shift->end_date = $shift->start_date->copy()->addHours((float) ($get('duration')));
        $shift->parallel_weight = $get('parallel_weight');

        return $shift;
    }
}
