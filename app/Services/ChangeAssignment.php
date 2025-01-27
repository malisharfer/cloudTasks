<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\Soldier;
use App\Services\Constraint as ConstraintService;
use App\Services\Soldier as SoldierService;

class ChangeAssignment
{
    protected $shift;

    protected $soldier;

    protected $shifts;

    public function __construct($shift)
    {
        $this->shift = Helpers::buildShift($shift);
        $this->soldier = $this->buildSoldier();
    }

    protected function buildSoldier(): SoldierService
    {
        $soldier = Soldier::find(Shift::find($this->shift->id)->soldier_id);
        $constraints = $this->getConstraints($soldier);
        $shifts = $this->getSoldiersShifts($soldier->id, false);
        $shifts->push(...Helpers::addShiftsSpaces($shifts));
        $concurrentsShifts = $this->getSoldiersShifts($soldier->id, true);

        return Helpers::buildSoldier($soldier, $constraints, $shifts, [], $concurrentsShifts);
    }

    public function getMatchingSoldiers()
    {
        return Soldier::where('id', '!=', $this->soldier->id)
            ->get()
            ->map(function ($soldier) {
                $constraints = $this->getConstraints($soldier);
                $soldierShifts = $this->getSoldiersShifts($soldier->id, false);
                $soldierShifts->push(...Helpers::addShiftsSpaces($soldierShifts));
                $concurrentsShifts = $this->getSoldiersShifts($soldier->id, true);

                return Helpers::buildSoldier($soldier, $constraints, $soldierShifts, [], $concurrentsShifts);
            })
            ->filter(function (SoldierService $soldier) {
                return $soldier->isQualified($this->shift->taskType)
                    && $soldier->isAvailableBySpaces($this->shift->getShiftSpaces($soldier->shifts))
                    && ! $this->isConflictWithConstraints($soldier, $this->shift->range)
                    && $soldier->isAvailableByShifts($this->shift, Shift::find($this->shift->id)->task->in_parallel);
            })
            ->mapWithKeys(function (SoldierService $soldier) {
                return ! $soldier->isAvailableByConcurrentsShifts($this->shift) ?
                    [$soldier->id => Soldier::find($soldier->id)->user->displayName.' 📌']
                    : [$soldier->id => Soldier::find($soldier->id)->user->displayName];
            })
            ->toArray();
    }

    public function getMatchingShifts()
    {
        return Shift::whereNotNull('soldier_id')
            ->where('soldier_id', '!=', $this->soldier->id)
            ->get()
            ->filter(function (Shift $shift) {
                $range = new Range($shift->start_date, $shift->end_date);
                $shift = Helpers::buildShift($shift);

                return
                    ! $range->isPass()
                    && $range->isSameMonth(new Range($this->shift->range->start->copy()->startOfMonth(), $this->shift->range->end->copy()->endOfMonth()))
                    && $this->soldier->isQualified($shift->taskType)
                    && $this->soldier->isAvailableBySpaces($this->shift->getShiftSpaces($this->soldier->shifts))
                    && ! $this->isConflictWithConstraints($this->soldier, $range)
                    && $this->soldier->isAvailableByShifts($shift, Shift::find($shift->id)->task->in_parallel);
            })
            ->groupBy('soldier_id')
            ->filter(function ($shifts, $soldier_id) {
                $soldierDetails = Soldier::find($soldier_id);
                $constraints = $this->getConstraints($soldierDetails);
                $soldierShifts = $this->getSoldiersShifts($soldierDetails->id, false);
                $soldierShifts->push(...Helpers::addShiftsSpaces($soldierShifts));
                $concurrentsShifts = $this->getSoldiersShifts($soldierDetails->id, true);

                $soldier = Helpers::buildSoldier($soldierDetails, $constraints, $soldierShifts, [], $concurrentsShifts);

                return $soldier->isQualified($this->shift->taskType)
                    && $soldier->isAvailableBySpaces($this->shift->getShiftSpaces($soldier->shifts))
                    && ! $this->isConflictWithConstraints($soldier, $this->shift->range)
                    && $soldier->isAvailableByShifts($this->shift, Shift::find($this->shift->id)->task->in_parallel);
            });
    }

    protected function getConstraints(Soldier $soldier)
    {
        return ! $soldier->is_reservist ? Helpers::getConstraintBy($soldier->id, new Range($this->shift->range->start->copy()->startOfMonth(), $this->shift->range->end->copy()->endOfMonth())) : collect([]);
    }

    protected function getSoldiersShifts($soldierId, $inParallel)
    {
        return Helpers::getSoldiersShifts($soldierId, new Range($this->shift->range->start->copy()->startOfMonth(), $this->shift->range->end->copy()->endOfMonth()), $inParallel);
    }

    protected function isConflictWithConstraints($soldier, $range): bool
    {
        return $soldier->constraints->contains(function (ConstraintService $constraint) use ($range): bool {
            return $constraint->range->isConflict($range);
        });
    }

    public function exchange($shift)
    {
        Shift::where('id', $shift->id)->update(['soldier_id' => Shift::find($this->shift->id)->soldier_id]);
        Shift::where('id', $this->shift->id)->update(['soldier_id' => $shift->soldier_id]);
    }
}
