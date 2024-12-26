<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\Soldier;
use App\Services\Constraint as ConstraintService;
use App\Services\Shift as ShiftService;
use App\Services\Soldier as SoldierService;

class ChangeAssignment
{
    protected $shift;

    protected $soldier;

    protected $shifts;

    public function __construct($shift)
    {
        $this->shift = $shift;
        $this->soldier = $this->buildSoldier();
    }

    protected function buildSoldier(): SoldierService
    {
        $soldier = Soldier::find($this->shift->soldier_id);
        $constraints = $this->getConstraints($soldier);
        $shifts = $this->getSoldiersShifts($soldier->id);

        return Helpers::buildSoldier($soldier, $constraints, $shifts, []);
    }

    public function getMatchingSoldiers()
    {
        return Soldier::where('id', '!=', $this->soldier->id)
            ->get()
            ->map(function ($soldier) {
                $constraints = $this->getConstraints($soldier);
                $soldierShifts = $this->getSoldiersShifts($soldier->id);

                return Helpers::buildSoldier($soldier, $constraints, $soldierShifts, []);
            })
            ->filter(function (SoldierService $soldier) {
                return $soldier->isQualified($this->shift->task->type)
                    && ! $this->isConflictWithConstraints($soldier, new Range($this->shift->start_date, $this->shift->end_date))
                    && ! $this->isConflictWithShifts($soldier, new Range($this->shift->start_date, $this->shift->end_date));
            })
            ->mapWithKeys(function (SoldierService $soldier) {
                return [$soldier->id => Soldier::find($soldier->id)->user->displayName];
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

                return
                    ! $range->isPass()
                    && $range->isSameMonth(new Range($this->shift->start_date->copy()->startOfMonth(), $this->shift->end_date->copy()->endOfMonth()))
                    && $this->soldier->isQualified($shift->task->type)
                    && ! $this->isConflictWithConstraints($this->soldier, $range)
                    && ! $this->isConflictWithShifts($this->soldier, $range);
            })
            ->groupBy('soldier_id')
            ->filter(function ($shifts, $soldier_id) {
                $soldierDetails = Soldier::find($soldier_id);
                $constraints = $this->getConstraints($soldierDetails);
                $soldierShifts = $this->getSoldiersShifts($soldierDetails->id);
                $soldier = Helpers::buildSoldier($soldierDetails, $constraints, $soldierShifts, []);

                return $soldier->isQualified($this->shift->task->type)
                    && ! $this->isConflictWithConstraints($soldier, new Range($this->shift->start_date, $this->shift->end_date))
                    && ! $this->isConflictWithShifts($soldier, new Range($this->shift->start_date, $this->shift->end_date));
            });
    }

    protected function getConstraints(Soldier $soldier)
    {
        return ! $soldier->is_reservist ? Helpers::getConstraintBy($soldier->id, new Range($this->shift->start_date->copy()->startOfMonth(), $this->shift->end_date->copy()->endOfMonth())) : collect([]);
    }

    protected function getSoldiersShifts($soldierId)
    {
        return Helpers::getSoldiersShifts($soldierId, new Range($this->shift->start_date->copy()->startOfMonth(), $this->shift->end_date->copy()->endOfMonth()));
    }

    protected function isConflictWithConstraints($soldier, $range): bool
    {
        return $soldier->constraints->contains(function (ConstraintService $constraint) use ($range): bool {
            return $constraint->range->isConflict($range);
        });
    }

    protected function isConflictWithShifts($soldier, $range): bool
    {
        return $soldier->shifts->contains(function (ShiftService $shift) use ($range): bool {
            return $shift->range->isConflict($range);
        });
    }

    public function exchange($shift)
    {
        Shift::where('id', $this->shift->id)->update(['soldier_id' => $shift->soldier_id]);
        Shift::where('id', $shift->id)->update(['soldier_id' => $this->shift->soldier_id]);
    }
}
