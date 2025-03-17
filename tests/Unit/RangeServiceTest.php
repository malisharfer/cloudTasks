<?php

use App\Enums\DaysInWeek;
use App\Services\Range;
use Carbon\Carbon;

it('should return true if the dates have conflicts', function () {
    $range = new Range(Carbon::create(2024, 5, 14, 17), Carbon::create(2024, 5, 14, 18));
    expect($range->isConflict(new Range(Carbon::create(2024, 5, 14, 5), Carbon::create(2024, 5, 14, 18))))->toBeTrue();
});

it('should return false if the date have not conflict', function () {
    $range = new Range(Carbon::create(2024, 5, 14, 17), Carbon::create(2024, 5, 14, 18));
    expect($range->isConflict(new Range(Carbon::create(2024, 5, 14, 5), Carbon::create(2024, 5, 14, 16))))->toBeFalse();
});

it('should return true if the dates are in the same month', function () {
    $range = new Range(Carbon::create(2024, 5, 14, 17), Carbon::create(2024, 5, 14, 18));
    expect($range->isSameMonth(new Range(Carbon::create(2024, 5, 14, 5), Carbon::create(2024, 5, 14, 18))))->toBeTrue();
});

it('should return false if the dates are not in the same month', function () {
    $range = new Range(Carbon::create(2024, 5, 14, 17), Carbon::create(2024, 5, 14, 18));
    expect($range->isSameMonth(new Range(Carbon::create(2024, 4, 14, 5), Carbon::create(2024, 4, 14, 18))))->toBeFalse();
});

it('should return true if the date has passed', function () {
    $range = new Range(Carbon::create(2024, 5, 14, 17), Carbon::create(2024, 5, 14, 18));
    expect($range->isPass())->toBeTrue();
});

it('should return false if the date has passed', function () {
    $range = new Range(now()->addDay(), now()->addDays(2));
    expect($range->isPass())->toBeFalse();
});

it('should return true if range include the provided day', function () {
    $range = new Range(Carbon::create(2024, 11, 3), Carbon::create(2024, 11, 11));
    expect($range->isRangeInclude(DaysInWeek::THURSDAY))->toBeTrue();
});

it('should return false if range is not include the provided day', function () {
    $range = new Range(Carbon::create(2024, 11, 3), Carbon::create(2024, 11, 4));
    expect($range->isRangeInclude(DaysInWeek::WEDNESDAY))->toBeFalse();
});
it('should return the next sunday', function () {
    $range = new Range(Carbon::create(2024, 11, 7), Carbon::create(2024, 11, 8));
    $result = $range->getDayAfterWeekend();
    $expectedStart = Carbon::parse('2024-11-10 08:00:00');
    $expectedEnd = Carbon::parse('2024-11-11 08:00:00');

    expect($result->start)->toEqual($expectedStart);
    expect($result->end)->toEqual($expectedEnd);
});

it('should return the night spaces', function () {
    $range = new Range('2024-11-07 22:00:00', '2024-11-08 05:00:00');
    $result = $range->getNightSpaces();
    $expect = [
        new Range('2024-11-07 10:00:00', '2024-11-07 22:00:00'),
        new Range('2024-11-08 05:00:00', '2024-11-08 17:00:00'),
    ];
    expect(count($result))->toBe(count($expect));
    foreach ($result as $index => $range) {
        expect($range->start)->toEqual($expect[$index]->start);
        expect($range->end)->toEqual($expect[$index]->end);
    }
});

it('should return the day before night', function () {
    $range = new Range('2024-11-07 22:00:00', '2024-11-08 05:00:00');
    $result = $range->getDayBeforeNight();
    $expect = new Range('2024-11-07 10:00:00', '2024-11-07 22:00:00');
    expect($result->start)->toEqual($expect->start);
    expect($result->end)->toEqual($expect->end);
});

it('should return the day after night', function () {
    $range = new Range(Carbon::create(2024, 11, 7, 22), Carbon::create(2024, 11, 8, 5));
    $result = $range->getDayAfterNight();
    $expect = new Range(Carbon::parse('2024-11-08 05:00:00'), Carbon::parse('2024-11-08 17:00:00'));
    expect($result->start->toString())->toBe($expect->start->toString());
    expect($result->end->toString())->toBe($expect->end->toString());
});
