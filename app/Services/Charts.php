<?php

namespace App\Services;

use App\Enums\ConstraintType;
use App\Enums\TaskKind;
use App\Models\Constraint as ConstraintModel;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\User;
use Carbon\Carbon;

class Charts
{
    protected $data;

    protected $labels;

    public function __construct()
    {
        $this->data = collect([]);
        $this->labels = collect([]);
    }

    public function organizeChartData($filter, $course, $month, $year): array
    {
        $soldiersData = $this->getData($course, $month, $year);
        $soldiersData->map(function ($soldier) use ($filter) {
            $this->data->push($soldier[$filter]);
            $this->labels->push($soldier['first_name'].' '.$soldier['last_name']);
        });

        return [
            'data' => $this->data,
            'labels' => $this->labels,
        ];
    }

    protected function getData($course, $month = null, $year = null)
    {
        $month = $month ? Carbon::createFromDate($year, $month, 1) : now()->addMonth();
        $shifts = Shift::whereNotNull('soldier_id')
            ->get()
            ->filter(function (Shift $shift) use ($month): bool {
                $range = new Range(
                    $shift->start_date,
                    $shift->end_date,
                );

                return $range->isSameMonth(new Range($month->copy()->startOfMonth(), $month->copy()->endOfMonth()));
            })
            ->groupBy('soldier_id');
        $soldiersDetails = collect([]);
        $shifts->each(function ($shifts, $soldier_id) use (&$soldiersDetails) {
            $user = User::where('userable_id', $soldier_id)->first();
            $constraints = ConstraintModel::where('soldier_id', $soldier_id)->get();
            $soldier = Soldier::where('id', $soldier_id)->first();
            $soldiersDetails->push([
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'course' => $soldier->course,
                'nights' => $this->howMuchNights($shifts),
                'weekends' => $this->howMuchWeekends($shifts),
                'shifts' => $shifts->count(),
                'points' => $this->howMuchPoints($shifts),
                'constraints' => $constraints,
                'lowConstraintsRejected' => $this->howMuchLowConstraintsRejected($constraints, $shifts),
            ]);
        });
        $soldiersDetails = $soldiersDetails->filter(function ($soldierDetail) use ($course) {
            return $soldierDetail['course'] === (int) $course;
        });

        return $soldiersDetails;

    }

    protected function howMuchNights($shifts)
    {
        return $shifts->filter(fn ($shift) => $shift->task()->withTrashed()->first()->kind === TaskKind::NIGHT->value)->count();
    }

    protected function howMuchWeekends($shifts)
    {
        return $shifts->filter(fn ($shift) => $shift->is_weekend != null ? $shift->is_weekend : ($shift->task()->withTrashed()->first()->kind === TaskKind::WEEKEND->value))->count();
    }

    protected function howMuchPoints($shifts)
    {
        return collect($shifts)->sum(fn ($shift) => $shift->parallel_weight != null ? $shift->parallel_weight : $shift->task()->withTrashed()->first()->parallel_weight);
    }

    protected function howMuchLowConstraintsRejected($constraints, $shifts): int
    {
        $count = 0;
        $constraints->filter(
            fn (ConstraintModel $constraint) => ConstraintType::getPriority()[$constraint->constraint_type->value] == 2
        )
            ->map(
                function ($constraint) use ($count, $shifts) {
                    $shifts->map(function ($shift) use ($constraint, $count) {
                        $range = new Range(
                            $shift->start_date,
                            $shift->end_date,
                        );
                        $range->isConflict(new Range($constraint->start_date, $constraint->end_date)) ?
                            $count++ : $count;
                    });
                }
            );

        return $count;
    }
}
