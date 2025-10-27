<?php

use App\Enums\Availability;
use App\Enums\Priority;
use App\Enums\TaskKind;
use App\Models\Shift as ShiftModel;
use App\Models\Task;
use App\Services\Constraint;
use App\Services\Helpers;
use App\Services\MaxData;
use App\Services\Range;
use App\Services\Shift;
use App\Services\Soldier;
use Carbon\Carbon;

it('should return true if the soldier able to take the shift', function () {
    $soldier = new Soldier(1, 1, new MaxData(2.75), new MaxData(12), new MaxData(5), new MaxData(3), new MaxData(5), new MaxData(3), ['Run'], []);
    $shift = new Shift(1, 0, 'Run', Carbon::create(2024, 5, 14, 17), Carbon::create(2024, 5, 14, 18), 2, TaskKind::REGULAR->value, []);
    expect($soldier->isAbleTake($shift, []))->toBeTrue();
});

it('should return false if the soldier cant take the shift', function () {
    $soldier = new Soldier(1, 1, new MaxData(2.75), new MaxData(12), new MaxData(5), new MaxData(3), new MaxData(5), new MaxData(3), ['Run'], []);
    $shift = new Shift(1, 0, 'Run', Carbon::create(2024, 5, 14, 17), Carbon::create(2024, 5, 14, 18), 4, TaskKind::REGULAR->value, []);
    expect($soldier->isAbleTake($shift, []))->toBeFalse();
});

it('should return true if the soldier available by maxes', function () {
    $soldier = new Soldier(1, 1, new MaxData(2.75), new MaxData(12), new MaxData(5), new MaxData(3), new MaxData(5), new MaxData(3), ['Run'], []);
    $shift = new Shift(1, 0, 'Run', Carbon::create(2024, 5, 14, 17), Carbon::create(2024, 5, 14, 18), 1, TaskKind::REGULAR->value, []);
    $soldier->isAvailableByMaxes($shift);
    expect($soldier->isAvailableByMaxes($shift))->toBeTrue();
});

it('should return false if the soldier is not available by maxes', function () {
    $soldier = new Soldier(1, 1, new MaxData(2.75), new MaxData(12), new MaxData(0), new MaxData(3), new MaxData(5), new MaxData(3), ['Run'], []);
    $shift = new Shift(1, 0, 'Run', Carbon::create(2024, 5, 14, 17), Carbon::create(2024, 5, 14, 21), 1, TaskKind::NIGHT->value, []);
    expect($soldier->isAvailableByMaxes($shift))->toBeFalse();
});

it('should return true if the soldier is available by shifts', function () {
    $shift = new Shift(1, 1, 'Run', Carbon::create(2024, 5, 14, 19), Carbon::create(2024, 5, 14, 21), 1, TaskKind::NIGHT->value, []);
    $soldier = new Soldier(1, 0, new MaxData(2.75), new MaxData(12), new MaxData(0), new MaxData(3), new MaxData(5), new MaxData(3), ['Run'], []);
    $soldier->assign($shift, []);
    $shift = new Shift(1, 0, 'go', Carbon::create(2024, 5, 14, 16), Carbon::create(2024, 5, 14, 18), 0, TaskKind::REGULAR->value, []);
    expect($soldier->isAvailableByShifts($shift))->toBeTrue();
});

it('should return false if the soldier is not available by shifts', function () {
    $shift = new Shift(1, 1, 'Run', Carbon::create(2024, 5, 14, 17), Carbon::create(2024, 5, 14, 21), 1, TaskKind::NIGHT->value, []);
    $soldier = new Soldier(1, 0, new MaxData(2.75), new MaxData(12), new MaxData(0), new MaxData(3), new MaxData(5), new MaxData(3), ['Run'], []);
    $soldier->assign($shift, []);
    $shift = new Shift(1, 0, 'go', Carbon::create(2024, 5, 14, 16), Carbon::create(2024, 5, 14, 18), 0, TaskKind::REGULAR->value, []);
    expect($soldier->isAvailableByShifts($shift))->toBeFalse();
});

it('should return true if the soldier is available by spaces', function () {
    $shifts = [new Shift(2, 0, '', '2025-01-05', '2025-01-06', 0, TaskKind::REGULAR->value, [])];
    $spaces = [new Range('2025-01-02', '2025-01-03')];
    $soldier = new Soldier(1, 1, new MaxData(2.75), new MaxData(12), new MaxData(5), new MaxData(3), new MaxData(3), new MaxData(3), [], [], $shifts, []);
    expect($soldier->isAvailableBySpaces($spaces))->toBeTrue();
});

it('should return false if the soldier is not available by spaces', function () {
    $spaces = [new Range('2025-01-04', '2025-01-07')];
    $shifts = [new Shift(2, 0, 'run', '2025-01-05', '2025-01-06', 0, TaskKind::REGULAR->value, [])];
    $soldier = new Soldier(1, 1, new MaxData(2.75), new MaxData(12), new MaxData(5), new MaxData(3), new MaxData(5), new MaxData(3), [], [], $shifts, []);
    expect($soldier->isAvailableBySpaces($spaces))->toBeFalse();
});

