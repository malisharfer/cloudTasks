<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\Task;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class RecurringEvents
{
    protected $task = Task::class;

    protected $month;

    public function __construct($month = null)
    {
        $this->month = $month ? Carbon::parse($month) : Carbon::now()->addMonth();
    }

    public function setMonth($month)
    {
        $this->month = Carbon::parse($month);
    }

    public function recurringTask(): void
    {

        $tasks = Task::get();
        $tasks->filter(function ($task) {
            return $task->recurring['type'] !== 'Daily range' && $task->recurring['type'] !== 'One time';
        })->map(fn ($task) => $this->switchTasks($task));
    }

    public function oneTimeTask(Task $task)
    {
        $this->task = $task;
        $dates = $this->addTimeToDate(Carbon::parse($this->task['recurring']['date']));
        $this->createShifts([$dates]);
    }

    public function dailyRangeTask(Task $task)
    {
        $this->task = $task;
        $dates = $this->getDatesOfMonth();
        $this->createShifts($dates);
    }

    protected function switchTasks(Task $task): void
    {
        $this->task = $task;
        $dates = match ($this->task->recurring['type']) {
            'Daily' => $this->dailyRecurring(),
            'Weekly' => $this->weeklyRecurring(),
            'Monthly' => $this->monthlyRecurring(),
            'Custom' => $this->customRecurring(),
        };

        $this->createShifts($dates);
    }

    protected function dailyRecurring()
    {
        return $this->getDatesOfMonth();
    }

    protected function weeklyRecurring()
    {
        return $this->getDatesOfDaysInMonth($this->task['recurring']['days_in_week']);
    }

    protected function monthlyRecurring()
    {
        $dates[] = $this->task['recurring']['dates_in_month'];

        return $this->convertNumbersToDatesInMonth($dates);
    }

    protected function customRecurring()
    {
        return $this->convertNumbersToDatesInMonth($this->task['recurring']['dates_in_month']);
    }

    protected function getDatesOfMonth()
    {
        $period = $this->createPeriod();

        return collect($period)->map(function ($date) {
            return $this->addTimeToDate($date);
        })->all();
    }

    protected function addTimeToDate($date)
    {
        return Carbon::parse($date->format('Y-m-d').' '.$this->task['start_hour']);
    }

    protected function calculateEndDateTime($startDate)
    {
        $endDate = $this->addTimeToDate($startDate)
            ->addHours((float) $this->task['duration']);

        return $endDate;
    }

    protected function getDatesOfDaysInMonth($daysArray)
    {
        $period = $this->createPeriod();

        return collect($period)->filter(function ($date) use ($daysArray) {
            return in_array($date->englishDayOfWeek, $daysArray);
        })->map(function ($date) {
            return $this->addTimeToDate($date);
        })->all();
    }

    protected function convertNumbersToDatesInMonth($dayNumbers)
    {
        return collect($dayNumbers)->map(function ($day) {
            return $this->addTimeToDate(Carbon::create($this->month->year, $this->month->month, $day));
        })->all();
    }

    protected function createPeriod()
    {
        return $this->task->recurring['type'] == 'Daily range' ?
            CarbonPeriod::between($this->task['recurring']['start_date'], $this->task['recurring']['end_date']) :
            CarbonPeriod::between($this->month->copy()->startOfMonth(), $this->month->copy()->endOfMonth());
    }

    protected function createShifts(array $dates)
    {
        collect($dates)->map(function ($date) {
            if (
                ! Shift::where('task_id', '=', $this->task['id'])
                    ->where('start_date', $date)
                    ->where('end_date', $this->calculateEndDateTime($date))
                    ->get()
                    ->first()
                && checkdate($date->month, $date->day, $date->year)
            ) {
                $shift = new Shift;
                $shift->start_date = $date;
                $shift->end_date = $this->calculateEndDateTime($date);
                $shift->task_id = $this->task['id'];
                $shift->save();
            }
        });
    }
}
