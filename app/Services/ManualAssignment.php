<?php

namespace App\Services;

use App\Enums\Availability;
use App\Enums\ConstraintType;
use App\Enums\Priority;
use App\Models\Constraint;
use App\Models\Department;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class ManualAssignment
{
    public $shift;

    public $soldier_type;

    public $soldiers;

    public function __construct(Shift $shift, string $soldier_type)
    {
        $this->shift = new \App\Services\Shift($shift->id, $shift->task->name, $shift->start_date, $shift->end_date, $shift->task->parallel_weight);
        $this->soldier_type = $soldier_type;
        $this->soldiers = [];
    }

    public function getSoldiers()
    {
        $this->initSoldiersData();

        return $this->getAvailableSoldiers();
    }

    protected function initSoldiersData()
    {
        $this->soldiers = Cache::remember('users', 30 * 60, function () {
            return User::all();
        });
        match ($this->soldier_type) {
            'reserves' => $this->filterReserves(),
            'my_soldiers' => $this->filterMySoldiers(),
            'department' => $this->filterDepartment(),
            'all' => $this->filterAll()
        };
        $this->mapSoldiersConstraints();
    }

    protected function filterReserves()
    {
        $this->soldiers = $this->soldiers
            ->filter(function ($user) {
                $soldier = Soldier::where('id', $user->userable_id)->first();

                return $soldier->is_reservist;
            });
    }

    protected function filterMySoldiers()
    {
        $this->soldiers = $this->soldiers
            ->filter(function ($user) {
                $current_user_id = auth()->user()->userable_id;
                $role = current(array: array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier']));
                $soldier = Soldier::where('id', $user->userable_id)->first();
                match ($role) {
                    'department-commander' => (function () use ($soldier) {
                        $department_id = Department::where('commander_id', $soldier->id)->value('id');
                        $teams = Team::where('department_id', $department_id)->get('id');

                        return $teams->contains($soldier->team_id);
                    })(),
                    'team-commander' => (function () use ($soldier, $current_user_id) {
                        $team = Team::where('commander_id', $soldier->id)->get('id');

                        return $soldier->team_id === $team && $soldier->id != $current_user_id;
                    })()
                };
            });
    }

    protected function filterDepartment()
    {
        $department_name = Shift::find($this->shift->id)->task->department_name;
        $this->soldiers = $this->soldiers
            ->filter(function ($user) use ($department_name) {
                $soldier = Soldier::where('id', '=', $user->userable_id)->first();
                $department = Department::where('name', '=', $department_name);
                $department_id = $department->value('id');
                $teams = Team::where('department_id', '=', $department_id)->get('id');

                return $teams->contains($soldier->team_id) || $soldier->id == $department->value('commander_id');
            });
    }

    protected function filterAll()
    {
        $this->soldiers = $this->soldiers
            ->filter(function ($user) {
                $soldier = Soldier::where('id', $user->userable_id)->first();

                return ! $soldier->is_reservist;
            });
    }

    protected function mapSoldiersConstraints()
    {
        $this->soldiers = $this->soldiers
            ->map(
                function (User $user) {
                    $constraints = collect();
                    $soldier = Soldier::where('id', $user->userable_id)->first();
                    if ($this->soldier_type != 'reserves') {
                        $constraints = $this->getConstraintBy($soldier->id);
                    }value:
                    $soldiers_shifts = Shift::where('soldier_id', $soldier->id)
                        ->get()
                        ->filter(
                            fn (Shift $shift) => $this->shift->range->isSameMonth(new Range($shift->start_date, $shift->end_date))
                        );
                    $soldiers_shifts->map(fn (Shift $shift) => $constraints->push(
                        new \App\Services\Constraint(
                            $shift->start_date,
                            $shift->end_date,
                            Priority::HIGH,
                        )
                    ));

                    return new \App\Services\Soldier(
                        $soldier->id,
                        new MaxData($soldier->capacity, $soldier->capacity_hold),
                        new MaxData($soldier->max_shifts, $soldiers_shifts->count()),
                        new MaxData($soldier->max_nights, $this->nightShiftSum($soldiers_shifts)),
                        new MaxData($soldier->max_weekends, $this->weekendShiftSum($soldiers_shifts)),
                        $soldier->qualifications,
                        $constraints
                    );
                }
            );
    }

    protected function getConstraintBy(int $soldier_id)
    {
        return Constraint::where('soldier_id', $soldier_id)
            ->get()
            ->map(
                fn ($constraint) => new \App\Services\Constraint(
                    $constraint->start_date,
                    $constraint->end_date,
                    ConstraintType::getPriority()[$constraint->constraint_type] == 1 ? Priority::HIGH : Priority::LOW
                )
            );
    }

    protected function weekendShiftSum($shifts): float
    {
        return $shifts->filter(function (Shift $shift) {
            $range = new Range($shift->start_date, $shift->end_date);

            return $range->isWeekend();
        })->count();
    }

    protected function nightShiftSum($shifts): float
    {
        return $shifts->filter(function (Shift $shift) {
            $range = new Range($shift->start_date, $shift->end_date);
            $range->isNight();
        })->count();
    }

    protected function getAvailableSoldiers()
    {
        $available_soldiers = $this->soldiers->filter(
            fn (\App\Services\Soldier $soldier) => $soldier->isQualified($this->shift->task_name)
            && $soldier->isAvailableByMaxes($this->shift)
            && $soldier->isAvailableByConstraints($this->shift->range) != Availability::NO
        );

        return $available_soldiers->mapWithKeys(
            function (\App\Services\Soldier $soldier) {
                $user = User::get()->where('userable_id', '=', $soldier->id)->first();

                return [$user->userable_id => $user->displayName];
            }
        );
    }
}
