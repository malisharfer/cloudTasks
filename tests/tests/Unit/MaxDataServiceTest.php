<?php

use App\Services\MaxData;

it('should return the remaining of max', function () {
    $maxData = new MaxData(4);
    expect($maxData->remaining())->toBe(4.0);
});
