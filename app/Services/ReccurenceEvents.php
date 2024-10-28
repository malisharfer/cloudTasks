<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\Task;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ReccurenceEvents
{
    protected $task = Task::class;

    public function __construct() {}

    public function recurrenceTask()
    {
        $tasks = Task::get();
        $tasks->filter(function ($task) {
            return $task->recurrence['type'] !== 'OneTime';
        })->map(fn ($task) => $this->swichTasks($task));
    }

    public function oneTimeTask(Task $task)
    {
        $this->task = $task;
        $dates = $this->getDatesOfMonth();
        $this->createShifts($dates);
    }

    protected function swichTasks(Task $task): void
    {
        $this->task = $task;
        $dates = match ($this->task->recurrence['type']) {
            'Daily' => $this->dailyRecurrence(),
            'Weekly' => $this->weeklyRecurrence(),
            'Monthly' => $this->monthlyRecurrence(),
            'Custom' => $this->customRecurrence(),
        };
        $this->createShifts($dates);
    }

    protected function dailyRecurrence()
    {
        return $this->getDatesOfMonth();
    }

    protected function weeklyRecurrence()
    {
        return $this->getDatesOfDaysInMonth($this->task['recurrence']['days_in_week']);
    }

    protected function monthlyRecurrence()
    {
        $dates[] = $this->task['recurrence']['dates_in_month'];

        return $this->convertNumbersToDatesInMonth($dates);
    }

    protected function customRecurrence()
    {
        return $this->convertNumbersToDatesInMonth($this->task['recurrence']['dates_in_month']);
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
            return $this->addTimeToDate(Carbon::create(Carbon::now()->addMonth()->year, Carbon::now()->addMonth()->month, $day));
        })->all();
    }

    protected function createPeriod()
    {
        return $this->task->recurrence['type'] == 'OneTime' ?
            CarbonPeriod::between($this->task['recurrence']['start_date'], $this->task['recurrence']['end_date']) :
            CarbonPeriod::between(Carbon::now()->addMonth()->startOfMonth(), Carbon::now()->addMonth()->endOfMonth());
    }

    protected function createShifts(array $dates)
    {
        collect($dates)->map(function ($date) {
            $shift = new Shift;
            $shift->parallel_weight = $this->task['parallel_weight'];
            $shift->start_date = $date;
            $shift->end_date = $this->calculateEndDateTime($date);
            $shift->task_id = $this->task['id'];
            $shift->save();
        });
    }
}
