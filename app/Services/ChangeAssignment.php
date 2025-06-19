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
            // ->whereJsonLength('qualifications', '>', 0)
            ->get()
            ->map(function ($soldier) {
                $constraints = $this->getConstraints($soldier);
                $soldierShifts = $this->getSoldiersShifts($soldier->id, false);
                $soldierShifts->push(...Helpers::addShiftsSpaces($soldierShifts));
                $concurrentsShifts = $this->getSoldiersShifts($soldier->id, true);

                return Helpers::buildSoldier($soldier, $constraints, $soldierShifts, [], $concurrentsShifts);
            })
            ->filter(fn (SoldierService $soldier) => $soldier->isQualified($this->shift->taskType)
                && $soldier->isAvailableBySpaces($this->shift->getShiftSpaces($soldier->shifts))
                && ! $this->isConflictWithConstraints($soldier, $this->shift->range)
                && $soldier->isAvailableByShifts($this->shift))
            ->mapWithKeys(fn (SoldierService $soldier) => ! $soldier->isAvailableByConcurrentsShifts($this->shift) ?
                [$soldier->id => Soldier::find($soldier->id)->user->displayName.' 📌']
                : [$soldier->id => Soldier::find($soldier->id)->user->displayName])
            ->toArray();
    }

    public function getMatchingShifts()
    {
        $data = collect([
            'shifts' => collect([]),
            'soldiersWithConcurrents' => collect([]),
        ]);
        Shift::with('task')
            ->whereNotNull('soldier_id')
            ->where('soldier_id', '!=', $this->soldier->id)
            ->where('start_date', '>', now())
            ->whereMonth('start_date', $this->shift->range->start->month)
            ->whereYear('start_date', $this->shift->range->start->year)
            ->whereHas('soldier', function ($query) {
                $query->whereJsonContains('qualifications', $this->shift->taskType);
            })
            ->whereHas('task', function ($query) {
                $query->whereIn('type', collect($this->soldier->qualifications));
            })
            ->get()
            ->filter(function (Shift $shift) use (&$data) {
                $newShift = Helpers::buildShift($shift);
                if (! $this->soldier->isAvailableByConcurrentsShifts($newShift)) {
                    $data['shifts']->push(['shift' => $shift, 'hasConcurrentsShifts' => true]);
                }

                return
                    $this->soldier->isAvailableBySpaces($newShift->getShiftSpaces($this->soldier->shifts))
                    && ! $this->isConflictWithConstraints($this->soldier, $newShift->range)
                    && $this->soldier->isAvailableByShifts($newShift)
                    && $this->soldier->isAvailableByConcurrentsShifts($newShift);
            })
            ->groupBy('soldier_id')
            ->filter(function ($shifts, $soldier_id) use (&$data) {
                $soldierDetails = Soldier::find($soldier_id);
                $constraints = $this->getConstraints($soldierDetails);
                $soldierShifts = $this->getSoldiersShifts($soldierDetails->id, false);
                $soldierShifts->push(...Helpers::addShiftsSpaces($soldierShifts));
                $concurrentsShifts = $this->getSoldiersShifts($soldierDetails->id, true);
                $soldier = Helpers::buildSoldier($soldierDetails, $constraints, $soldierShifts, [], $concurrentsShifts);
                if (! $soldier->isAvailableByConcurrentsShifts($this->shift)) {
                    $data['soldiersWithConcurrents']->push($soldier_id);
                }

                return $soldier->isAvailableBySpaces($this->shift->getShiftSpaces($soldier->shifts))
                    && ! $this->isConflictWithConstraints($soldier, $this->shift->range)
                    && $soldier->isAvailableByShifts($this->shift);

            })
            ->each(function ($shifts, $soldier_id) use (&$data) {
                $shifts->each(function (Shift $shift) use (&$data) {
                    $data['shifts']->push(['shift' => $shift, 'hasConcurrentsShifts' => false]);
                });
            });

        return $data;
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
        $currentSoldierId = Shift::find($this->shift->id)->soldier_id;
        $newSoldierId = $shift->soldier_id;

        \DB::transaction(function () use ($shift, $currentSoldierId, $newSoldierId) {
            Shift::where('id', $shift->id)->update(['soldier_id' => $currentSoldierId]);
            Shift::where('id', $this->shift->id)->update(['soldier_id' => $newSoldierId]);
        });
    }
}