<?php

namespace App\Services;

use App\Enums\Availability;
use App\Models\Department;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\User;
use App\Services\Soldier as SoldierService;
use Illuminate\Support\Facades\Cache;

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
        $this->soldiers = Cache::remember('users', 30 * 60, function () {
            return User::all();
        });
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
                $role = current(array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier']));
                $soldier = $this->getSoldierBy($user->userable_id);

                return match ($role) {
                    'department-commander' => $soldier?->team?->department?->commander_id == $currentUserId
                    && $soldier?->id != $currentUserId,
                    'team-commander' => $soldier?->team?->commander_id == $currentUserId
                    && $soldier?->id != $currentUserId
                };
            });
    }

    protected function filterDepartment($departmentName)
    {
        $this->soldiers = $this->soldiers
            ->filter(function ($user) use ($departmentName) {
                $soldier = Soldier::where('id', '=', $user->userable_id)->first();

                return $soldier?->team?->department?->name == $departmentName
                    || $soldier->id == Department::where('name', '=', $departmentName)->value('commander_id');
            });
    }

    protected function filterMatching()
    {
        $this->soldiers = $this->soldiers
            ->filter(function ($user) {
                $soldier = $this->getSoldierBy($user->userable_id);

                return ! $soldier->is_reservist && $soldier->id != auth()->user()->userable_id;
            });
    }

    protected function getSoldiersDetails()
    {
        $this->soldiers = $this->soldiers
            ->map(
                function (User $user) {
                    $soldier = $this->getSoldierBy($user->userable_id);
                    $constraints = $this->getConstraints($soldier);
                    $soldiersShifts = $this->getSoldiersShifts($soldier->id, false);

                    $soldiersShifts->push(...Helpers::addShiftsSpaces($soldiersShifts));

                    $capacityHold = Helpers::capacityHold($soldiersShifts);

                    $concurrentsShifts = $this->getSoldiersShifts($soldier->id, true);

                    return Helpers::buildSoldier($soldier, $constraints, $soldiersShifts, $capacityHold, $concurrentsShifts);
                }
            );
    }

    protected function getSoldierBy($userable_id)
    {
        return Soldier::where('id', $userable_id)->first();
    }

    public function amIAvailable(): bool
    {
        $me = Soldier::find(auth()->user()->userable_id);
        $constraints = $this->getConstraints($me);
        $myShifts = $this->getSoldiersShifts($me->id, false);

        $myShifts->push(...Helpers::addShiftsSpaces($myShifts));

        $capacityHold = Helpers::capacityHold($myShifts);

        $soldier = Helpers::buildSoldier($me, $constraints, $myShifts, $capacityHold);

        return $soldier->isQualified($this->shift->taskType)
            && $soldier->isAvailableByMaxes($this->shift)
            && $soldier->isAvailableByConstraints($this->shift->range) != Availability::NO
            && $soldier->isAvailableByShifts($this->shift, $this->shift->inParallel)
            && $soldier->isAvailableBySpaces($this->shift->getShiftSpaces($soldier->shifts));
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
        $availableSoldiers = $this->soldiers->filter(
            fn (SoldierService $soldier) => $soldier->isQualified($this->shift->taskType)
            && $soldier->isAvailableByMaxes($this->shift)
            && $soldier->isAvailableByConstraints($this->shift->range) != Availability::NO
            && $soldier->isAvailableByShifts($this->shift, $this->shift->inParallel)
            && $soldier->isAvailableBySpaces($this->shift->getShiftSpaces($soldier->shifts))
        );

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