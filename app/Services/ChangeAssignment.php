<?php

namespace App\Services;

use App\Enums\TaskKind;
use App\Models\Shift;
use App\Models\Soldier;
use App\Services\Constraint as ConstraintService;
use App\Services\Shift as ShiftService;
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
        $soldier = Soldier::with($this->withRelations())->find(Shift::find($this->shift->id)->soldier_id);

        $constraints = Helpers::buildConstraints($soldier->constraints);

        $shifts = $this->mapSoldierShifts($soldier->shifts, false);

        $shifts->push(...Helpers::addShiftsSpaces($shifts));
        $shifts->push(...Helpers::addPrevMonthSpaces($soldier->id, $this->shift->range->start));

        $concurrentsShifts = $this->mapSoldierShifts($soldier->shifts, true);

        return Helpers::buildSoldier($soldier, $constraints, $shifts, [], $concurrentsShifts);
    }

    public function getMatchingSoldiers()
    {
        return Soldier::where('id', '!=', $this->soldier->id)
            ->whereJsonContains('qualifications', $this->shift->taskType)
            ->with($this->withRelations())
            ->lazy()
            ->map(function ($soldier) {
                $constraints = Helpers::buildConstraints($soldier->constraints);

                $soldierShifts = $this->mapSoldierShifts($soldier->shifts, false);

                $soldierShifts->push(...Helpers::addShiftsSpaces($soldierShifts));

                $concurrentsShifts = $this->mapSoldierShifts($soldier->shifts, true);

                $soldierShifts->push(...Helpers::addPrevMonthSpaces($soldier->id, $this->shift->range->start));

                return Helpers::buildSoldier($soldier, $constraints, $soldierShifts, [], $concurrentsShifts);
            })
            ->filter(fn(SoldierService $soldier) => $soldier->isAvailableBySpaces($this->shift->getShiftSpaces($soldier->shifts))
                && !$this->isConflictWithConstraints($soldier, $this->shift->range)
                && $soldier->isAvailableByShifts($this->shift))
            ->mapWithKeys(fn(SoldierService $soldier) => !$soldier->isAvailableByConcurrentsShifts($this->shift) ?
                [$soldier->id => Soldier::find($soldier->id)->user->displayName . ' 📌']
                : [$soldier->id => Soldier::find($soldier->id)->user->displayName])
            ->toArray();
    }

    public function getMatchingShifts()
    {
        $data = collect([
            'shifts' => collect([]),
            'soldiersWithConcurrents' => collect([]),
        ]);
        Shift::whereNotNull('soldier_id')
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
                if (!$this->soldier->isAvailableByConcurrentsShifts($newShift)) {
                    $data['shifts']->push(['shift' => $shift, 'hasConcurrentsShifts' => true]);
                }

                return
                    $this->soldier->isAvailableBySpaces($newShift->getShiftSpaces($this->soldier->shifts))
                    && !$this->isConflictWithConstraints($this->soldier, $newShift->range)
                    && $this->soldier->isAvailableByShifts($newShift)
                    && $this->soldier->isAvailableByConcurrentsShifts($newShift);
            })
            ->groupBy('soldier_id')
            ->filter(function ($shifts, $soldier_id) use (&$data) {
                $soldierDetails = Soldier::with($this->withRelations())->find($soldier_id);
                $constraints = Helpers::buildConstraints($soldierDetails->constraints);

                $soldierShifts = $this->mapSoldierShifts($soldierDetails->shifts, false);

                $soldierShifts->push(...Helpers::addShiftsSpaces($soldierShifts));

                $concurrentsShifts = $this->mapSoldierShifts($soldierDetails->shifts, true);

                $soldierShifts->push(...Helpers::addPrevMonthSpaces($soldierDetails->id, $this->shift->range->start));

                $soldier = Helpers::buildSoldier($soldierDetails, $constraints, $soldierShifts, [], $concurrentsShifts);

                if (!$soldier->isAvailableByConcurrentsShifts($this->shift)) {
                    $data['soldiersWithConcurrents']->push($soldier_id);
                }

                return $soldier->isAvailableBySpaces($this->shift->getShiftSpaces($soldier->shifts))
                    && !$this->isConflictWithConstraints($soldier, $this->shift->range)
                    && $soldier->isAvailableByShifts($this->shift);

            })
            ->each(function ($shifts, $soldier_id) use (&$data) {
                $shifts->each(function (Shift $shift) use (&$data) {
                    $data['shifts']->push(['shift' => $shift, 'hasConcurrentsShifts' => false]);
                });
            });

        return $data;
    }

    protected function mapSoldierShifts($shifts, $inParallel)
    {
        return $shifts->filter(fn(Shift $shift) => $inParallel
            ? $shift->task->kind == TaskKind::INPARALLEL->value
            : $shift->task->kind != TaskKind::INPARALLEL->value)
            ->map(fn(Shift $shift): ShiftService => Helpers::buildShift($shift));
    }


    protected function withRelations(): array
    {
        $range = new Range($this->shift->range->start->copy()->startOfMonth(), $this->shift->range->end->copy()->endOfMonth());

        return [
            'constraints' => fn($q) => $q->whereBetween('start_date', [$range->start, $range->end]),
            'shifts' => fn($q) => $q->whereBetween('start_date', [$range->start, $range->end])
        ];
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
