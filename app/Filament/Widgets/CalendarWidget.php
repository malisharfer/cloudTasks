<?php

namespace App\Filament\Widgets;

use App\Models\Constraint;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\Task;
use App\Services\RecurringEvents;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Saade\FilamentFullCalendar\Actions\CreateAction;
use Saade\FilamentFullCalendar\Actions\DeleteAction;
use Saade\FilamentFullCalendar\Actions\EditAction;
use Saade\FilamentFullCalendar\Actions\viewAction;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    protected static bool $isLazy = false;

    public Model|string|null $model;

    public ?Collection $keys;

    public ?string $type;

    public bool $filter = false;

    public $filterData = [];

    public $lastFilterData = [];

    public $activeFilters = [];

    public $currentMonth;

    public function fetchEvents(array $fetchInfo): array
    {

        if (Carbon::parse($fetchInfo['start'])->day != 1) {
            $this->currentMonth = Carbon::parse($fetchInfo['start'])->addMonth()->year.'-'.Carbon::parse($fetchInfo['start'])->addMonth()->month.'-'.'01';
        } else {
            $this->currentMonth = Carbon::parse($fetchInfo['start'])->year.'-'.Carbon::parse($fetchInfo['start'])->month.'-'.'01';
        }

        $events = $this->getEventsByRole();

        $events->where('start_date', '>=', $fetchInfo['start'])
            ->where('end_date', '<=', $fetchInfo['end']);

        return self::events($events)

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
                        }),
                ];
            }
        } elseif ($this->model !== Shift::class) {
            FilamentFullCalendarPlugin::get()->editable(false);
            FilamentFullCalendarPlugin::get()->selectable(false);
        } else {

            if (Task::exists()) {
                $actions = [
                    Action::make('Shifts assignment')
                        ->action(fn (): RecurringEvents => self::runEvents())
                        ->label(__('Shifts assignment'))
                        ->icon('heroicon-o-arrow-path')
                        ->visible(current(array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier']))),

                    Action::make('Run Algorithm')
                        ->action(fn (): RecurringEvents => self::runEvents())
                        ->label(__('Run Algorithm'))
                        ->icon('heroicon-o-play')
                        ->visible(current(array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier']))),
                ];
            }
        }

        if ($this->lastFilterData != $this->filterData) {
            $this->refreshRecords();
            $this->lastFilterData = $this->filterData;
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

    protected function runEvents()
    {
        $recurringEvents = new RecurringEvents($this->currentMonth);
        $recurringEvents->recurringTask();
        $this->refreshRecords();

        return $recurringEvents;
    }

    protected function resetFilters()
    {
        return Action::make('resetFilters')
            ->label(__('Reset filters'))
            ->action(function () {
                $this->filter = false;
                $this->filterData = [];
                $this->refreshRecords();
            });
    }

    protected function activeFilters()
    {
        $activeFilters = $this->model::activeFilters($this);

        $tags = collect($activeFilters)->map(function ($tag) {
            return Action::make($tag)
                ->label($tag)
                ->disabled()
                ->badge();
        });

        return $tags->toArray();

    }

    protected function run() {}

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
                    : [...$record->getAttributes(),
                        'start_date' => $arguments['event']['start'] ?? $record->start_date,
                        'end_date' => $arguments['event']['end'] ?? $record->end_date];
                })
                ->modalCloseButton(false)
                ->modalCancelAction(false)
                ->modalSubmitAction(false)
                ->closeModalByClickingAway(false)
                ->extraModalFooterActions(fn (Action $action): array => [
                    $action->makeExtraModalAction(__('Save'), arguments: ['save' => true])->color('primary'),
                    $action->makeExtraModalAction(__('Cancel'), arguments: ['cancel' => true])->color('primary'),
                ])
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
                ->label(__('Delete')),
        ];
        if ($this->type === 'my' || ($this->type === 'my_soldiers' && $this->model === Shift::class)) {
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
                : [...$record->getAttributes(),
                    'start_date' => $arguments['event']['start'] ?? $record->start_date,
                    'end_date' => $arguments['event']['end'] ?? $record->end_date];
            });
            // ->modalHeading(__('View').$this->model::getTitle());
    }
}