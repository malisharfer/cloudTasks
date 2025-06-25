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
        if ($startHour >= '20' && $startHour <= '23') {
            return [
                new Range(Carbon::create($this->start->year, $this->start->month, $this->start->day, 00, 00), $this->start),
                new Range($this->end, Carbon::create($endTomorrow->year, $endTomorrow->month, $endTomorrow->day, 7, 59)),
            ];
        }
        if ($startHour >= '0' && $startHour <= '1') {
            $startYesterday = $this->start->copy()->subDay();

            return [
                new Range(Carbon::create($startYesterday->year, $startYesterday->month, $startYesterday->day, 00, 00), $this->start),
                new Range($this->end, Carbon::create($endTomorrow->year, $endTomorrow->month, $endTomorrow->day, 7, 59)),
            ];
        }
    }

    public function getNightInWeekendSpaces()
    {
        return [
            new Range($this->start->copy()->setHour(8)->setMinutes(30), $this->start->copy()),
            new Range($this->end->copy(), $this->end->copy()->setHour(19)->setMinutes(59)),
        ];
    }
}