it('should return true if the soldier is available by concurrents shifts', function () {
    $shift = ShiftModel::factory()->create(['start_date' => '2025-01-08', 'end_date' => '2025-01-09', 'task_id' => Task::factory()->create(['type' => 'sing', 'kind' => TaskKind::INPARALLEL->value, 'concurrent_tasks' => ['run']])->id]);
    $concurrentsShifts = [Helpers::buildShift($shift)];
    $shift = new Shift(2, 0, 'run', '2025-01-08', '2025-01-09', 0, TaskKind::REGULAR->value, []);
    $soldier = new Soldier(1, 1, new MaxData(2.75), new MaxData(12), new MaxData(5), new MaxData(3), new MaxData(5), new MaxData(3), [], [], [], $concurrentsShifts);
    expect($soldier->isAvailableByConcurrentsShifts($shift))->toBeTrue();
});

it('should return false if the soldier is not available by concurrents shifts', function () {
    $shift = ShiftModel::factory()->create(['start_date' => '2025-01-08', 'end_date' => '2025-01-09', 'task_id' => Task::factory()->create(['type' => 'sing',  'kind' => TaskKind::INPARALLEL->value, 'concurrent_tasks' => ['run']])->id]);
    $concurrentsShifts = [Helpers::buildShift($shift)];
    $shift = new Shift(2, 0, 'go', '2025-01-08', '2025-01-09', 0, TaskKind::REGULAR->value, []);
    $soldier = new Soldier(1, 1, new MaxData(2.75), new MaxData(12), new MaxData(5), new MaxData(3), new MaxData(5), new MaxData(3), [], [], [], $concurrentsShifts);
    expect($soldier->isAvailableByConcurrentsShifts($shift))->toBeFalse();
});

it('should return better not availability if the soldier is not available by constraint in low priority', function () {
    $soldier = new Soldier(1, 1, new MaxData(2.75), new MaxData(12), new MaxData(0), new MaxData(3), new MaxData(3), new MaxData(3), ['Run'], [new Constraint(Carbon::create(2024, 5, 14, 20), Carbon::create(2024, 5, 15, 15), Priority::LOW)]);
    expect($soldier->isAvailableByConstraints(new Range(Carbon::create(2024, 5, 14, 22), Carbon::create(2024, 5, 15, 8))))->toBe(Availability::BETTER_NOT);
});

it('should return no availability if the soldier is not available by constraint in high priority', function () {
    $soldier = new Soldier(1, 1, new MaxData(2.75), new MaxData(12), new MaxData(0), new MaxData(3), new MaxData(3), new MaxData(3), ['Run'], [new Constraint(Carbon::create(2024, 5, 14, 20), Carbon::create(2024, 5, 15, 15), Priority::HIGH)]);
    expect($soldier->isAvailableByConstraints(new Range(Carbon::create(2024, 5, 14, 22), Carbon::create(2024, 5, 15, 8))))->toBe(Availability::NO);
});

it('should return yes availability if the soldier has no constraints', function () {
    $soldier = new Soldier(1, 1, new MaxData(2.75), new MaxData(12), new MaxData(0), new MaxData(3), new MaxData(3), new MaxData(3), ['Run'], []);
    expect($soldier->isAvailableByConstraints(new Range(Carbon::create(2024, 5, 14, 22), Carbon::create(2024, 5, 15, 8))))->toBe(Availability::YES);
});

it('should assign shift to soldier and update all details', function () {
    $soldier = new Soldier(1, 1, new MaxData(2.75), new MaxData(12), new MaxData(0), new MaxData(3), new MaxData(3), new MaxData(3), ['Run'], []);
    $shift = new Shift(1, 0, 'Run', Carbon::create(2024, 5, 14, 17), Carbon::create(2024, 5, 14, 18), 2.75, TaskKind::NIGHT->value, []);
    $soldier->assign($shift, []);
    expect($soldier->shifts->count())->toBe(1);
    expect($soldier->pointsMaxData->used)->toBe(2.75);
    expect($soldier->shiftsMaxData->used)->toBe(1.0);
    expect($soldier->weekendsMaxData->used)->toBe(0.0);
    expect($soldier->nightsMaxData->used)->toBe(1.0);
});

it('should return true if the soldier qualified to the task', function () {
    $soldier = new Soldier(1, 1, new MaxData(2.75), new MaxData(12), new MaxData(0), new MaxData(3), new MaxData(3), new MaxData(3), ['Run'], []);
    expect($soldier->isQualified('Run'))->toBeTrue();
});

it('should return false if the soldier is not qualified to the task', function () {
    $soldier = new Soldier(1, 1, new MaxData(2.75), new MaxData(12), new MaxData(0), new MaxData(3), new MaxData(3), new MaxData(3), ['Run'], []);
    expect($soldier->isQualified('Clean'))->toBeFalse();
});
