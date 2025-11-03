<?php

namespace App\Services;

use App\Enums\TaskKind;
use App\Models\Department;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\Team;
use App\Models\User;
use App\Services\Shift as ShiftService;
use App\Services\Soldier as SoldierService;

class ManualAssignment
{
    public $shift;

    public $soldierType;

    public $soldiers;

    public $range;

    public function __construct(Shift $shift, string $soldierType)
    {
        $this->shift = Helpers::buildShift($shift);
        $this->soldierType = $soldierType;
        $this->soldiers = [];
        $this->range = new Range($this->shift->range->start->copy()->startOfMonth(), $this->shift->range->end->copy()->endOfMonth());
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
            ->with($this->withRelations())
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
            ->with($this->withRelations())
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
            })
            ->with($this->withRelations())
            ->get();
    }

    protected function filterMatching()
    {
        $this->soldiers = Soldier::whereJsonContains('qualifications', $this->shift->taskType)
            ->where('is_reservist', false)
            ->where('id', '!=', auth()->user()->userable_id)
            ->with($this->withRelations())
            ->get();
    }

    protected function withRelations(): array
    {
        return [
            'constraints' => fn ($q) => $q->whereBetween('start_date', [$this->range->start, $this->range->end]),
            'shifts' => fn ($q) => $q->whereBetween('start_date', [$this->range->start, $this->range->end]),
        ];
    }

    protected function getSoldiersDetails()
    {
        $this->soldiers = $this->soldiers
            ->map(
                function (Soldier $soldier) {
                    $constraints = Helpers::buildConstraints($soldier->constraints);
                    $soldiersShifts = Helpers::mapSoldierShifts($soldier->shifts, false);
                    $concurrentsShifts = Helpers::mapSoldierShifts($soldier->shifts, true);

                    $soldiersShifts->push(...Helpers::addShiftsSpaces($soldiersShifts));

                    $soldiersShifts->push(...Helpers::addPrevMonthSpaces($soldier->id, $this->shift->range->start));
                    $capacityHold = Helpers::capacityHold($soldiersShifts, $concurrentsShifts);

                    return Helpers::buildSoldier($soldier, $constraints, $soldiersShifts, $capacityHold, $concurrentsShifts);
                }
            );
    }

    public function amIAvailable(): bool
    {
        $me = Soldier::with($this->withRelations())
            ->find(auth()->user()->userable_id);
        $constraints = Helpers::buildConstraints($me->constraints);
        $myShifts = $this->mapSoldierShifts($me->shifts, false);

        $myShifts = Helpers::mapSoldierShifts($me->shifts, false);

        $myShifts->push(...Helpers::addShiftsSpaces($myShifts));

        $concurrentsShifts = Helpers::mapSoldierShifts($me->shifts, true);
        $myShifts->push(...Helpers::addPrevMonthSpaces($me->id, $this->shift->range->start));
        $capacityHold = Helpers::capacityHold($myShifts, $concurrentsShifts);

        $soldier = Helpers::buildSoldier($me, $constraints, $myShifts, $capacityHold, $concurrentsShifts);

        return $soldier->isAbleTake($this->shift, true)
            && $soldier->isAvailableByConcurrentsShifts($this->shift);
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
