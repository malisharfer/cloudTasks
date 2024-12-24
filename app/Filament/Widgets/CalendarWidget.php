<?php

namespace App\Filament\Widgets;

use App\Exports\ShiftsExport;
use App\Models\Constraint;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\Task;
use App\Services\Algorithm;
use App\Services\Holidays;
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
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
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

    public $activeFilters = [];

    public $currentMonth;

    public $startDate;

    public $lastMonth;

    public function fetchEvents(array $fetchInfo): array
    {
        $this->currentMonth = Carbon::parse($fetchInfo['start'])->addDays(7)->year.'-'.Carbon::parse($fetchInfo['start'])->addDays(7)->month;

        $this->headerActions();

        $events = $this->getEventsByRole();

        $events->where('start_date', '>=', $fetchInfo['start'])
            ->where('end_date', '<=', $fetchInfo['end']);
        $eventDays = self::events($events)
            ->map(function (Model $event) {
                return [
                    'id' => $event[$this->keys[0]],
                    'title' => $event[$this->keys[1]],
                    'start' => $event[$this->keys[2]],
                    'end' => $event[$this->keys[3]],
                    'backgroundColor' => $event[$this->keys[4]],
                    'borderColor' => $event[$this->keys[4]],
                    'textColor' => 'black',
                    'display' => 'block',
                ];
            })
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
        $role = current(array: array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier']));

        return ($this->type === 'my_soldiers') ? match ($role) {
            'manager' => $this->model::where('soldier_id', '!=', $current_user_id)
                ->orWhereNull('soldier_id')
                ->get(),
            'department-commander' => $this->model::where('soldier_id', '!=', $current_user_id)
                ->orWhereNull('soldier_id')
                ->get()
                ->filter(function (Model $object) use ($current_user_id) {
                    $soldier = Soldier::where('id', '=', $object->soldier_id)->first();

                    return ! $soldier || $soldier?->team?->department?->commander_id == $current_user_id;
                }),
            'team-commander' => $this->model::where('soldier_id', '!=', $current_user_id)
                ->orWhereNull('soldier_id')
                ->get()
                ->filter(function (Model $object) use ($current_user_id) {
                    $soldier = Soldier::where('id', '=', $object->soldier_id)->first();

                    return ! $soldier || $soldier?->team?->commander_id == $current_user_id;
                }),
        } : $this->model::where('soldier_id', '=', $current_user_id)->get();
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
        if ($this->lastFilterData != $this->filterData || $this->lastMonth !== $this->currentMonth) {
            $this->refreshRecords();
            $this->lastFilterData = $this->filterData;
            $this->lastMonth = $this->currentMonth;
        }
        $today = now()->startOfDay();
        $actions = [];
        if ($this->type === 'my') {
            if ($this->model === Constraint::class) {
                return [
                    CreateAction::make()
                        ->mountUsing(function (Form $form, array $arguments) {
                            $form->fill([
                                'start_date' => $arguments['start'] ?? null,
                                'end_date' => $arguments['end'] ?? null,
                            ]);
                        })
                        ->label($this->model::getTitle().' '.__('New'))
                        ->modalHeading(__('Create').' '.$this->model::getTitle())
                        ->disabled(function (array $arguments) use ($today) {
                            $startDate = Carbon::parse($arguments['start'] ?? null);

                            return $startDate->isBefore($today);
                        })->extraAttributes(['class' => 'fullcalendar'])
                        ->hidden($this->model === Shift::class && $this->type === 'my' && ! array_intersect(auth()->user()->getRoleNames()->toArray(), ['manager', 'department-commander', 'team-commander'])),
                ];
            }
        } else {
            if ($this->model !== Shift::class) {
                FilamentFullCalendarPlugin::get()->editable(false);
                FilamentFullCalendarPlugin::get()->selectable(false);
            } else {
                if (Task::exists()) {
                    $actions = [
                        ActionGroup::make([
                            Action::make('Download')
                                ->label(__('Download to excel'))
                                ->icon('heroicon-o-arrow-down-tray')
                                ->action(function () {
                                    return Excel::download(new ShiftsExport($this->getEventsByRole(), $this->currentMonth), __('File name', [
                                        'name' => auth()->user()->displayName,
                                        'month' => $this->currentMonth]).'.xlsx');
                                }),
                            Action::make('Create shifts')
                                ->action(fn () => $this->runEvents())
                                ->label(__('Create shifts'))
                                ->icon('heroicon-o-clipboard-document-check')
                                ->visible(current(array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier'])) && Carbon::today()->startOfMonth() <= Carbon::parse($this->currentMonth))
                                ->extraAttributes(['class' => 'fullcalendar']),
                            Action::make('Shifts assignment')
                                ->action(fn () => $this->runAlgorithm())
                                ->label(__('Shifts assignment'))
                                ->icon('heroicon-o-play')
                                ->visible(current(array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier'])) && Carbon::today()->startOfMonth() <= Carbon::parse($this->currentMonth))
                                ->extraAttributes(['class' => 'fullcalendar']),
                            Action::make('Reset assignment')
                                ->action(fn () => $this->resetShifts())
                                ->label(__('Reset assignment'))
                                ->icon('heroicon-o-arrow-path')
                                ->visible(current(array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier'])) && Carbon::today()->startOfMonth() <= Carbon::parse($this->currentMonth))
                                ->extraAttributes(['class' => 'fullcalendar']),
                        ]),
                    ];
                }
            }
            if ($this->filter) {
                return array_merge(self::activeFilters(), [
                    self::resetFilters(),
                    $this->model::getFilters($this)
                        ->closeModalByClickingAway(false),
                ]);
            }

            return array_merge(
                $actions ?? [],
                [
                    $this->model::getFilters($this)
                        ->closeModalByClickingAway(false),
                ]
            );
        }

        return [];
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
            })
            ->extraAttributes(['class' => 'fullcalendar']);
    }

    protected function activeFilters()
    {
        $activeFilters = $this->model::activeFilters($this);

        $tags = collect($activeFilters)->map(function ($tag) {
            return Action::make($tag)
                ->label(__($tag))
                ->disabled()
                ->badge()
                ->extraAttributes(['class' => 'fullcalendar']);
        });

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
        $basicActions = [
            EditAction::make()
                ->fillForm(function (Model $record, array $arguments): array {
                    return method_exists($this->model, 'fillForm')
                        ? (new $this->model)->fillForm($record, $arguments)
                        : [
                            ...$record->getAttributes(),
                            'start_date' => $arguments['event']['start'] ?? $record->start_date,
                            'end_date' => $arguments['event']['end'] ?? $record->end_date,
                        ];
                })
                ->hidden($this->model === Shift::class && $this->type === 'my' && ! array_intersect(auth()->user()->getRoleNames()->toArray(), ['manager', 'department-commander', 'team-commander']))
                ->modalCloseButton(false)
                ->modalCancelAction(false)
                ->modalSubmitAction(false)
                ->closeModalByClickingAway(false)
                ->extraModalFooterActions(function (Action $action, array $arguments): array {
                    $canSave = empty($arguments) ? true : (
                        ($this->model === Constraint::class) ? (
                            isset($this->mountedActionsData[0]['constraint_type']) &&
                            array_key_exists(
                                $this->mountedActionsData[0]['constraint_type'],
                                $this->model::getAvailableOptions($arguments['event']['start'], $arguments['event']['end'])
                            )
                        ) : true
                    );
                    $oldDate = date('l', strtotime($this->mountedActionsArguments[0]['oldEvent']['start']));
                    $newDate = date('l', strtotime($this->mountedActionsData[0]['start_date']));
                    $startOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
                    if (
                        $this->model === Shift::class &&
                        (in_array($oldDate, $startOfWeek) !== in_array($newDate, $startOfWeek))
                    ) {
                        Notification::make()
                            ->info()
                            ->title(__('Update dragged shift details!'))
                            ->body(__('Pay attention to update the shift details according to the changes you made .'))
                            ->color('info')
                            ->persistent()
                            ->send();
                    }

                    return [
                        $action->makeExtraModalAction(__('Save'), arguments: ['save' => true])
                            ->color('primary')
                            ->disabled(! $canSave),
                        $action->makeExtraModalAction(__('Cancel'), arguments: ['cancel' => true])
                            ->color('primary'),
                    ];
                })
                ->modalHeading(__('Edit').' '.$this->model::getTitle())
                ->action(function (array $data, array $arguments, Model $record): void {
                    $data = method_exists($this->model, 'setData') ? $data = $this->model::setData($record, $data) : $data;
                    if ($arguments['cancel'] ?? false) {
                        $this->refreshRecords();
                    }
                    if ($arguments['save'] ?? false) {
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
                ->label(__('Delete'))
                ->hidden($this->model === Shift::class && $this->type === 'my' && ! array_intersect(auth()->user()->getRoleNames()->toArray(), ['manager', 'department-commander', 'team-commander'])),

        ];
        if (($this->type === 'my' && $this->model === Constraint::class) || ($this->type === 'my_soldiers' && $this->model === Shift::class)) {
            if (method_exists($this->model, 'getAction')) {
                $action = $this->model::getAction($this)
                    ->visible(function (): bool {
                        $record = is_array($this->mountedActionsData) && ! empty($this->mountedActionsData)
                            ? (object) $this->mountedActionsData[0]
                            : (object) $this->mountedActionsData;

                        return $this->model === 'App\Models\Shift' && $record->soldier_id !== null;
                    })
                    ->closeModalByClickingAway(false)
                    ->cancelParentActions();

                return array_merge($basicActions, [$action]);
            }

            return $basicActions;
        }
        FilamentFullCalendarPlugin::get()->editable(false);
        FilamentFullCalendarPlugin::get()->selectable(false);

        return [];
    }

    protected function viewAction(): Action
    {
        return ViewAction::make()
            ->fillForm(function (Model $record, array $arguments): array {
                return method_exists($this->model, 'fillForm')
                    ? (new $this->model)->fillForm($record, $arguments)
                    : [
                        ...$record->getAttributes(),
                        'start_date' => $arguments['event']['start'] ?? $record->start_date,
                        'end_date' => $arguments['event']['end'] ?? $record->end_date,
                    ];
            })
            ->modalHeading(__('View').$this->model::getTitle());
    }
}
