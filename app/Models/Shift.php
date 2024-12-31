<?php

namespace App\Models;

use App\Casts\Integer;
use App\Services\ChangeAssignment;
use App\Services\ManualAssignment;
use Cache;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Livewire\Component;

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
                            ->label(__('Soldier assignment'))
                            ->options(
                                function (?Shift $shift, Get $get) {
                                    if ($get('soldier_type') === 'all') {
                                        return Cache::remember('users', 30 * 60, function () {
                                            return User::all();
                                        })
                                            ->mapWithKeys(function ($user) {
                                                return [$user->userable_id => $user->displayName];
                                            });
                                    }
                                    $manual_assignment = new ManualAssignment($shift, $get('soldier_type'));

                                    return $manual_assignment->getSoldiers();
                                }
                            )
                            ->default(null)
                            ->placeholder(__('Select a soldier'))
                            ->visible(
                                fn (Get $get): bool => $get('soldier_type') != null
                                && $get('soldier_type') != 'me'
                            ),
                        Placeholder::make('')
                            ->content(__('Assigning the soldier to this shift is your sole responsibility!'))
                            ->extraAttributes(['style' => 'color: red; font-family: Arial, Helvetica, sans-serif; font-size: 20px'])
                            ->live()
                            ->visible(fn (Get $get) => $get('soldier_type') === 'all'),
                    ])
                    ->visible(
                        fn (?Shift $record): bool => $record !== null
                        && Carbon::parse($record->start_date)->isAfter(now())
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
            'matching' => __('Matching soldiers'),
            'all' => __('All'),
        ];
        if ($shift->task->department_name) {
            $options = collect($options)
                ->put('department', '"'.$shift->task->department_name.'" '.__('Department'))
                ->toArray();
        }
        $manual_assignment = new ManualAssignment($shift, 'me');
        if ($manual_assignment->amIAvailable()) {
            $options = collect($options)
                ->put('me', __('Me'))
                ->toArray();
        }
        if (current(array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier'])) != 'manager') {
            return collect($options)
                ->put('my_soldiers', __('My Soldiers'))
                ->toArray();
        }

        return $options;
    }

    public static function exchangeAction(): Action
    {
        return Action::make('Exchange')
            ->label(__('Exchange'))
            ->icon('heroicon-s-arrow-path')
            ->cancelParentActions()
            ->closeModalByClickingAway(false)
            ->modalCancelAction(false)
            ->modalSubmitAction(false)
            ->modalCloseButton(false)
            ->form(
                function ($record) {
                    session()->put('selected_shift', false);
                    $changeAssignment = new ChangeAssignment($record);
                    $sections = $changeAssignment->getMatchingShifts()
                        ->map(
                            function ($shifts, $soldierId) {
                                return Section::make()
                                    ->id($soldierId)
                                    ->description(__('Exchange with').' '.Soldier::find($soldierId)->user->displayName)
                                    ->schema(
                                        $shifts->map(
                                            function ($shift) {
                                                return Section::make()
                                                    ->id($shift->id)
                                                    ->schema([
                                                        Radio::make('selected_shift')
                                                            ->label(__(''))
                                                            ->options([
                                                                $shift->id => __('Task').': '.Task::find($shift->task_id)->name.' '.__('Time').': '.__('From').' '.$shift->start_date.' '.__('To').' '.$shift->end_date,
                                                            ])
                                                            ->default(null)
                                                            ->afterStateUpdated(fn () => session()->put('selected_shift', true))
                                                            ->live()
                                                            ->reactive(),
                                                    ]);
                                            }
                                        )
                                            ->toArray()
                                    )
                                    ->collapsed();
                            }
                        );

                    return array_merge(
                        [
                            Placeholder::make('')
                                ->content(fn (Shift $shift) => $shift->task_name)
                                ->inlineLabel(),
                            Placeholder::make('')
                                ->content(fn (Shift $shift) => $shift->start_date.' - '.$shift->end_date)
                                ->inlineLabel(),
                        ],
                        $sections->toArray()
                    );
                }
            )
            ->extraModalFooterActions(function (Action $action) {
                return [
                    $action->makeExtraModalAction('exchange', ['exchange' => true])
                        ->label(__('Exchange assignment'))
                        ->icon('heroicon-s-arrow-path')
                        ->color('primary')
                        ->disabled(fn (): bool => ! session()->get('selected_shift'))
                        ->visible(fn (): bool => current(array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier']))),
                    $action->makeExtraModalAction(__('Request'), ['request' => true])
                        ->icon('heroicon-s-arrow-path')
                        ->disabled(fn (): bool => ! session()->get('selected_shift'))
                        ->color('primary')
                        ->visible(fn (): bool => ! current(array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier']))),
                    $action->makeExtraModalAction(__('Cancel'), ['cancel' => true]),
                ];
            })
            ->action(function (array $data, array $arguments, Model $record, Component $livewire): void {
                session()->put('selected_shift', false);
                if ($arguments['exchange'] ?? false) {
                    self::commanderExchange($record, Shift::find($data['selected_shift']));
                    $livewire->dispatch('filament-fullcalendar--refresh');
                }
                if ($arguments['request'] ?? false) {
                    self::soldierExchange($record, Shift::find($data['selected_shift']));
                    $livewire->dispatch('filament-fullcalendar--refresh');
                }
                if ($arguments['cancel'] ?? false) {
                    $livewire->dispatch('filament-fullcalendar--refresh');
                }
            });
    }

    protected static function commanderExchange($record, $shift)
    {
        self::sendExchangeNotifications($record, $shift);
        $changeAssignment = new ChangeAssignment($record);
        $changeAssignment->exchange($shift);
    }

    protected static function sendExchangeNotifications($shiftA, $shiftB)
    {
        $soldierA = Soldier::find($shiftA->soldier_id);
        $soldierB = Soldier::find($shiftB->soldier_id);
        if ($soldierA->id !== auth()->user()->userable_id) {
            self::sendNotification(
                __('Exchange shift'),
                __(
                    'Commander notification of exchanging shifts for first soldier',
                    [
                        'soldierAName' => $soldierA->user->displayName,
                        'shiftAName' => $shiftA->task->name,
                        'shiftAStart' => $shiftA->start_date,
                        'shiftAEnd' => $shiftA->end_date,
                        'soldierBName' => $soldierB->user->displayName,
                        'shiftBName' => $shiftB->task->name,
                        'shiftBStart' => $shiftB->start_date,
                        'shiftBEnd' => $shiftB->end_date,
                        'commander' => auth()->user()->displayName,
                    ]
                ),
                [],
                $soldierA->user
            );
            self::sendNotification(
                __('Exchange shift'),
                __(
                    'Commander notification of exchanging shifts for second soldier',
                    [
                        'soldierBName' => $soldierB->user->displayName,
                        'shiftBName' => $shiftB->task->name,
                        'shiftBStart' => $shiftB->start_date,
                        'shiftBEnd' => $shiftB->end_date,
                        'soldierAName' => $soldierA->user->displayName,
                        'shiftAName' => $shiftA->task->name,
                        'shiftAStart' => $shiftA->start_date,
                        'shiftAEnd' => $shiftA->end_date,
                        'commander' => auth()->user()->displayName,
                    ]
                ),
                [],
                $soldierB->user
            );
        } else {
            self::sendNotification(
                __('Exchange shift'),
                __(
                    'Commander notification of exchanging shifts for second soldier',
                    [
                        'soldierBName' => $soldierB->user->displayName,
                        'shiftBName' => $shiftB->task->name,
                        'shiftBStart' => $shiftB->start_date,
                        'shiftBEnd' => $shiftB->end_date,
                        'soldierAName' => $soldierA->user->displayName,
                        'shiftAName' => $shiftA->task->name,
                        'shiftAStart' => $shiftA->start_date,
                        'shiftAEnd' => $shiftA->end_date,
                        'commander' => auth()->user()->displayName,
                    ]
                ),
                [],
                $soldierB->user
            );
        }
    }

    protected static function soldierExchange($record, $shift)
    {
        $user = Soldier::find($shift->soldier_id)->user;
        self::sendNotification(
            __('Request for shift exchange'),
            __(
                'Request for shift exchange from soldier',
                [
                    'soldierAName' => $user->displayName,
                    'shiftAName' => $shift->task->name,
                    'shiftAStart' => $shift->start_date,
                    'shiftAEnd' => $shift->end_date,
                    'shiftBName' => $record->task->name,
                    'shiftBStart' => $record->start_date,
                    'shiftBEnd' => $record->end_date,
                    'soldierBName' => Soldier::find($record->soldier_id)->user->displayName,
                ]
            ),
            [
                NotificationAction::make('confirm')
                    ->label(__('Confirm'))
                    ->color('success')
                    ->icon('heroicon-s-hand-thumb-up')
                    ->button()
                    ->dispatch('confirmExchange', [
                        'approverRole' => current(array_diff(collect(Soldier::find($shift->soldier_id)->user->getRoleNames())->toArray(), ['soldier'])),
                        'requestingSoldier' => $record->soldier_id,
                        'approvingSoldier' => $shift->soldier_id,
                        'shiftA' => $record->id,
                        'shiftB' => $shift->id,
                    ])
                    ->close(),
                NotificationAction::make('deny')
                    ->label(__('Deny'))
                    ->color('danger')
                    ->icon('heroicon-m-hand-thumb-down')
                    ->button()
                    ->dispatch('denyExchange', [
                        'rejectorRole' => current(array_diff(collect(Soldier::find($shift->soldier_id)->user->getRoleNames())->toArray(), ['soldier'])),
                        'requestingSoldier' => $record->soldier_id,
                        'rejectingSoldier' => $shift->soldier_id,
                        'shiftA' => $record->id,
                        'shiftB' => $shift->id,
                    ])
                    ->close(),
            ],
            $user
        );
    }

    public static function changeAction(): Action
    {
        return Action::make('Change')
            ->label(__('Change'))
            ->icon('heroicon-o-arrow-uturn-up')
            ->cancelParentActions()
            ->closeModalByClickingAway(false)
            ->modalCancelAction(false)
            ->modalSubmitAction(false)
            ->modalCloseButton(false)
            ->form(
                function ($record) use (&$soldiers) {
                    $changeAssignment = new ChangeAssignment($record);
                    session()->put('selected_soldier', false);

                    return [
                        Section::make()
                            ->id($record->id)
                            ->description(__('Change shift'))
                            ->schema(
                                [
                                    Placeholder::make(__('Task'))
                                        ->content(Task::find($record->task_id)->name),
                                    Placeholder::make(__('Soldier'))
                                        ->content(Soldier::find($record->soldier_id)->user->displayName),
                                    Placeholder::make(__('Time'))
                                        ->content(__('From').' '.$record->start_date.' '.__('To').' '.$record->end_date),
                                    Placeholder::make('')
                                        ->content(__('Changing the shifts is your sole responsibility! (pay attention to conflicts between shifts).'))
                                        ->extraAttributes(['style' => 'color: red; font-family: Arial, Helvetica, sans-serif; font-size: 20px'])
                                        ->live()
                                        ->visible(fn (Get $get) => $get('soldiers') == 'all'),
                                    ToggleButtons::make('soldiers')
                                        ->label('')
                                        ->options(['all' => __('All soldiers'), 'matching' => __('Matching soldiers')])
                                        ->inline()
                                        ->live()
                                        ->default(fn () => 'matching')
                                        ->visible(fn (): bool => current(array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier'])))
                                        ->afterStateUpdated(function (callable $set) {
                                            $set('soldier', null);
                                            session()->put('selected_soldier', false);
                                        }),
                                    Select::make('soldier')
                                        ->label(__('Soldier'))
                                        ->options(
                                            fn (Get $get) => match ($get('soldiers')) {
                                                'all' => Cache::remember('users', 30 * 60, function () {
                                                    return User::all();
                                                })->where('userable_id', '!=', $record->soldier_id)
                                                    ->mapWithKeys(function ($user) {
                                                        return [$user->userable_id => $user->displayName];
                                                    }),
                                                'matching' => $changeAssignment->getMatchingSoldiers(),
                                                default => $changeAssignment->getMatchingSoldiers(),
                                            }
                                        )
                                        ->placeholder(__('Select a soldier'))
                                        ->afterStateUpdated(
                                            fn () => session()->put('selected_soldier', true)
                                        )
                                        ->live()
                                        ->reactive(),
                                ]
                            ),
                    ];
                }
            )
            ->extraModalFooterActions(
                function (Action $action): array {
                    return [
                        $action->makeExtraModalAction('change', ['change' => true])
                            ->label(__('Change assignment'))
                            ->icon('heroicon-o-arrow-uturn-up')
                            ->color('primary')
                            ->disabled(fn (): bool => ! session()->get('selected_soldier'))
                            ->visible(fn (): bool => current(array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier']))),
                        $action->makeExtraModalAction(__('Request'), ['request' => true])
                            ->icon('heroicon-o-arrow-uturn-up')
                            ->disabled(fn (): bool => ! session()->get('selected_soldier'))
                            ->color('primary')
                            ->visible(fn (): bool => ! current(array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier']))),
                        $action->makeExtraModalAction(__('Cancel'), ['cancel' => true]),
                    ];
                }
            )
            ->action(function (array $data, array $arguments, Model $record, Component $livewire): void {
                session()->put('selected_soldier', false);
                if ($arguments['change'] ?? false) {
                    self::commanderChange($record, $data['soldier']);
                    $livewire->dispatch('filament-fullcalendar--refresh');
                }
                if ($arguments['request'] ?? false) {
                    self::soldierChange($record, $data['soldier']);
                    $livewire->dispatch('filament-fullcalendar--refresh');
                }
                if ($arguments['cancel'] ?? false) {
                    $livewire->dispatch('filament-fullcalendar--refresh');
                }
            });
    }

    protected static function commanderChange($shift, $soldierId)
    {
        self::sendChangeNotifications($shift, $soldierId);
        Shift::where('id', $shift->id)->update(['soldier_id' => $soldierId]);
    }

    protected static function sendChangeNotifications($shift, $soldierId)
    {
        $soldier = Soldier::find($soldierId);
        if ($shift->soldier_id !== auth()->user()->userable_id) {
            self::sendNotification(
                __('Change assignment'),
                __(
                    'Commander notification of changing shifts for first soldier',
                    [
                        'soldierName' => $soldier->user->displayName,
                        'shiftName' => $shift->task->name,
                        'shiftStart' => $shift->start_date,
                        'shiftEnd' => $shift->end_date,
                        'commander' => auth()->user()->displayName,
                    ]
                ),
                [],
                $soldier->user
            );
            self::sendNotification(
                __('Change assignment'),
                __(
                    'Commander notification of changing shifts for second soldier',
                    [
                        'soldierName' => Soldier::find($shift->soldier_id)->user->displayName,
                        'shiftName' => $shift->task->name,
                        'shiftStart' => $shift->start_date,
                        'shiftEnd' => $shift->end_date,
                        'commander' => auth()->user()->displayName,
                    ]
                ),
                [],
                Soldier::find($shift->soldier_id)->user
            );
        } else {
            self::sendNotification(
                __('Change assignment'),
                __(
                    'Commander notification of changing shifts for first soldier',
                    [
                        'soldierName' => $soldier->user->displayName,
                        'shiftName' => $shift->task->name,
                        'shiftStart' => $shift->start_date,
                        'shiftEnd' => $shift->end_date,
                        'commander' => auth()->user()->displayName,
                    ]
                ),
                [],
                $soldier->user
            );
        }
    }

    protected static function soldierChange($record, $soldierId)
    {
        $soldier = Soldier::find($soldierId);
        self::sendNotification(
            __('Request for shift change'),
            __(
                'Request for shift change from soldier',
                [
                    'soldierName' => $soldier->user->displayName,
                    'shiftName' => $record->task->name,
                    'shiftStart' => $record->start_date,
                    'shiftEnd' => $record->end_date,
                    'requestingSoldierName' => Soldier::find($record->soldier_id)->user->displayName,
                ]
            ),
            [
                NotificationAction::make('confirm')
                    ->label(__('Confirm'))
                    ->color('success')
                    ->icon('heroicon-s-hand-thumb-up')
                    ->button()
                    ->dispatch('confirmChange', [
                        'approverRole' => current(array_diff(collect(Soldier::find($record->soldier_id)->user->getRoleNames())->toArray(), ['soldier'])),
                        'shift' => $record->id,
                        'soldierId' => $soldierId,
                    ])
                    ->close(),
                NotificationAction::make('deny')
                    ->label(__('Deny'))
                    ->color('danger')
                    ->icon('heroicon-m-hand-thumb-down')
                    ->button()
                    ->dispatch('denyChange', [
                        'rejectorRole' => current(array_diff(collect(Soldier::find($record->soldier_id)->user->getRoleNames())->toArray(), ['soldier'])),
                        'shift' => $record->id,
                        'soldierId' => $soldierId,
                    ])
                    ->close(),
            ],
            $soldier->user
        );
    }

    protected static function sendNotification($title, $body, $actions, $user)
    {
        Notification::make()
            ->title($title)
            ->persistent()
            ->body(
                $body
            )
            ->actions(
                $actions
            )
            ->sendToDatabase($user, true);
    }

    public static function getFilters($calendar)
    {
        return Action::make('Filters')
            ->iconButton()
            ->label(__('Filter'))
            ->icon('heroicon-o-funnel')
            ->extraAttributes(['class' => 'fullcalendar'])
            ->form(function () use ($calendar) {
                $shifts = $calendar->getEventsByRole();
                $soldiersShifts = array_filter($shifts->toArray(), fn ($shift) => $shift['soldier_id'] !== null);

                return [
                    Select::make('soldier_id')
                        ->label(__('Soldier'))
                        ->options(fn (): array => collect($soldiersShifts)->mapWithKeys(fn ($shift) => [
                            $shift['soldier_id'] => User::where('userable_id', $shift['soldier_id'])
                                ->first()?->displayName,
                        ])->toArray())
                        ->multiple(),
                    Select::make('type')
                        ->label(__('Type'))
                        ->options(Task::all()->pluck('type', 'id')->unique())
                        ->multiple(),
                ];
            })
            ->modalSubmitAction(false)
            ->extraModalFooterActions(fn (Action $action): array => [
                $action->makeModalSubmitAction('Filter', arguments: ['Filter' => true])->color('success')->label(__('Filter')),
                $action->makeModalSubmitAction('Unassigned shifts', arguments: ['UnassignedShifts' => true])->color('primary')->label(__('Unassigned shifts')),
            ])
            ->action(function (array $data, array $arguments) use ($calendar) {
                $data['type'] = Task::whereIn(
                    'type',
                    Task::whereIn('id', $data['type'])
                        ->pluck('type')
                )
                    ->pluck('id');
                if ($arguments['Filter'] ?? false) {
                    $calendar->filterData = $data;
                    $calendar->filter = ! ($data['soldier_id'] === [] && $data['type'] === []);
                    $calendar->refreshRecords();
                }
                if ($arguments['UnassignedShifts'] ?? false) {
                    $calendar->filterData = 'UnassignedShifts';
                    $calendar->filter = true;
                    $calendar->refreshRecords();
                }
            });
    }

    public static function filter($events, $filterData)
    {
        return $events
            ->when($filterData === 'UnassignedShifts', fn ($query) => $query
                ->where('soldier_id', null))
            ->when(! empty($filterData['soldier_id']), fn ($query) => $query
                ->whereIn('soldier_id', $filterData['soldier_id']))
            ->when(! empty($filterData['type']), fn ($query) => $query
                ->whereIn('task_id', $filterData['type']))
            ->values();
    }

    public static function activeFilters($calendar)
    {
        if ($calendar->filter) {
            return $calendar->filterData === 'UnassignedShifts'
                ? ['Unassigned shifts']
                : collect($calendar->filterData['soldier_id'])
                    ->map(function ($soldier_id) {
                        return User::where('userable_id', $soldier_id)->first()->displayName ?? null;
                    })
                    ->concat(
                        collect($calendar->filterData['type'])->map(function ($task_id) {
                            return Task::find($task_id)?->type;
                        })->unique()
                    )
                    ->filter()
                    ->toArray();
        }

        return [];
    }

    public static function getTitle(): string
    {
        return __('Shift');
    }

    public static function setData($record, $data)
    {
        $record->is_weekend ?? $data['is_weekend'] = $record->task->is_weekend === $data['is_weekend'] ? null : $data['is_weekend'];
        if ($record->parallel_weight === null) {
            $data['parallel_weight'] = $record->task->parallel_weight === $data['parallel_weight'] ? null : $data['parallel_weight'];
        }

        return $data;
    }

    public static function fillForm($record, $arguments)
    {
        return [
            ...$record->getAttributes(),
            'is_weekend' => $record->is_weekend === null ? $record->task->is_weekend : $record->is_weekend,
            'parallel_weight' => $record->parallel_weight === null ? $record->task->parallel_weight : $record->parallel_weight,
            'start_date' => $arguments['event']['start'] ?? $record->start_date,
            'end_date' => $arguments['event']['end'] ?? $record->end_date,
        ];
    }
}
