<?php

namespace App\Filament\Widgets;

use App\Enums\ConstraintType;
use App\Exports\AssignmentJustice;
use App\Exports\ShiftsExport;
use App\Models\Constraint;
use App\Models\Department;
use App\Models\Shift;
use App\Models\Task;
use App\Models\Team;
use App\Services\Algorithm;
use App\Services\Holidays;
use App\Services\Range;
use App\Services\RecurringEvents;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Saade\FilamentFullCalendar\Actions\CreateAction;
use Saade\FilamentFullCalendar\Actions\DeleteAction;
use Saade\FilamentFullCalendar\Actions\EditAction;
use Saade\FilamentFullCalendar\Actions\ViewAction;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    protected static bool $isLazy = false;

    public Model|string|null $model;

    public ?Collection $keys;

    public ?string $type;

    public bool $filter = false;

    public $filterData;

    public $lastFilterData = [];

    public $currentMonth;

    public $startDate;

    public $fetchInfo;

    public function fetchEvents(array $fetchInfo): array
    {
        $this->fetchInfo = $fetchInfo;
        $this->currentMonth = Carbon::parse($fetchInfo['start'])->addDays(7)->year.'-'.Carbon::parse($fetchInfo['start'])->addDays(7)->month;

        $events = $this->getEventsByRole();

        $eventDays = self::events($events)
            ->map(fn (Model $event) => [
                'id' => $event[$this->keys[0]],
                'title' => $event[$this->keys[1]],
                'start' => $event[$this->keys[2]],
                'end' => $event[$this->keys[3]],
                'backgroundColor' => $event[$this->keys[4]],
                'borderColor' => $event[$this->keys[4]],
                'textColor' => 'black',
                'display' => 'block',
            ])
            ->toArray();

        $specialDays = [];
        $startDate = Carbon::parse($fetchInfo['start']);
        $endDate = Carbon::parse($fetchInfo['end']);

        while ($startDate->lte($endDate)) {
            $holidays = $this->getHolidays($startDate->month, $startDate->day, $startDate->year);
            if ($holidays[0]) {
                $specialDays[] = [
                    'id' => null,
                    'title' => $holidays[1],
                    'start' => $startDate->toDateString(),
                    'end' => $startDate->toDateString(),
                    'backgroundColor' => 'rgba(var(--primary-600))',
                    'borderColor' => '#ffffff',
                    'textColor' => 'black',
                    'display' => 'background',
                ];
            }
            $startDate->addDay();
        }

        return array_merge($eventDays, $specialDays);
    }

    private function getHolidays($month, $day, $year): array
    {
        $holiday = new Holidays($month, $day, $year);

        return [$holiday->isHoliday, $holiday->holidayName];
    }

    public function getEventsByRole()
    {
        $current_user_id = auth()->user()->userable_id;
        $role = current(array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier']));
        $query = $this->model == Shift::class ?
        $this->model::with(['task', 'soldier'])
        : $this->model::with('soldier');
        $query = ($this->type === 'my_soldiers') ? match ($role) {
            'manager', 'shifts-assignment' => $query->where('soldier_id', '!=', $current_user_id)
                ->orWhereNull('soldier_id'),
            'department-commander' => $query->where('soldier_id', '!=', $current_user_id)
                ->where(function ($query) use ($current_user_id) {
                    $query->whereNull('soldier_id')
                        ->orWhereIn('soldier_id', Department::whereHas('commander', function ($query) use ($current_user_id) {
                            $query->where('id', $current_user_id);
                        })->first()?->teams->flatMap(fn (Team $team) => $team->members->pluck('id'))->toArray() ?? collect([]))
                        ->orWhereIn('soldier_id', Department::whereHas('commander', function ($query) use ($current_user_id) {
                            $query->where('id', $current_user_id);
                        })->first()?->teams->pluck('commander_id') ?? collect([]));
                })->orWhereNull('soldier_id'),
            'team-commander' => $query->where('soldier_id', '!=', $current_user_id)
                ->where(function ($query) use ($current_user_id) {
                    $query->whereNull('soldier_id')
                        ->orWhereIn('soldier_id', Team::whereHas('commander', function ($query) use ($current_user_id) {
                            $query->where('id', $current_user_id);
                        })->first()?->members->pluck('id') ?? collect([]));
                })
                ->orWhereNull('soldier_id'),
        } : $query->where('soldier_id', '=', $current_user_id);

        return $query->where('start_date', '>=', $this->fetchInfo['start'])
            ->where('end_date', '<=', $this->fetchInfo['end'])
            ->get();
    }

    protected function events($events): Collection
    {
        return $this->filter ? $this->model::filter($events, $this->filterData) : $events;
    }

    public function getFormSchema(): array
    {
        return $this->model::getSchema();
    }

    protected function headerActions(): array
    {
        $this->currentMonth ?? $this->currentMonth = Carbon::now()->year.'-'.Carbon::now()->month;
        if ($this->lastFilterData != $this->filterData) {
            $this->refreshRecords();
            $this->lastFilterData = $this->filterData;
        }
        $actions = [];
        if ($this->type === 'my') {
            if ($this->model === Constraint::class) {
                return [$this->createConstraintAction()];
            } else {
                return [$this->downloadAssignmentsAction()];
            }
        } else {
            if ($this->model !== Shift::class) {
                if (in_array('shifts-assignment', auth()->user()->getRoleNames()->toArray())) {
                    return [$this->createConstraintAction()];
                }
            } else {
                $hasActiveTasks = Task::withTrashed()->whereNull('deleted_at')->exists();

                $actions = [
                    ActionGroup::make([
                        $this->downloadAssignmentsAction(),
                        $this->downloadAssignmentJustice(),
                        Action::make('Create shifts')
                            ->action(fn () => $this->runEvents())
                            ->label(__('Create shifts'))
                            ->icon('heroicon-o-clipboard-document-check')
                            ->visible($hasActiveTasks),
                        Action::make('Shifts assignment')
                            ->action(fn () => $this->runAlgorithm())
                            ->label(__('Shifts assignment and Parallel shifts'))
                            ->icon('heroicon-o-play')
                            ->visible($hasActiveTasks),
                        Action::make('Reset assignment')
                            ->action(fn () => $this->resetShifts())
                            ->label(__('Reset assignment'))
                            ->icon('heroicon-o-arrow-path'),
                    ])
                        ->visible(in_array('shifts-assignment', auth()->user()->getRoleNames()->toArray())
                            || in_array('manager', auth()->user()->getRoleNames()->toArray())),
                ];
            }
            if ($this->filter) {
                return array_merge(
                    $actions ?? [],
                    self::activeFilters(),
                    [
                        self::resetFilters(),
                        $this->model::getFilters($this)
                            ->closeModalByClickingAway(false),
                    ]
                );
            }

            return array_merge(
                $actions ?? [],
                [
                    $this->model::getFilters($this)
                        ->closeModalByClickingAway(false),
                ]
            );
        }
    }

    protected function createConstraintAction()
    {
        $today = now()->startOfDay();

        return CreateAction::make()
            ->action(function (array $data) {
                if (
                    ($data['constraint_type'] == ConstraintType::VACATION->value ||
                        $data['constraint_type'] == ConstraintType::MEDICAL->value)
                    && auth()->user()->getRoleNames()->count() === 1
                ) {
                    Constraint::requestConstraint($data);
                } else {
                    Constraint::create([
                        'constraint_type' => $data['constraint_type'],
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                    ]);
                }
            })
            ->mountUsing(
                fn (Form $form, array $arguments) => $form->fill([
                    'start_date' => $arguments['start'] ?? null,
                    'end_date' => $arguments['end'] ?? null,
                ])
            )
            ->label($this->model::getTitle().' '.__('New'))
            ->modalHeading(__('Create').' '.$this->model::getTitle())
            ->disabled(function (array $arguments) use ($today) {
                $startDate = Carbon::parse($arguments['start'] ?? null);

                return $startDate->isBefore($today);
            })
            ->hidden($this->model === Shift::class && $this->type === 'my' && ! array_intersect(auth()->user()->getRoleNames()->toArray(), ['manager', 'shifts-assignment', 'department-commander', 'team-commander']));
    }

    protected function downloadAssignmentsAction()
    {
        return Action::make('Download')
            ->label(__('Download to excel'))
            ->icon('heroicon-o-arrow-down-tray')
            ->action(fn () => Excel::download(new ShiftsExport($this->currentMonth), __('File name', [
                'name' => auth()->user()->displayName,
                'month' => $this->currentMonth,
            ]).'.xlsx'));
    }

    protected function downloadAssignmentJustice()
    {
        return Action::make('Download assignment justice')
            ->label(__('Download the assignment justice'))
            ->icon('heroicon-o-arrow-down-tray')
            ->action(fn () => Excel::download(new AssignmentJustice($this->currentMonth), __('File name', [
                'name' => auth()->user()->displayName,
                'month' => $this->currentMonth,
            ]).'.xlsx'));
    }

    protected function resetShifts()
    {
        $this->startDate = (Carbon::now()->format('m') == Carbon::parse($this->currentMonth)->format('m'))
            ? Carbon::now()->addDay()->format('Y-m-d')
            : Carbon::parse($this->currentMonth)->startOfMonth()->format('Y-m-d');
        Shift::whereNotNull('soldier_id')
            ->whereBetween('start_date', [$this->startDate, (Carbon::parse($this->currentMonth)->endOfMonth()->addDay())->format('Y-m-d')])
            ->update(['soldier_id' => null]);
        $this->refreshRecords();
    }

    protected function runEvents()
    {
        $recurringEvents = new RecurringEvents($this->currentMonth);
        $recurringEvents->recurringTask();
        $this->refreshRecords();
    }

    protected function runAlgorithm()
    {
        $algorithm = new Algorithm($this->currentMonth);
        $algorithm->run();
        $this->refreshRecords();
    }

    protected function resetFilters()
    {
        return Action::make('resetFilters')
            ->label(__('Reset filters'))
            ->icon('heroicon-o-arrow-path')
            ->iconButton()
            ->action(function () {
                $this->filter = false;
                $this->filterData = [];
                $this->refreshRecords();
            });
    }

    protected function activeFilters()
    {
        $activeFilters = $this->model::activeFilters($this);

        $tags = collect($activeFilters)->map(fn ($tag) => Action::make($tag)
            ->label(__($tag))
            ->disabled()
            ->badge());

        return $tags->toArray();
    }

    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'locale' => app()->getLocale(),
        ]);
    }

    protected function modalActions(): array
    {
        $basicActions = $this->getBasicActions();
        $changeAction = $this->getChangeActions();

        if ($this->model == Shift::class) {
            return array_merge($changeAction, $basicActions);
        }

        return $basicActions;
    }

    protected function getBasicActions()
    {
        return [
            EditAction::make()
                ->fillForm(fn (Model $record, array $arguments) => method_exists($this->model, 'fillForm')
                    ? (new $this->model)->fillForm($record, $arguments)
                    : [
                        ...$record->getAttributes(),
                        'start_date' => $arguments['event']['start'] ?? $record->start_date,
                        'end_date' => $arguments['event']['end'] ?? $record->end_date,
                    ])
                ->visible(function (Model $record, $arguments) {
                    if ($record->start_date < now()) {
                        return false;
                    }
                    if (! empty($arguments['event']) && $arguments['event']['start'] < now()) {
                        $this->refreshRecords();

                        return false;
                    }

                    return true;
                })
                ->modalCloseButton(false)
                ->modalCancelAction(false)
                ->modalSubmitAction(false)
                ->closeModalByClickingAway(false)
                ->extraModalFooterActions(function (Action $action, array $arguments): array {
                    $canSave = empty($arguments) ? true : (
                        $this->model === Constraint::class ? (
                            isset($this->mountedActionsData[0]['constraint_type']) ? (
                                $arguments['type'] === 'drop' ?
                                array_key_exists(
                                    $this->mountedActionsData[0]['constraint_type'],
                                    $this->model::getAvailableOptions($arguments['event']['start'], $arguments['event']['end'], false)
                                ) :
                                array_key_exists(
                                    $this->mountedActionsData[0]['constraint_type'],
                                    $this->model::getAvailableOptions($arguments['event']['start'], $arguments['event']['end'])
                                )
                            ) : false
                        ) : true
                    );
                    if (! empty($arguments) && $this->model === Shift::class) {
                        $oldDate = date('l', strtotime($this->mountedActionsArguments[0]['oldEvent']['start']));
                        $newDate = date('l', strtotime($this->mountedActionsData[0]['start_date']));
                        $startOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
                        if ((in_array($oldDate, $startOfWeek) !== in_array($newDate, $startOfWeek))) {
                            Notification::make()
                                ->info()
                                ->title(__('Update dragged shift details!'))
                                ->body(__('Pay attention to update the shift details according to the changes you made .'))
                                ->color('info')
                                ->persistent()
                                ->send();
                        }
                    }

                    return [
                        $action->makeExtraModalAction(__('Save'), ['save' => true])
                            ->color('primary')
                            ->disabled(! $canSave),
                        $action->makeExtraModalAction(__('Cancel'), ['cancel' => true])
                            ->color('primary'),
                    ];
                })
                ->modalHeading(__('Edit').' '.$this->model::getTitle())
                ->outlined()
                ->action(function (array $data, array $arguments, Model $record): void {
                    $data = method_exists($this->model, 'setData') ? $data = $this->model::setData($record, $data) : $data;
                    if ($arguments['cancel'] ?? false) {
                        $this->refreshRecords();
                    }
                    if ($arguments['save'] ?? false) {
                        if ($this->model == Constraint::class) {
                            if (
                                ($data['constraint_type'] === ConstraintType::VACATION->value ||
                                    $data['constraint_type'] === ConstraintType::MEDICAL->value) &&
                                auth()->user()->getRoleNames()->count() === 1
                            ) {
                                $dataToEdit = [
                                    'oldConstraint' => $record,
                                    'newConstraint' => $data,
                                ];

                                Constraint::requestEditConstraint($dataToEdit);

                                return;
                            }
                        }
                        $columns = Schema::getColumnListing(strtolower(class_basename($this->model)).'s');
                        $filteredData = array_intersect_key($data, array_flip($columns));
                        $record = $this->model::find($record['id']);

                        if ($record) {
                            collect($filteredData)->map(function ($value, $key) use ($record) {
                                $record->{$key} = $value;
                            });
                            $record->save();
                        }
                        method_exists($this->model, 'afterSave') && $this->model::afterSave($data, $record);
                    }
                }),
            DeleteAction::make()
                ->outlined()
                ->label(__('Delete')),
        ];
    }

    protected function getChangeActions()
    {
        return [
            Shift::exchangeAction()
                ->visible(fn (): bool => $this->displayButton())
                ->outlined()
                ->cancelParentActions(),
            Shift::changeAction()
                ->visible(fn (): bool => $this->displayButton())
                ->outlined()
                ->cancelParentActions(),
        ];
    }

    protected function displayButton(): bool
    {
        $record = is_array($this->mountedActionsData) && ! empty($this->mountedActionsData)
            ? (object) $this->mountedActionsData[0]
            : (object) $this->mountedActionsData;
        $range = new Range($record->start_date, $record->end_date);

        return $record->soldier_id !== null && ! $range->isPass();
    }

    protected function viewAction(): Action
    {
        return ViewAction::make()
            ->fillForm(fn (Model $record, array $arguments) => method_exists($this->model, 'fillForm')
                ? (new $this->model)->fillForm($record, $arguments)
                : [
                    ...$record->getAttributes(),
                    'start_date' => $arguments['event']['start'] ?? $record->start_date,
                    'end_date' => $arguments['event']['end'] ?? $record->end_date,
                ])
            ->modalFooterActions(
                function (ViewAction $action, FullCalendarWidget $livewire) {
                    if (
                        ($this->model == Shift::class && auth()->user()->getRoleNames()->count() === 1) ||
                        ($this->model == Constraint::class && $this->type == 'my_soldiers' && ! auth()->user()->getRoleNames()->contains('shifts-assignment') && ! auth()->user()->getRoleNames()->contains('manager'))
                    ) {
                        return $this->model === Shift::class ?
                            [...$this->getChangeActions()] :
                            [$action->getModalCancelAction()];
                    }

                    return [
                        ...$livewire->getCachedModalActions(),
                    ];
                }
            )
            ->modalHeading(__('View ').$this->model::getTitle());
    }

    public function eventDidMount(): string
    {
        return <<<'JS'
        function({ event, timeText, isStart, isEnd, isMirror, isPast, isFuture, isToday, el, view }){
            el.setAttribute("x-tooltip", "tooltip");
            el.setAttribute("x-data", "{ tooltip: `"+event.title+"` }");
        }
    JS;
    }

    public function onEventDrop(array $event, array $oldEvent, array $relatedEvents, array $delta, ?array $oldResource, ?array $newResource): bool
    {
        if (
            ($this->model == Shift::class && $this->type == 'my' && auth()->user()->getRoleNames()->count() === 1) ||
            ($this->model == Constraint::class && $this->type == 'my_soldiers' && ! auth()->user()->getRoleNames()->contains('shifts-assignment') && ! auth()->user()->getRoleNames()->contains('manager'))
        ) {
            $this->refreshRecords();
        } else {
            if ($this->getModel()) {
                $this->record = $this->resolveRecord($event['id']);
            }
            $this->mountAction('edit', [
                'type' => 'drop',
                'event' => $event,
                'oldEvent' => $oldEvent,
                'relatedEvents' => $relatedEvents,
                'delta' => $delta,
                'oldResource' => $oldResource,
                'newResource' => $newResource,
            ]);
        }

        return false;
    }
}
