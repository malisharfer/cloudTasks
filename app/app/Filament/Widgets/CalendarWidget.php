<?php

namespace App\Filament\Widgets;

use App\Exports\ShiftsExport;
use App\Models\Constraint;
use App\Models\Shift;
use App\Services\Holidays;
use App\Services\Range;
use Carbon\Carbon;
use Filament\Actions\Action;
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

        $events = self::events();

        $eventDays = $events
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

    protected function events(): Collection
    {
        return $this->type == 'soldiers'
            ? ($this->filter ? $this->model::filter($this->fetchInfo, $this->filterData) : collect())
            : $this->getMyEvents();
    }

    public function getMyEvents()
    {
        $currentUserId = auth()->user()->userable_id;
        $query = $this->model == Shift::class ?
            $this->model::with(['task', 'soldier'])
            : $this->model::with('soldier');
        $query = $query->where('soldier_id', '=', $currentUserId);

        return $query->where('start_date', '>=', Carbon::create($this->fetchInfo['start'])->setTimezone('Asia/Jerusalem'))
            ->where('end_date', '<=', Carbon::create($this->fetchInfo['end'])->setTimezone('Asia/Jerusalem'))
            ->get();
    }

    private function getHolidays($month, $day, $year): array
    {
        $holiday = new Holidays($month, $day, $year);

        return [$holiday->isHoliday, $holiday->holidayName];
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
        if ($this->type === 'my') {
            if ($this->model === Constraint::class) {
                return [$this->createConstraintAction()];
            } else {
                return [$this->downloadAssignmentsAction()];
            }
        } else {
            $actions = collect();

            if ($this->filter) {
                $actions->push(...self::activeFilters());
                $actions->push(self::resetFilters());
            }
            $actions->push($this->model::getFilters($this)->closeModalByClickingAway(false));
            if ($this->model == Constraint::class) {
                $actions->push($this->createConstraintAction());
            }

            return $actions->toArray();
        }
    }

    protected function createConstraintAction()
    {
        $today = now()->startOfDay();

        return CreateAction::make()
            ->action(function (array $data) {
                if (auth()->user()->getRoleNames()->count() === 1) {
                    Constraint::requestConstraint($data);
                } else {
                    Constraint::createConstraint($data);
                }
            })
            ->mountUsing(function (Form $form, array $arguments) {
                $start = $arguments['start'] ?? null;
                $end = $arguments['end'] ?? null;

                $form->fill([
                    'start_date' => $start,
                    'end_date' => $end ?? ($start ? Carbon::parse($start)->addHour() : null),
                ]);
            })
            ->label($this->model::getTitle().' '.__('New'))
            ->modalHeading(__('Create').' '.$this->model::getTitle())
            ->disabled(function (array $arguments) use ($today) {
                $startDate = Carbon::parse($arguments['start'] ?? null);

                return $startDate->isBefore($today);
            });
    }

    protected function downloadAssignmentsAction()
    {
        return Action::make('Download')
            ->label(__('Download to assignment'))
            ->icon('heroicon-o-arrow-down-tray')
            ->action(fn () => Excel::download(new ShiftsExport($this->currentMonth), __('File name', [
                'name' => auth()->user()->displayName,
                'month' => $this->currentMonth,
            ]).'.xlsx'));
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
                    // if ($record->start_date < now()) {
                    //     if (! empty($arguments) && $arguments['type'] == 'drop') {
                    //         $this->refreshRecords();
                    //     }

                    //     return false;
                    // }
                    // if (! empty($arguments['event']) && $arguments['event']['start'] < now()) {
                    //     $this->refreshRecords();

                    //     return false;
                    // }

                    return true;
                })
                ->modalCloseButton(false)
                ->modalCancelAction(false)
                ->modalSubmitAction(false)
                ->closeModalByClickingAway(false)
                ->extraModalFooterActions(function (Action $action, array $arguments): array {
                    $canSave = (! empty($arguments) && $arguments == ['save' => true])
                        ? (
                            $this->model === Constraint::class
                            ? (
                                isset($this->mountedActionsData[0]['constraint_type'])
                                ? array_key_exists(
                                    $this->mountedActionsData[0]['constraint_type'],
                                    $this->model::getAvailableOptions(
                                        $arguments['event']['start'] ?? null,
                                        $arguments['event']['end'] ?? null,
                                        ($arguments['type'] ?? null) != 'drop'
                                    )
                                )
                                : false
                            )
                            : true
                        )
                        : true;
                    // if (! empty($arguments) && $this->model === Shift::class) {
                    //     $oldDate = date('l', strtotime($this->mountedActionsArguments[0]['oldEvent']['start']));
                    //     $newDate = date('l', strtotime($this->mountedActionsData[0]['start_date']));
                    //     $startOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
                    //     if ((in_array($oldDate, $startOfWeek) !== in_array($newDate, $startOfWeek))) {
                    //         Notification::make()
                    //             ->info()
                    //             ->title(__('Update dragged shift details!'))
                    //             ->body(__('Pay attention to update the shift details according to the changes you made .'))
                    //             ->color('info')
                    //             ->persistent()
                    //             ->send();
                    //     }
                    // }

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
                            if (auth()->user()->getRoleNames()->count() === 1) {
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
                            if ($this->model == Shift::class) {
                                $record->manually_assigned = true;
                            }
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

        return $record->soldier_id !== null;
        // return $record->soldier_id !== null && ! $range->isPass();
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
                    if ($this->model == Shift::class && auth()->user()->getRoleNames()->count() === 1) {
                        return [...$this->getChangeActions()];
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
        if ($this->model == Shift::class && $this->type == 'my' && auth()->user()->getRoleNames()->count() === 1) {
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
