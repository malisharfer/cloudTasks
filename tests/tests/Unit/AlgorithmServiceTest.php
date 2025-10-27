<?php

use App\Enums\TaskKind;
use App\Models\Constraint;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\Task;
use App\Services\Algorithm;

it('should return only unassigned shifts', function () {
    $reflection = new ReflectionClass(Algorithm::class);
    $method = $reflection->getMethod('getShiftWithTasks');
    $method->setAccessible(true);
    $task = Task::factory()->create(['kind' => TaskKind::REGULAR->value]);
    Shift::factory()->count(3)->create(['task_id' => $task->id]);
    expect($method->invoke(new Algorithm))->toBeEmpty();
});

it('should return shifts with their task details', function () {
    $reflection = new ReflectionClass(Algorithm::class);
    $method = $reflection->getMethod('getShiftWithTasks');
    $method->setAccessible(true);
    $task = Task::factory()->create(['kind' => TaskKind::REGULAR->value]);
    Shift::factory()->count(3)->create([
        'task_id' => $task->id,
        'soldier_id' => null,
        'is_weekend' => false,
        'start_date' => now()->addMonth(),
        'end_date' => now()->addMonth()->addDay(),
    ]);
    $shiftsWithTasks = $method->invoke(new Algorithm);
    $shiftsWithTasks->each(fn ($shift) => expect($shift->kind)->toBe($task->kind));
    expect($shiftsWithTasks->count())->toBe(3);
});

it('should return only non-reserve soldiers', function () {
    $reflection = new ReflectionClass(Algorithm::class);
    $method = $reflection->getMethod('getSoldiersDetails');
    $method->setAccessible(true);
    Soldier::factory()->create([
        'is_reservist' => true,
    ]);
    $soldiers = $method->invoke(new Algorithm);
    expect($soldiers)->toBeEmpty();
});

it('should return soldiers with their constraints details', function () {
    $reflection = new ReflectionClass(Algorithm::class);
    $method = $reflection->getMethod('getSoldiersDetails');
    $method->setAccessible(true);
    $soldier = Soldier::factory()->create([
        'is_reservist' => false,
        'qualifications' => ['Run'],
    ]);
    Constraint::factory()->count(3)->create([
        'soldier_id' => $soldier->id,
        'constraint_type' => 'Not task',
        'start_date' => now()->addMonth(),
        'end_date' => now()->addMonth()->addDay(),
    ]);
    $soldierWithConstraints = $method->invoke(new Algorithm)->values()->all();
    expect($soldierWithConstraints[0]->constraints->count())->toBe(3);
});
