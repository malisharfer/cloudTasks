<?php

namespace App\Services;

use App\Enums\DaysInWeek;
use Carbon\Carbon;
use Exception;

class Range
{
    public $start;

    public $end;

    public function __construct($start, $end)
    {
        if (Carbon::parse($start)->isAfter(Carbon::parse($end))) {
            new Exception('Invalid range');
        }
        $this->start = Carbon::parse($start)->setTimezone('Asia/Jerusalem');
        $this->end = Carbon::parse($end)->setTimezone('Asia/Jerusalem');
    }

    public function isConflict(Range $other): bool
    {
        return $this->start->isBefore($other->end) && $other->start->isBefore($this->end);
    }

    public function isSameMonth(Range $other): bool
    {
        return $this->isConflict($other);
    }

    public function isPass(): bool
    {
        return Carbon::now()->greaterThan($this->start) || Carbon::now()->greaterThan($this->end);
    }

    public function isRangeInclude(DaysInWeek $dayInWeek): bool
    {
        $startDayIndex = $this->start->dayOfWeek;
        $endDayIndex = $this->end->dayOfWeek;
        $checkDayIndex = date('N', strtotime($dayInWeek->value));
        if ($startDayIndex <= $endDayIndex) {
            return $this->start->copy()->diffInDays($this->end->copy()) > 5 || ($checkDayIndex >= $startDayIndex && $checkDayIndex <= $endDayIndex);
        }

        return $checkDayIndex >= $startDayIndex || $checkDayIndex <= $endDayIndex;
    }

    public function getDayAfterWeekend(): Range
    {
        $nextDayAfterWeekend = $this->end->englishDayOfWeek == DaysInWeek::SUNDAY->value ? $this->end->copy() : $this->end->copy()->next(DaysInWeek::SUNDAY->value)->setTime(8, 0);

        return new Range($nextDayAfterWeekend, $nextDayAfterWeekend->copy()->addDay());
    }

    public function getNightSpaces()
    {
        $startHour = $this->start->hour;
        $endTomorrow = $this->end->copy()->addDay();
        $startSpaceDay = ($startHour >= '0' && $startHour <= '3') ?
            $this->start->copy()->subDay():
            $this->start->copy();
        return [
            new Range($startSpaceDay->setHour(00), $this->start),
            new Range($this->end,$endTomorrow->setHour(7)->setminutes(59)),
        ];
    }

    public function getNightInWeekendSpaces()
    {
        $startSpace = $this->start->englishDayOfWeek == DaysInWeek::THURSDAY->value ?
        $this->start->copy()->setHour(00)->setMinutes(00) :
        $this->start->copy()->setHour(8)->setMinutes(30);

        return [
            new Range($startSpace, $this->start->copy()),
            new Range($this->end->copy(), $this->end->copy()->setHour(19)->setMinutes(59)),
        ];
    }

    public function getAlertSpaces()
    {
        $startHour = $this->start->hour;
        $endTomorrow = $this->end->copy()->addDay();
        $spaceStartDay = ($startHour >= '0' && $startHour <= '3') ?
            $this->start->copy()->subDay():
            $this->start->copy();
        return [
            new Range($spaceStartDay->copy()->setHour(00)->setMinutes(00), $this->start),
            new Range($endTomorrow->copy()->setHour(00)->setMinutes(00), $endTomorrow->copy()->setHour(7)->setMinutes(59)),
        ];
    }

    public function getThursdaySpace()
    {
        return new Range($this->end, $this->end->copy()->next(Carbon::SUNDAY)->setHour(7)->setMinutes(59));        
    }

}
