<?php

use App\Enums\Availability;
use App\Enums\Priority;
use App\Services\Constraint;
use App\Services\MaxData;
use App\Services\Range;
use App\Services\Shift;
use App\Services\Soldier;
use Carbon\Carbon;

it('should return true if the soldier able to take the shift', function () {
    $soldier = new Soldier(1, new MaxData(2.75), new MaxData(12), new MaxData(5), new MaxData(3), ['Run'], []);
    $shift = new Shift(1, 'Run', Carbon::create(2024, 5, 14, 17), Carbon::create(2024, 5, 14, 18), 2, false, false);
    expect($soldier->isAbleTake($shift, []))->toBeTrue();
});

it('should return false if the soldier cant take the shift', function () {
    $soldier = new Soldier(1, new MaxData(2.75), new MaxData(12), new MaxData(5), new MaxData(3), ['Run'], []);
    $shift = new Shift(1, 'Run', Carbon::create(2024, 5, 14, 17), Carbon::create(2024, 5, 14, 18), 4, false, false);
    expect($soldier->isAbleTake($shift, []))->toBeFalse();
});

it('should return true if the soldier available by maxes', function () {
    $reflection = new ReflectionClass(Soldier::class);
    $method = $reflection->getMethod('isAvailableByMaxes');
    $method->setAccessible(true);
    $soldier = new Soldier(1, new MaxData(2.75), new MaxData(12), new MaxData(5), new MaxData(3), ['Run'], []);
    $shift = new Shift(1, 'Run', Carbon::create(2024, 5, 14, 17), Carbon::create(2024, 5, 14, 18), 1, false, false);
    expect($method->invoke($soldier, $shift))->toBeTrue();
});

it('should return false if the soldier is not available by maxes', function () {
    $reflection = new ReflectionClass(Soldier::class);
    $method = $reflection->getMethod('isAvailableByMaxes');
    $method->setAccessible(true);
    $soldier = new Soldier(1, new MaxData(2.75), new MaxData(12), new MaxData(0), new MaxData(3), ['Run'], []);
    $shift = new Shift(1, 'Run', Carbon::create(2024, 5, 14, 17), Carbon::create(2024, 5, 14, 21), 1, true, false);
    expect($method->invoke($soldier, $shift))->toBeFalse();
});

it('should return true if the soldier is available by shifts', function () {
    $reflection = new ReflectionClass(Soldier::class);
    $method = $reflection->getMethod('isAvailableByShifts');
    $method->setAccessible(true);
    $shift = new Shift(1, 'Run', Carbon::create(2024, 5, 14, 19), Carbon::create(2024, 5, 14, 21), 1, true, false);
    $soldier = new Soldier(1, new MaxData(2.75), new MaxData(12), new MaxData(0), new MaxData(3), ['Run'], []);
    $soldier->assign($shift, []);
    $range = new Range(Carbon::create(2024, 5, 14, 16), Carbon::create(2024, 5, 14, 18));
    expect($method->invoke($soldier, $range))->toBeTrue();
});

it('should return false if the soldier is not available by shifts', function () {
    $reflection = new ReflectionClass(Soldier::class);
    $method = $reflection->getMethod('isAvailableByShifts');
    $method->setAccessible(true);
    $shift = new Shift(1, 'Run', Carbon::create(2024, 5, 14, 17), Carbon::create(2024, 5, 14, 21), 1, true, false);
    $soldier = new Soldier(1, new MaxData(2.75), new MaxData(12), new MaxData(0), new MaxData(3), ['Run'], []);
    $soldier->assign($shift, []);
    $range = new Range(Carbon::create(2024, 5, 14, 16), Carbon::create(2024, 5, 14, 18));
    expect($method->invoke($soldier, $range))->toBeFalse();
});

it('should return better not availability if the soldier is not available by constraint in low priority', function () {
    $soldier = new Soldier(1, new MaxData(2.75), new MaxData(12), new MaxData(0), new MaxData(3), ['Run'], [new Constraint(Carbon::create(2024, 5, 14, 20), Carbon::create(2024, 5, 15, 15), Priority::LOW)]);
    expect($soldier->isAvailableByConstraints(new Range(Carbon::create(2024, 5, 14, 22), Carbon::create(2024, 5, 15, 8))))->toBe(Availability::BETTER_NOT);
});

it('should return no availability if the soldier is not available by constraint in high priority', function () {
    $soldier = new Soldier(1, new MaxData(2.75), new MaxData(12), new MaxData(0), new MaxData(3), ['Run'], [new Constraint(Carbon::create(2024, 5, 14, 20), Carbon::create(2024, 5, 15, 15), Priority::HIGH)]);
    expect($soldier->isAvailableByConstraints(new Range(Carbon::create(2024, 5, 14, 22), Carbon::create(2024, 5, 15, 8))))->toBe(Availability::NO);
});

it('should return yes availability if the soldier has no constraints', function () {
    $soldier = new Soldier(1, new MaxData(2.75), new MaxData(12), new MaxData(0), new MaxData(3), ['Run'], []);
    expect($soldier->isAvailableByConstraints(new Range(Carbon::create(2024, 5, 14, 22), Carbon::create(2024, 5, 15, 8))))->toBe(Availability::YES);
});

it('should assign shift to soldier and update all details', function () {
    $soldier = new Soldier(1, new MaxData(2.75), new MaxData(12), new MaxData(0), new MaxData(3), ['Run'], []);
    $shift = new Shift(1, 'Run', Carbon::create(2024, 5, 14, 17), Carbon::create(2024, 5, 14, 21), 2.75, true, false);
    $soldier->assign($shift, []);
    expect($soldier->shifts->count())->toBe(1);
    expect($soldier->pointsMaxData->used)->toBe(2.75);
    expect($soldier->shiftsMaxData->used)->toBe(1.0);
    expect($soldier->weekendsMaxData->used)->toBe(0.0);
    expect($soldier->nightsMaxData->used)->toBe(2.75);
});

it('should return true if the soldier qualified to the task', function () {
    $soldier = new Soldier(1, new MaxData(2.75), new MaxData(12), new MaxData(0), new MaxData(3), ['Run'], []);
    expect($soldier->isQualified('Run'))->toBeTrue();
});

it('should return false if the soldier is not qualified to the task', function () {
    $soldier = new Soldier(1, new MaxData(2.75), new MaxData(12), new MaxData(0), new MaxData(3), ['Run'], []);
    expect($soldier->isQualified('Clean'))->toBeFalse();
});
