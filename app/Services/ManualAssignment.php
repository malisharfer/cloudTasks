<?php

namespace App\Services;

use App\Enums\Availability;
use App\Enums\ConstraintType;
use App\Enums\Priority;
use App\Models\Constraint;
use App\Models\Department;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class ManualAssignment
{
    public $shift;

    public $soldierType;

    public $soldiers;

    public function __construct(Shift $shift, string $soldierType)
    {
        $this->shift = new \App\Services\Shift(
            $shift->id,
            $shift->task->type,
            $shift->start_date,
            $shift->end_date,
            $shift->parallel_weight == 0 ? $shift->task->parallel_weight : $shift->parallel_weight,
            $shift->task->is_night,
            $shift->is_weekend != null ? $shift->is_weekend : $shift->task->is_weekend
        );
        $this->soldierType = $soldierType;
        $this->soldiers = [];
    }

    public function getSoldiers($qualification = null, $departmentName = null)
    {
        $this->initSoldiersData($qualification, $departmentName);

        return $this->getAvailableSoldiers();
    }

    protected function initSoldiersData($qualification, $departmentName)
    {
        $this->soldiers = Cache::remember('users', 30 * 60, function () {
            return User::all();
        });
        match ($this->soldierType) {
            'reserves' => $this->filterReserves(),
            'my_soldiers' => $this->filterMySoldiers(),
            'department' => $this->filterDepartment($departmentName),
            'all' => $this->filterAll()
        };
        $this->getSoldiersDetails($qualification);
    }

    protected function filterReserves()
    {
        $this->soldiers = $this->soldiers
            ->filter(function ($user) {
                $soldier = $this->getSoldierBy($user->userable_id);

                return $soldier->is_reservist && $soldier->id != auth()->user()->userable_id;
            });
    }

    protected function filterMySoldiers()
    {
        $this->soldiers = $this->soldiers
            ->filter(function ($user) {
                $currentUserId = auth()->user()->userable_id;
                $role = current(array: array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier']));
                $soldier = $this->getSoldierBy($user->userable_id);

                return match ($role) {
                    'department-commander' => $soldier?->team?->department?->commander_id == $currentUserId
                    && $soldier?->id != $currentUserId,
                    'team-commander' => $soldier?->team?->commander_id == $currentUserId
                    && $soldier?->id != $currentUserId
                };
            });
    }

    protected function filterDepartment($name)
    {
        $departmentName = $name ?: Shift::find($this->shift->id)->task?->department_name;

        $this->soldiers = $this->soldiers
            ->filter(function ($user) use ($departmentName) {
                $soldier = Soldier::where('id', '=', $user->userable_id)->first();

                return $soldier?->team?->department?->name == $departmentName
                    || $soldier->id == Department::where('name', '=', $departmentName)->value('commander_id');
            });
    }

    protected function filterAll()
    {
        $this->soldiers = $this->soldiers
            ->filter(function ($user) {
                $soldier = $this->getSoldierBy($user->userable_id);

                return ! $soldier->is_reservist && $soldier->id != auth()->user()->userable_id;
            });
    }

    protected function getSoldiersDetails($qualification)
    {
        $this->soldiers = $this->soldiers
            ->map(
                function (User $user) use ($qualification) {
                    $soldier = $this->getSoldierBy($user->userable_id);
                    $soldier->qualifications = $this->mapQualifications($soldier->qualifications, $qualification);
                    $constraints = $this->getConstraints($soldier);
                    $soldiersShifts = $this->getSoldiersShifts($soldier);

                    $soldiersShifts->push(...$this->addShiftsSpaces($soldiersShifts));

                    $soldierSums = $this->soldierSum($soldiersShifts);

                    return new \App\Services\Soldier(
                        $soldier->id,
                        new MaxData($soldier->capacity, $soldierSums['points']),
                        new MaxData($soldier->max_shifts, $soldierSums['count']),
                        new MaxData($soldier->max_nights, $soldierSums['sumNights']),
                        new MaxData($soldier->max_weekends, $soldierSums['sumWeekends']),
                        $soldier->qualifications,
                        $constraints,
                        $soldiersShifts
                    );
                }
            );
    }

    protected function getSoldierBy($userable_id)
    {
        return Soldier::where('id', $userable_id)->first();
    }

    public function amIAvailable($qualification = null): bool
    {
        $me = Soldier::find(auth()->user()->userable_id);
        $me->qualifications = $this->mapQualifications($me->qualifications, $qualification);
        $constraints = $this->getConstraints($me);
        $myShifts = $this->getSoldiersShifts($me);

        $myShifts->push(...$this->addShiftsSpaces($myShifts));

        $soldierSums = $this->soldierSum($myShifts);

        $soldier = new \App\Services\Soldier(
            $me->id,
            new MaxData($me->capacity, $soldierSums['points']),
            new MaxData($me->max_shifts, $soldierSums['count']),
            new MaxData($me->max_nights, $soldierSums['sumNights']),
            new MaxData($me->max_weekends, $soldierSums['sumWeekends']),
            $me->qualifications,
            $constraints,
            $myShifts
        );

        return $soldier->isQualified($this->shift->taskType)
            && $soldier->isAvailableByMaxes($this->shift)
            && $soldier->isAvailableByConstraints($this->shift->range) != Availability::NO
            && $soldier->isAvailableByShifts($this->shift->range)
            && $soldier->isAvailableBySpaces($this->shift->getShiftSpaces($soldier->shifts));
    }

    protected function mapQualifications($qualifications, $qualification)
    {
        return $qualification ? collect($qualifications)->push($qualification) : $qualifications;
    }

    protected function getConstraints(Soldier $soldier)
    {
        return $this->soldierType != 'reserves' ? $this->getConstraintBy($soldier->id) : collect([]);
    }

    protected function getSoldiersShifts(Soldier $soldier)
    {
        return Shift::where('soldier_id', $soldier->id)
            ->get()
            ->filter(
                fn (Shift $shift) => $this->shift->range->isSameMonth(new Range($shift->start_date, $shift->end_date))
            )->map(fn (Shift $shift) => new \App\Services\Shift(
                $shift->id,
                $shift->task->type,
                $shift->start_date,
                $shift->end_date,
                $shift->parallel_weight == 0 ? $shift->task->parallel_weight : $shift->parallel_weight,
                $shift->task->is_night,
                $shift->is_weekend != null ? $shift->is_weekend : $shift->task->is_weekend,
            ));
    }

    protected function addShiftsSpaces($shifts)
    {
        $allSpaces = collect([]);
        collect($shifts)->map(function (\App\Services\Shift $shift) use ($shifts, &$allSpaces) {
            if ($shift->isWeekend) {
                $spaces = $shift->getShiftSpaces($shifts);
            }
            if ($shift->isNight) {
                $spaces = $shift->getShiftSpaces($shifts);
            }
            if (! empty($spaces)) {
                collect($spaces)->map(fn (Range $space) => $allSpaces->push(new \App\Services\Shift(0, 'space', $space->start, $space->end, 0, false, false)));
            }
        });

        return $allSpaces;
    }

    protected function getConstraintBy(int $soldierId)
    {
        return Constraint::where('soldier_id', $soldierId)
            ->get()
            ->map(
                fn ($constraint) => new \App\Services\Constraint(
                    $constraint->start_date,
                    $constraint->end_date,
                    ConstraintType::getPriority()[$constraint->constraint_type] == 1 ? Priority::HIGH : Priority::LOW
                )
            );
    }

    protected function soldierSum($shifts)
    {
        $points = 0;
        $nights = 0;
        $weekends = 0;
        $count = 0;
        collect($shifts)
            ->filter(function ($shift) {
                return $shift->id != 0;
            })
            ->map(function ($shift) use (&$count, &$points, &$nights, &$weekends) {
                $count++;
                $points += $shift->points;
                $shift->isWeekend ? $weekends += $shift->points : $weekends;
                $shift->isNight ? $nights += $shift->points : $nights;
            });

        return [
            'count' => $count,
            'points' => $points,
            'sumWeekends' => $weekends,
            'sumNights' => $nights,
        ];
    }

    protected function getAvailableSoldiers()
    {
        $availableSoldiers = $this->soldiers->filter(
            fn (\App\Services\Soldier $soldier) => $soldier->isQualified($this->shift->taskType)
            && $soldier->isAvailableByMaxes($this->shift)
            && $soldier->isAvailableByConstraints($this->shift->range) != Availability::NO
            && $soldier->isAvailableByShifts($this->shift->range)
            && $soldier->isAvailableBySpaces($this->shift->getShiftSpaces($soldier->shifts))
        );

        return $availableSoldiers->mapWithKeys(
            function (\App\Services\Soldier $soldier) {
                $user = User::where('userable_id', '=', $soldier->id)->first();

                return [$user->userable_id => $user->displayName];
            }
        );
    }
}