<?php

namespace App\Filament\Widgets;

use App\Models\Constraint;
use App\Models\Department;
use App\Models\Shift;
use App\Models\Team;
use Filament\Actions\Action;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
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

    public function fetchEvents(array $fetchInfo): array
    {
        $query = $this->model::query();
        $eventsByRole = $this->getEventsByRole();
        $query = $eventsByRole($query);

        $query->where('start_date', '>=', $fetchInfo['start'])
            ->where('end_date', '<=', $fetchInfo['end']);

        return $query->get()
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

    protected function getEventsByRole()
    {
        $current_user_id = auth()->user()->userable_id;

        $role = current(array: array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier']));

        $tableName = strtolower(class_basename($this->model)).'s';

        return ($this->type === 'my_soldiers') ? match ($role) {
            'manager' => fn (Builder $query) => $query
                ->where('soldier_id', '!=', $current_user_id)
                ->orWhereNull('soldier_id'),
            'department-commander' => fn (Builder $query) => $query
                ->leftJoin('soldiers', 'soldier_id', '=', 'soldiers.id')
                ->leftJoin('teams', 'soldiers.team_id', '=', 'teams.id')
                ->where(function ($q) use ($current_user_id) {
                    $q->where('teams.department_id', '=', value: Department::where('commander_id', $current_user_id)->value('id'))
                        ->orWhere(function ($q) {
                            $q->whereNull('soldiers.team_id')
                                ->whereNull('soldier_id');
                        });
                })
                ->where(function ($q) use ($current_user_id) {
                    $q->where('soldier_id', '!=', $current_user_id)
                        ->orWhereNull('soldier_id');
                })
                ->select("{$tableName}.*"),
            'team-commander' => fn (Builder $query) => $query
                ->leftJoin('soldiers', 'soldiers.id', '=', 'soldier_id')
                ->where(function ($q) use ($current_user_id) {
                    $q->where('soldiers.team_id', '=', Team::where('commander_id', $current_user_id)->value('id'))
                        ->orWhere(function ($q) {
                            $q->whereNull('soldiers.team_id')
                                ->whereNull('soldier_id');
                        });
                })
                ->where(function ($q) use ($current_user_id) {
                    $q->where('soldier_id', '!=', $current_user_id)
                        ->orWhereNull('soldier_id');
                })
                ->select("{$tableName}.*"),
        } : fn (Builder $query) => $query->where('soldier_id', $current_user_id);
    }

    public function getFormSchema(): array
    {
        return $this->model::getSchema();
    }

    protected function headerActions(): array
    {
        if ($this->type === 'my') {
            if ($this->model === Constraint::class) {
                return [
                    CreateAction::make()
                        ->mountUsing(
                            function (Form $form, array $arguments) {
                                $form->fill([
                                    'start_date' => $arguments['start'] ?? null,
                                    'end_date' => $arguments['end'] ?? null,
                                ]);
                            }
                        )->label(label: $this->model::getTitle().' '.__('New'))->modalHeading(__('Create').' '.$this->model::getTitle()),
                ];
            }
        } elseif ($this->model !== Shift::class) {
            FilamentFullCalendarPlugin::get()->editable(false);
            FilamentFullCalendarPlugin::get()->selectable(false);
        }

        return [];
    }

    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'locale' => app()->getLocale(),
        ]);
    }

    protected function modalActions(): array
    {
        if ($this->type === 'my' || ($this->type === 'my_soldiers' && $this->model === Shift::class)) {
            return [
                EditAction::make()
                    ->fillForm(fn (Model $record, array $arguments): array => [
                        ...$record->getAttributes(),
                        'start_date' => $arguments['event']['start'] ?? $record->start_date,
                        'end_date' => $arguments['event']['end'] ?? $record->end_date,
                    ])
                    ->modalCloseButton(false)
                    ->modalCancelAction(false)
                    ->modalSubmitAction(false)
                    ->closeModalByClickingAway(false)
                    ->extraModalFooterActions(fn (Action $action): array => [
                        $action->makeExtraModalAction('save', arguments: ['save' => true])->color('primary'),
                        $action->makeExtraModalAction('cancel', arguments: ['cancel' => true])->color('primary'),
                    ])
                    ->modalHeading(__('Edit').' '.$this->model::getTitle())
                    ->action(function (array $data, array $arguments, Model $record): void {
                        if ($arguments['cancel'] ?? false) {
                            $this->refreshRecords();
                        }
                        if ($arguments['save'] ?? false) {
                            $this->model::where('id', operator: $record['id'])->update([...$data]);
                        }
                    }),
                DeleteAction::make(),
            ];
        } else {
            FilamentFullCalendarPlugin::get()->editable(false);
            FilamentFullCalendarPlugin::get()->selectable(false);

            return [];
        }
    }

    protected function viewAction(): Action
    {
        return ViewAction::make()->modalHeading(__('View').$this->model::getTitle());
    }
}
