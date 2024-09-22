<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Services\ReccurenceEvents;
use Carbon\Carbon;

beforeEach(function () {
    $this->recurrenceEvents = new ReccurenceEvents;
    $this->now = Carbon::now()->addMonth();
});

it('should create shifts for daily recurrence', function () {
    $task = Task::factory()->create([
        'name' => 'Daily Task',
        'recurrence' => ['type' => 'Daily'],
        'start_hour' => '09:00:00',
        'duration' => 1,
    ]);
    $this->recurrenceEvents->recurrenceTask();
    $this->assertDatabaseHas('shifts', [
        'task_id' => $task->id,
        'start_date' => Carbon::parse($this->now->startOfMonth()->format('Y-m-d').' '.$task['start_hour']),
        'end_date' => Carbon::parse($this->now->startOfMonth()->format('Y-m-d').' '.$task['start_hour'])->addHours($task['duration']),

    ]);
});

it('should create shifts for weekly recurrence', function () {
    $task = Task::factory()->create([
        'name' => 'Weekly Task',
        'recurrence' => ['type' => 'Daily'],
        'start_hour' => '10:00:00',
        'duration' => 2,
    ]);
    $this->recurrenceEvents->recurrenceTask();
    $this->assertDatabaseHas('shifts', [
        'task_id' => $task->id,
        'start_date' => Carbon::parse($this->now->startOfMonth()->format('Y-m-d').' '.$task['start_hour']),
        'end_date' => Carbon::parse($this->now->startOfMonth()->format('Y-m-d').' '.$task['start_hour'])->addHours($task['duration']),
    ]);
});

it('should create shifts for monthly recurrence', function () {
    $task = Task::factory()->create([
        'name' => 'Monthly Task',
        'recurrence' => ['type' => 'Monthly', 'dates_in_month' => 5],
        'start_hour' => '11:00:00',
        'duration' => 2,
    ]);
    $this->recurrenceEvents->recurrenceTask();
    $this->assertDatabaseHas('shifts', [
        'task_id' => $task->id,
        'start_date' => Carbon::create($this->now->year, $this->now->month, 5)->format('Y-m-d').' '.$task['start_hour'],
    ]);
    $this->assertDatabaseCount('shifts', 1);
});

it('should create shifts for custom recurrence', function () {
    $task = Task::factory()->create([
        'name' => 'Custom Task',
        'recurrence' => ['type' => 'Custom', 'dates_in_month' => [10, 20]],
        'start_hour' => '12:00:00',
        'duration' => 2,
    ]);

    $this->recurrenceEvents->recurrenceTask();
    $this->assertDatabaseHas('shifts', [
        'task_id' => $task->id,
        'start_date' => Carbon::create($this->now->year, $this->now->month, 10)->format('Y-m-d').' '.$task['start_hour'],
    ]);
    $this->assertDatabaseCount('shifts', 2);
});

it('should create shifts for OneTime task', function () {
    $task = Task::factory()->create([
        'name' => 'OneTime Task',
        'recurrence' => ['type' => 'OneTime', 'start_date' => '2022-12-01', 'end_date' => '2022-12-05'],
        'start_hour' => '13:00:00',
        'duration' => 1,
    ]);

    $this->recurrenceEvents->oneTimeTask($task);

    $this->assertDatabaseHas('shifts', [
        'task_id' => $task->id,
        'start_date' => Carbon::parse('2022-12-01'.' '.$task['start_hour']),
        'end_date' => Carbon::parse('2022-12-01'.' '.$task['start_hour'])->addHours($task['duration']),
    ]);
});
