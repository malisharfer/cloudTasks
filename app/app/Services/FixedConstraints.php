<?php

namespace App\Services;

use App\Enums\ConstraintType;
use App\Models\Constraint;
use App\Models\Soldier;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class FixedConstraints
{
    public function createFixedConstraints()
    {
        $soldiers = Soldier::where('not_thursday_evening', true)->orWhere('not_sunday_morning', true)->get();
        $soldiers->map(function ($soldier) {
            if ($soldier->not_thursday_evening) {
                $this->getDatesOfDaysInMonth(ConstraintType::NOT_THURSDAY_EVENING->value, $soldier->id);
            }
            if ($soldier->not_sunday_morning) {
                $this->getDatesOfDaysInMonth(ConstraintType::NOT_SUNDAY_MORNING->value, $soldier->id);
            }
        });
    }

    protected function getDatesOfDaysInMonth($constraintType, $soldierId)
    {
        $dates = $this->createPeriod();
        collect($dates)->filter(function ($date) use ($constraintType) {
            return Carbon::parse($date)->dayOfWeek === ($constraintType === 'Not Thursday evening' ? Carbon::THURSDAY : Carbon::SUNDAY);
        })->each(function ($date) use (&$soldierId, &$constraintType): void {
            $dates_of_constaints = $this->setTimeToDate($date, $constraintType);
            $this->createConstraint($constraintType, $soldierId, $dates_of_constaints[0], $dates_of_constaints[1]);
        });
    }

    protected function setTimeToDate($date, $constraintType)
    {
        return match ($constraintType) {
            'Not Thursday evening' => [$date->setTime(14, 00, 0)->toDateTimeString(), $date->setTime(15, 30, 0)->toDateTimeString()],
            'Not Sunday morning' => [$date->setTime(8, 30, 0)->toDateTimeString(), $date->setTime(9, 30, 0)->toDateTimeString()]
        };
    }

    protected function createConstraint($constraintType, $soldierId, $startDate, $endDate)
    {
        $constraint = new Constraint;
        $constraint->soldier_id = $soldierId;
        $constraint->constraint_type = $constraintType;
        $constraint->start_date = $startDate;
        $constraint->end_date = $endDate;
        $constraint->save();
    }

    protected function createPeriod()
    {
        $month = Carbon::now()->addMonth();

        // return CarbonPeriod::between(max($month->copy()->startOfMonth(), Carbon::tomorrow()), $month->copy()->endOfMonth());
        return CarbonPeriod::between($month->copy()->startOfMonth(), $month->copy()->endOfMonth());
    }
}
