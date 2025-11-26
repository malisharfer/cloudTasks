<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\Team;
use App\Models\User;
use App\Services\Soldier as SoldierService;

class ManualAssignment
{
    public $shift;

    public $soldierType;

    public $soldiers;

    public function __construct(Shift $shift, string $soldierType)
    {
        $this->shift = Helpers::buildShift($shift);
        $this->soldierType = $soldierType;
        $this->soldiers = [];
    }

    public function getSoldiers($departmentName = null)
    {
        $this->initSoldiersData($departmentName);

        return $this->getAvailableSoldiers();
    }

    protected function initSoldiersData($departmentName)
    {
        match ($this->soldierType) {
            'reserves' => $this->filterReserves(),
            'my_soldiers' => $this->filterMySoldiers(),
            'department' => $this->filterDepartment($departmentName),
            'matching' => $this->filterMatching(),
        };
        $this->getSoldiersDetails();
    }

    protected function filterReserves()
    {
        $this->soldiers = Soldier::whereJsonContains('qualifications', $this->shift->taskType)
            ->where('is_reservist', true)
            ->where('id', '!=', auth()->user()->userable_id)
            ->get();
    }

    protected function filterMySoldiers()
    {
        $currentUserId = auth()->user()->userable_id;
        $role = current(array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier']));
        $members = collect();

        switch ($role) {
            case 'department-commander':
                $department = Department::whereHas('commander', function ($query) use ($currentUserId) {
                    $query->where('id', $currentUserId);
                })->first();

                $memberIds = $department?->teams->flatMap(fn (Team $team) => $team->members->pluck('id')) ?? collect();
                $commanderIds = $department?->teams->pluck('commander_id') ?? collect();

                $members = $memberIds->merge($commanderIds)->unique();
                break;
            case 'team-commander':
                $members = Team::whereHas('commander', function ($query) use ($currentUserId) {
                    $query->where('id', $currentUserId);
                })->first()?->members->pluck('id') ?? collect([]);
                break;
        }

        $this->soldiers = Soldier::whereJsonContains('qualifications', $this->shift->taskType)
            ->where('is_reservist', false)
            ->where('id', '!=', $currentUserId)
            ->whereIn('id', $members)
            ->get();
    }

    protected function filterDepartment($departmentName)
    {
        $department = Department::where('name', $departmentName)->first();
        $this->soldiers = Soldier::whereJsonContains('qualifications', $this->shift->taskType)
            ->where('is_reservist', false)
            ->where('id', '!=', auth()->user()->userable_id)
            ->where(function (Soldier $query) use ($department) {
                $query->whereIn('id', $department?->teams->flatMap(fn (Team $team) => $team->members->pluck('id')) ?? collect())
                    ->orWhereIn('id', $department?->teams->pluck('commander_id') ?? collect())
                    ->orWhere('id', $department?->commander_id);
            })->get();
    }

    protected function filterMatching()
    {
        $this->soldiers = Soldier::whereJsonContains('qualifications', $this->shift->taskType)
            ->where('is_reservist', false)
            ->where('id', '!=', auth()->user()->userable_id)
            ->get();
    }

    protected function getSoldiersDetails()
    {
        $this->soldiers = $this->soldiers
            ->map(
                function (Soldier $soldier) {
                    $constraints = $this->getConstraints($soldier);
                    $soldiersShifts = $this->getSoldiersShifts($soldier->id, false);

                    $soldiersShifts->push(...Helpers::addShiftsSpaces($soldiersShifts));

                    $concurrentsShifts = $this->getSoldiersShifts($soldier->id, true);
                    $soldiersShifts->push(...Helpers::addPrevMonthSpaces($soldier->id, $this->shift->range->start));
                    $capacityHold = Helpers::capacityHold($soldiersShifts);

                    return Helpers::buildSoldier($soldier, $constraints, $soldiersShifts, $capacityHold, $concurrentsShifts);
                }
            );
    }

    public function amIAvailable(): bool
    {
        $me = Soldier::find(auth()->user()->userable_id);
        $constraints = $this->getConstraints($me);
        $myShifts = $this->getSoldiersShifts($me->id, false);

        $myShifts->push(...Helpers::addShiftsSpaces($myShifts));

        $concurrentsShifts = $this->getSoldiersShifts($me->id, true);
        $myShifts->push(...Helpers::addPrevMonthSpaces($me->id,$this->shift->range->start));
        $capacityHold = Helpers::capacityHold($myShifts);

        $soldier = Helpers::buildSoldier($me, $constraints, $myShifts, $capacityHold, $concurrentsShifts);

        return $soldier->isAbleTake($this->shift, true)
            && $soldier->isAvailableByConcurrentsShifts($this->shift);
    }

    protected function getConstraints(Soldier $soldier)
    {
        return $this->soldierType != 'reserves' ? Helpers::getConstraintBy($soldier->id, new Range($this->shift->range->start->copy()->startOfMonth(), $this->shift->range->end->copy()->endOfMonth())) : collect([]);
    }

    protected function getSoldiersShifts($soldierId, $inParallel)
    {
        return Helpers::getSoldiersShifts($soldierId, new Range($this->shift->range->start->copy()->startOfMonth(), $this->shift->range->end->copy()->endOfMonth()), $inParallel);
    }

    protected function getAvailableSoldiers()
    {
        $availableSoldiers = $this->soldiers->filter(fn (SoldierService $soldier) => $soldier->isAbleTake($this->shift, true));

        $soldiersWithConcurrentsShifts = collect([]);
        $availableSoldiers->map(function (SoldierService $soldier) use ($soldiersWithConcurrentsShifts) {
            if (! $soldier->isAvailableByConcurrentsShifts($this->shift)) {
                $soldiersWithConcurrentsShifts->push($soldier->id);
            }
        });

        return $availableSoldiers->mapWithKeys(
            function (SoldierService $soldier) use ($soldiersWithConcurrentsShifts) {
                $user = User::where('userable_id', '=', $soldier->id)->first();
                if ($soldiersWithConcurrentsShifts->contains($soldier->id)) {
                    return [$user->userable_id => $user->displayName.' 📌'];
                }

                return [$user->userable_id => $user->displayName];
            }
        );
    }
}
