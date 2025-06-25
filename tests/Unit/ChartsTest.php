<?php

use App\Enums\TaskKind;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\Task;
use App\Models\User;
use App\Services\Charts;

it('should return data by the chart parameters', function () {
    $soldier1 = User::factory()->create([
        'userable_id' => Soldier::factory()->create(['course' => 1, 'max_alerts' => 2])->id,
    ]);
    $soldier2 = User::factory()->create([
        'userable_id' => Soldier::factory()->create(['course' => 1, 'max_alerts' => 3])->id,
    ]);
    $soldier3 = User::factory()->create([
        'userable_id' => Soldier::factory()->create(['course' => 1, 'max_alerts' => 3])->id,
    ]);
    $task = Task::factory()->create(['kind' => TaskKind::ALERT->value]);
    Shift::factory()->create([
        'task_id' => $task->id,
        'soldier_id' => $soldier1->userable_id,
        'start_date' => now('Asia/Jerusalem'),
        'end_date' => now('Asia/Jerusalem')->addMinutes(50),
        'is_weekend' => null,
    ]);
    Shift::factory()->create([
        'task_id' => $task->id,
        'soldier_id' => $soldier1->userable_id,
        'start_date' => now('Asia/Jerusalem')->addHour(),
        'end_date' => now('Asia/Jerusalem')->addHours(2),
        'is_weekend' => null,
    ]);
    Shift::factory()->create([
        'task_id' => $task->id,
        'soldier_id' => $soldier2->userable_id,
        'start_date' => now('Asia/Jerusalem'),
        'end_date' => now('Asia/Jerusalem')->addMinutes(50),
        'is_weekend' => null,
    ]);
    Shift::factory()->create([
        'task_id' => $task->id,
        'soldier_id' => $soldier3->userable_id,
        'start_date' => now('Asia/Jerusalem'),
        'end_date' => now('Asia/Jerusalem')->addMinutes(50),
        'is_weekend' => null,
    ]);
    $chart = new Charts(1, now()->year, now()->month, TaskKind::ALERT->value);
    $data = $chart->getData();
    expect($data->count())->toBe(2);
});
