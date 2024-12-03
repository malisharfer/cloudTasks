<?php

namespace App\Models;

use App\Casts\Integer;
use App\Services\ManualAssignment;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'parallel_weight',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime:Y-m-d H:i:s',
        'end_date' => 'datetime:Y-m-d H:i:s',
        'parallel_weight' => Integer::class,
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    private function getTaskParallelWeight()
    {
        return $this->task?->parallel_weight;
    }

    public function getTaskNameAttribute()
    {
        $user_name = User::where('userable_id', $this->soldier_id)->get(['first_name', 'last_name']);

        return $this->soldier_id == auth()->user()->userable_id
        ? $this->task?->name
        : $this->task?->name.' '.$user_name->first()?->first_name.' '.$user_name->first()?->last_name;
    }

    public function getTaskColorAttribute()
    {
        return $this->task?->color;
    }

    public static function getSchema(): array
    {
        return [
            Section::make([
                Placeholder::make('')
                    ->content(content: fn (Shift $shift) => $shift->task_name)
                    ->inlineLabel(),
                Grid::make()
                    ->schema([
                        ToggleButtons::make('soldier_type')
                            ->label(__('Soldier'))
                            ->reactive()
                            ->live()
                            ->inline()
                            ->options(
                                fn (?Shift $shift) => self::getOptions($shift)
                            )
                            ->afterStateUpdated(function (callable $set) {
                                $set('soldier_id', null);
                            }),
                        Select::make('soldier_id')
                            ->label('Soldier assignment')
                            ->options(
                                function (?Shift $shift, Get $get) {
                                    $manual_assignment = new ManualAssignment($shift, $get('soldier_type'));

                                    return $manual_assignment->getSoldiers();
                                }
                            )
                            ->default(null)
                            ->placeholder('Select soldier')
                            ->visible(
                                fn (Get $get): bool => $get('soldier_type') != null
                                && $get('soldier_type') != 'me'
                            ),
                    ])
                    ->visible(
                        fn (?Shift $record): bool => $record !== null
                        && ! $record->soldier_id
                        && \Str::contains($_SERVER['HTTP_REFERER'], 'my-soldiers-shifts')
                        && current(array: array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier']))
                    )
                    ->hiddenOn('view'),
                Toggle::make('is_weekend')
                    ->label(__('Is weekend')),
                TextInput::make('parallel_weight')
                    ->numeric()
                    ->minValue(0)
                    ->label(__('Parallel weight')),
                DateTimePicker::make('start_date')
                    ->label(__('Start date'))
                    ->minDate(today())
                    ->required(),
                DateTimePicker::make('end_date')
                    ->label(__('End date'))
                    ->after('start_date')
                    ->required(),
            ]),
        ];
    }

    public static function afterSave($shift, $record)
    {
        if ((empty($shift['soldier_type'])) || (empty($shift['soldier_id']) && $shift['soldier_type'] != 'me')) {
            return;
        }
        $shift_for_assignment = Shift::find($record->id);
        $soldier = ($shift['soldier_type'] == 'me') ? Soldier::find(auth()->user()->userable_id) : Soldier::find($shift['soldier_id']);
        $shift_for_assignment->soldier_id = $soldier->id;
        $shift_for_assignment->save();
    }

    protected static function getOptions($shift): array
    {
        $options = [
            'reserves' => __('Reserves'),
            'all' => __('All'),
        ];
        $manual_assignment = new ManualAssignment($shift, 'me');
        if ($shift->task->department_name) {
            $options = collect($options)
                ->put('department', '"'.$shift->task->department_name.'" '.__('Department'))
                ->toArray();
        }
        if ($manual_assignment->amIAvailable()) {
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

    public static function getAction($calendar): Action
    {
        $record = is_array($calendar->mountedActionsData) && ! empty($calendar->mountedActionsData) ? (object) $calendar->mountedActionsData[0] : (object) $calendar->mountedActionsData;

        return Action::make('Shift change')
            ->label(__('Shift change'))
            ->color('success')
            ->form(function () use ($record) {
                return [
                    Placeholder::make('')
                        ->content(fn (Shift $shift) => $shift->task_name)
                        ->inlineLabel(),
                    Placeholder::make('')
                        ->content(__('Changing the shifts is your sole responsibility! (pay attention to conflicts between shifts).'))
                        ->extraAttributes(['style' => 'color: red; font-family: Arial, Helvetica, sans-serif; font-size: 20px']),
                    Select::make('soldier_id')
                        ->label(__('New assignment'))
                        ->required()
                        ->options(
                            fn () => Cache::remember('users', 30 * 60, function () {
                                return User::all();
                            })->where('userable_id', '!=', $record->soldier_id)
                                ->mapWithKeys(function ($user) {
                                    return [$user->userable_id => $user->displayName];
                                })
                        ),
                ];
            })
            ->action(function (array $data) use ($record, $calendar) {
                $shift = Shift::where('soldier_id', $record->soldier_id)->first();
                $shift->soldier_id = (int) $data['soldier_id'];
                $shift->save();
                $calendar->refreshRecords();
            });
    }

    public static function getFilters($calendar)
    {
        return Action::make('Filters')
            ->label(__('Filter'))
            ->icon('heroicon-m-funnel')
            ->extraAttributes(['class' => 'fullcalendar'])
            ->form(function () use ($calendar) {
                $shifts = $calendar->getEventsByRole();
                $soldiersShifts = array_filter($shifts->toArray(), fn ($shift) => $shift['soldier_id'] !== null);

                return [
                    Select::make('soldier_id')
                        ->label(__('Soldier'))
                        ->options(fn (): array => collect($soldiersShifts)->mapWithKeys(fn ($shift) => [$shift['soldier_id'] => User::where('userable_id', $shift['soldier_id'])
                            ->first()?->displayName])->toArray())
                        ->multiple(),
                    Select::make('type')
                        ->label(__('Type'))
                        ->options(Task::all()->pluck('type')->unique())
                        ->multiple(),
                ];
            })
            ->modalSubmitActionLabel(__('Filter'))
            ->action(function (array $data) use ($calendar) {
                $calendar->filterData = $data;
                $calendar->filter = $data['soldier_id'] === [] && $data['type'] === [] ? false : true;
                $calendar->refreshRecords();
            });
    }

    public static function filter($events, $filterData)
    {
        if ($filterData['soldier_id'] == []) {
            return $events
                ->whereIn('task_id', $filterData['type'])
                ->values();
        }
        if ($filterData['type'] == []) {
            return $events
                ->whereIn('soldier_id', $filterData['soldier_id'])
                ->values();
        }

        return $events
            ->whereIn('soldier_id', $filterData['soldier_id'])
            ->whereIn('task_id', $filterData['type'])
            ->values();
    }

    public static function activeFilters($calendar)
    {
        $activeFilter = collect();
        if ($calendar->filter) {
            $soldiers = collect($calendar->filterData['soldier_id'])->map(function ($soldier_id) {
                return User::where('userable_id', $soldier_id)->first()->displayName;
            });
            $tasks = collect($calendar->filterData['type'])->map(function ($task_id) {
                return Task::find($task_id)->name;
            });
            $activeFilter = $soldiers->concat($tasks);
        }

        return $activeFilter->toArray();
    }

    public static function getTitle(): string
    {
        return __('Shift');
    }

    public static function setData($record, $data)
    {
        $record->is_weekend ?? $data['is_weekend'] = $record->task->is_weekend === $data['is_weekend'] ? null : $data['is_weekend'];
        if ($record->parallel_weight == 0) {
            $data['parallel_weight'] = $record->task->parallel_weight === $data['parallel_weight'] ? 0 : $data['parallel_weight'];
        }

        return $data;
    }

    public static function fillForm($record, $arguments)
    {
        return [
            ...$record->getAttributes(),
            'is_weekend' => $record->is_weekend === null ? $record->task->is_weekend : $record->is_weekend,
            'parallel_weight' => $record->parallel_weight == 0 ? $record->task->parallel_weight : $record->parallel_weight,
            'start_date' => $arguments['event']['start'] ?? $record->start_date,
            'end_date' => $arguments['event']['end'] ?? $record->end_date,
        ];
    }
}
