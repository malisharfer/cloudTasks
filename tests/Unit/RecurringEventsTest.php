<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Services\RecurringEvents;
use Carbon\Carbon;

it('should create shifts for daily recurring', function () {
    Task::factory()->create([
        'name' => 'Daily Task',
        'recurring' => ['type' => 'Daily'],
        'start_hour' => '09:00:00',
        'duration' => 1,
    ]);
    $recurringEvents = new RecurringEvents(now());
    $recurringEvents->recurringTask();
    $this->assertDatabaseCount('shifts', now()->lastOfMonth()->day - now()->day);
});

it('should create shifts for weekly recurring', function () {
    Task::factory()->create([
        'name' => 'Weekly Task',
        'recurring' => ['type' => 'Weekly', 'days_in_week' => ['Sunday', 'Monday']],
        'start_hour' => '10:00:00',
        'duration' => 2,
    ]);
    $recurringEvents = new RecurringEvents(now());
    $recurringEvents->recurringTask();
    $period = now()->toPeriod(now()->lastOfMonth()->day - now()->day + 1);
    $this->assertDatabaseCount('shifts',
        collect($period)->filter(fn ($date) => $date->isSunday())->count() - (now()->isSunday() ? 1 : 0)
           + collect($period)->filter(fn ($date) => $date->isMonday())->count() - (now()->isMonday() ? 1 : 0));
});

it('should create shift for monthly recurring', function () {
    $task = Task::factory()->create([
        'name' => 'Monthly Task',
        'recurring' => ['type' => 'Monthly', 'dates_in_month' => 5],
        'start_hour' => '11:00:00',
        'duration' => 2,
    ]);
    $recurringEvents = new RecurringEvents;
    $recurringEvents->recurringTask();
    $this->assertDatabaseHas('shifts', [
        'task_id' => $task->id,
        'start_date' => Carbon::create(now()->addMonth()->year, now()->addMonth()->month, 5)->format('Y-m-d').' '.$task['start_hour'],
    ]);
    $this->assertDatabaseCount('shifts', 1);
});

it('should not create shift that already exists', function () {
    Task::factory()->create([
        'name' => 'Monthly Task',
        'recurring' => ['type' => 'Monthly', 'dates_in_month' => 5],
        'start_hour' => '11:00:00',
        'duration' => 2,
    ]);
    $recurringEvents = new RecurringEvents;
    $recurringEvents->recurringTask();
    $recurringEvents->recurringTask();
    $this->assertDatabaseCount('shifts', 1);
});

it('should create shifts for custom recurring', function () {
    $task = Task::factory()->create([
        'name' => 'Custom Task',
        'recurring' => ['type' => 'Custom', 'dates_in_month' => [10, 20]],
        'start_hour' => '12:00:00',
        'duration' => 2,
    ]);
    $recurringEvents = new RecurringEvents;
    $recurringEvents->recurringTask();
    $this->assertDatabaseCount('shifts', 2);
});

it('should create shift for One time task', function () {
    $task = Task::factory()->create([
        'name' => 'One time Task',
        'recurring' => ['type' => 'One time', 'date' => Carbon::tomorrow()->toDateString()],
        'start_hour' => '13:00:00',
        'duration' => 1,
    ]);
    $recurringEvents = new RecurringEvents;
    $recurringEvents->oneTimeTask($task);
    $date = Carbon::create(
        Carbon::tomorrow()->year,
        Carbon::tomorrow()->month,
        Carbon::tomorrow()->day,
    );
    $this->assertDatabaseHas('shifts', [
        'task_id' => $task->id,
        'start_date' => Carbon::parse($date->format('Y-m-d').' '.$task['start_hour']),
        'end_date' => Carbon::parse($date->format('Y-m-d').' '.$task['start_hour'])->addHours($task['duration']),
    ]);
});

it('should create shifts for Daily range task', function () {
    $task = Task::factory()->create([
        'name' => 'Daily range Task',
        'recurring' => ['type' => 'Daily range', 'start_date' => Carbon::tomorrow()->toDateString(), 'end_date' => Carbon::tomorrow()->addDays(5)->toDateString()],
        'start_hour' => '13:00:00',
        'duration' => 1,
    ]);
    $recurringEvents = new RecurringEvents;
    $recurringEvents->dailyRangeTask($task);

    $this->assertDatabaseHas('shifts', [
        'task_id' => $task->id,
        'start_date' => Carbon::parse(Carbon::tomorrow()->toDateString().' '.$task['start_hour']),
        'end_date' => Carbon::parse(Carbon::tomorrow()->toDateString().' '.$task['start_hour'])->addHours($task['duration']),
    ]);
    $this->assertDatabaseCount('shifts', 6);
});
