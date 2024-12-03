<?php

use App\Services\MaxData;

it('should return the remaining of max', function () {
    $maxData = new MaxData(4);
    expect($maxData->remaining())->toBe(4.0);
});

it('should return the relative load of the max', function () {
    $maxData = new MaxData(4);
    expect($maxData->calculatedRelativeLoad())->toBe(0.05);
});

it('should return 0 if max is 0', function () {
    $maxData = new MaxData(0);
    expect($maxData->calculatedRelativeLoad())->toBe(0.0);
});
