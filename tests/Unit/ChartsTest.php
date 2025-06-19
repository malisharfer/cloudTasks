<?php

use App\Enums\TaskKind;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\Task;
use App\Models\User;
use App\Services\Charts;

it('should organize chart data correctly', function () {
    $soldier1 = Soldier::factory()->create([
        'course' => 1,
        'max_shifts' => 3,
        'max_nights' => 3,
        'max_weekends' => 3,
        'capacity' => 3,
        'qualifications' => ['run'],

    ]);

    Shift::factory()->create([
        'soldier_id' => User::factory()->create([
            'userable_id' => $soldier1->id,
        ])->userable_id,
        'parallel_weight' => 0.1,
        'start_date' => now()->addMonth(),
        'end_date' => now()->addMonth()->addHour(),
        'task_id' => Task::factory()->create([
            'type' => 'run',
            'kind' => TaskKind::REGULAR->value,
        ])->id,
    ]);

    $soldier2 = Soldier::factory()->create([
        'course' => 1,
        'max_shifts' => 3,
        'max_nights' => 3,
        'max_weekends' => 3,
        'capacity' => 3,
        'qualifications' => ['run'],

    ]);

    Shift::factory()->create([
        'soldier_id' => User::factory()->create([
            'userable_id' => $soldier2->id,
        ])->userable_id,
        'parallel_weight' => 0.2,
        'start_date' => now()->addMonth()->addHour(),
        'end_date' => now()->addMonth()->addHours(2),
        'task_id' => Task::factory()->create([
            'type' => 'run',
            'kind' => TaskKind::REGULAR->value,
        ])->id,
    ]);

    $charts = new Charts;
    $result = $charts->organizeChartData('points', 1, now()->addMonth()->month, now()->addMonth()->year);
    expect($result)->toHaveKey('labels')->toBeArray();
    expect($result)->toHaveKey('data')->toBeArray();
});
