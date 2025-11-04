<?php

namespace App\Services;

use App\Enums\TaskKind;
use App\Models\Shift;
use App\Models\Soldier;
use App\Services\Shift as ShiftService;
use Carbon\Carbon;

class Algorithm
{
    protected $date;

    public function __construct($date = null)
    {
        $this->date = $date ? Carbon::parse($date) : now()->addMonth();
    }

    protected function getShiftWithTasks()
    {
        $startOfMonth = $this->date->copy()->startOfMonth();
        $endOfMonth = $this->date->copy()->endOfMonth();

        $results = collect();

        Shift::query()
            ->with(['task' => fn ($q) => $q->withTrashed()])
            ->whereNull('soldier_id')
            ->whereBetween('start_date', [$startOfMonth, $endOfMonth])
            ->whereHas('task', function ($query) {
                $query->withTrashed()
                    ->where('kind', '!=', TaskKind::INPARALLEL->value);
            })
            ->chunk(300, function ($shifts) use (&$results) {
                $mapped = $shifts->map(fn (Shift $shift): ShiftService => Helpers::buildShift($shift));
                $results = $results->merge($mapped);
            });

        return $results;
    }

    protected function getSoldiersDetails()
    {
        $range = new Range(
            $this->date->copy()->startOfMonth(),
            $this->date->copy()->endOfMonth()
        );

        $results = collect();

        Soldier::where('is_reservist', false)
            ->with([
                'constraints' => fn($q) => $q->whereBetween('start_date', [$range->start, $range->end]),
                'shifts' => fn($q) => $q->whereBetween('start_date', [$range->start, $range->end])
                    ->whereHas('task', function ($query) {
                        $query->withTrashed()->where('kind', '!=', TaskKind::INPARALLEL->value);
                    }),
            ])
            ->chunk(100, function ($soldiers) use (&$results) {
                $mapped = $soldiers
                    ->map(function (Soldier $soldier) {
                        $constraints = Helpers::buildConstraints($soldier->constraints);

                        $shifts = Helpers::mapSoldierShifts($soldier->shifts, false);
                        $shifts->push(...Helpers::addShiftsSpaces($shifts));
                        $shifts->push(...Helpers::addPrevMonthSpaces($soldier->id, now()));

                        $capacityHold = Helpers::capacityHold($shifts, []);

                        return Helpers::buildSoldier($soldier, $constraints, $shifts, $capacityHold);
                    })
                    ->filter(fn($soldier) => $soldier->hasMaxes());

                $results = $results->merge($mapped);
            });

        return $results->shuffle();
    }    public function run()
    {
        $shifts = $this->getShiftWithTasks();
        $soldiers = $this->getSoldiersDetails();
        $scheduleAlgorithm = new Schedule($shifts, $soldiers);
        $scheduleAlgorithm->schedule();
        $concurrentTasks = new ConcurrentTasks($this->date);
        $concurrentTasks->run();
    }
}
