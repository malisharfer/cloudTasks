<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Services\RecurringEvents;
use Carbon\Carbon;

beforeEach(function () {
    $this->recurringEvents = new RecurringEvents(Carbon::now());
    $this->now = Carbon::now();
});

it('should create shifts for daily recurring', function () {
    $task = Task::factory()->create([
        'name' => 'Daily Task',
        'recurring' => ['type' => 'Daily'],
        'start_hour' => '09:00:00',
        'duration' => 1,
    ]);
    $this->recurringEvents->recurringTask();
    $this->assertDatabaseHas('shifts', [
        'task_id' => $task->id,
        'start_date' => Carbon::parse($this->now->tomorrow()->format('Y-m-d').' '.$task['start_hour']),
        'end_date' => Carbon::parse($this->now->tomorrow()->format('Y-m-d').' '.$task['start_hour'])->addHours($task['duration']),
    ]);
});

it('should create shifts for weekly recurring', function () {
    $task = Task::factory()->create([
        'name' => 'Weekly Task',
        'recurring' => ['type' => 'Weekly', 'days_in_week' => ['Sunday', 'Tuesday']],
        'start_hour' => '10:00:00',
        'duration' => 2,
    ]);
    $expectedShiftDates = collect([
        $this->now->tomorrow()->next('Sunday')->format('Y-m-d'),
        $this->now->tomorrow()->next('Tuesday')->format('Y-m-d'),
    ]);
    $this->recurringEvents->recurringTask();
    $this->assertDatabaseHas('shifts', [
        'task_id' => $task->id,
        'start_date' => Carbon::parse($expectedShiftDates[0].' '.$task['start_hour']),
        'end_date' => Carbon::parse($expectedShiftDates[0].' '.$task['start_hour'])->addHours($task['duration']),
    ]);
    $this->assertDatabaseHas('shifts', [
        'task_id' => $task->id,
        'start_date' => Carbon::parse($expectedShiftDates[1].' '.$task['start_hour']),
        'end_date' => Carbon::parse($expectedShiftDates[1].' '.$task['start_hour'])->addHours($task['duration']),
    ]);
});

it('should create shift for monthly recurring', function () {
    $task = Task::factory()->create([
        'name' => 'Monthly Task',
        'recurring' => ['type' => 'Monthly', 'dates_in_month' => 5],
        'start_hour' => '11:00:00',
        'duration' => 2,
    ]);
    $this->recurringEvents->recurringTask();
    $this->assertDatabaseHas('shifts', [
        'task_id' => $task->id,
        'start_date' => Carbon::create($this->now->year, $this->now->month, 5)->format('Y-m-d').' '.$task['start_hour'],
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
    $this->recurringEvents->recurringTask();
    $this->recurringEvents->recurringTask();
    $this->assertDatabaseCount('shifts', 1);
});

it('should create shifts for custom recurring', function () {
    $task = Task::factory()->create([
        'name' => 'Custom Task',
        'recurring' => ['type' => 'Custom', 'dates_in_month' => [10, 20]],
        'start_hour' => '12:00:00',
        'duration' => 2,
    ]);

    $this->recurringEvents->recurringTask();
    $this->assertDatabaseHas('shifts', [
        'task_id' => $task->id,
        'start_date' => Carbon::create($this->now->year, $this->now->month, 10)->format('Y-m-d').' '.$task['start_hour'],
    ]);
    $this->assertDatabaseCount('shifts', 2);
});

it('should create shift for One time task', function () {
    $task = Task::factory()->create([
        'name' => 'One time Task',
        'recurring' => ['type' => 'One time', 'date' => Carbon::tomorrow()->toDateString()],
        'start_hour' => '13:00:00',
        'duration' => 1,
    ]);

    $this->recurringEvents->oneTimeTask($task);
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

    $this->recurringEvents->dailyRangeTask($task);

    $this->assertDatabaseHas('shifts', [
        'task_id' => $task->id,
        'start_date' => Carbon::parse(Carbon::tomorrow()->toDateString().' '.$task['start_hour']),
        'end_date' => Carbon::parse(Carbon::tomorrow()->toDateString().' '.$task['start_hour'])->addHours($task['duration']),
    ]);
});
