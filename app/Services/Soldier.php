<?php

namespace App\Services;

use App\Enums\Availability;
use App\Enums\Priority;
use App\Models\Shift;

class Soldier
{
    public $id;

    public $points_max_data;

    public $shifts_max_data;

    public $nights_max_data;

    public $weekends_max_data;

    public $qualifications;

    public $constraints;

    public $shifts;

    public function __construct($id, MaxData $max_points, MaxData $max_shifts, MaxData $max_nights, MaxData $max_weekends, $qualifications, $constraints)
    {
        $this->id = $id;
        $this->points_max_data = $max_points;
        $this->shifts_max_data = $max_shifts;
        $this->nights_max_data = $max_nights;
        $this->weekends_max_data = $max_weekends;
        $this->qualifications = collect($qualifications);
        $this->constraints = collect($constraints);
        $this->shifts = collect([]);
    }

    public function isQualified(string $task_name): bool
    {
        return $this->qualifications->contains($task_name);
    }

    public function isAbleTake(\App\Services\Shift $shift): bool
    {
        return $this->isAvailableByMaxes($shift) && $this->isAvailableByShifts($shift->range);
    }

    public function isAvailableByMaxes(\App\Services\Shift $shift): bool
    {
        if ($shift->isWeekend() && $this->weekends_max_data->remaining() < 1) {
            return false;
        }
        if ($shift->isNight() && $this->nights_max_data->remaining() < 1) {
            return false;
        }

        return $this->points_max_data->remaining() >= $shift->points
            && $this->shifts_max_data->remaining() >= 1;
    }

    protected function isAvailableByShifts(Range $range): bool
    {
        return ! $this->shifts->contains(function ($shift) use ($range) {
            return $shift->range->isConflict($range);
        });
    }

    public function isAvailableByConstraints(Range $range): Availability
    {
        $conflicts = $this->constraints->filter(function ($constraint) use ($range) {
            return $constraint->range->isConflict($range);
        });

        if ($conflicts->isEmpty()) {
            return Availability::YES;
        }

        if (
            $conflicts->contains(function ($conflict) {
                return $conflict->priority == Priority::HIGH;
            })
        ) {
            return Availability::NO;
        }

        return Availability::BETTER_NOT;
    }

    public function assign(Shift $shift): void
    {
        $this->shifts->push($shift);
        $this->points_max_data->used += $shift->points;
        $this->shifts_max_data->used += 1;
        if ($shift->is_weekend()) {
            $this->weekends_max_data->used += 1;
        } elseif ($shift->is_night()) {
            $this->nights_max_data->used += 1;
        }
    }
}