<?php

use App\Resources\SoldierResource\Pages\CreateSoldier;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager');
});

it('returns an array with first_name and last_name', function () {

    DB::shouldReceive('table->where->where->pluck')
        ->andReturn(collect(['first_name' => 'John', 'last_name' => 'Doe']));

    Notification::fake();

    $classInstance = new CreateSoldier;

    $classInstance->data = [
        'user' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ],
    ];

    $classInstance->beforeCreate();

    $this->assertIsArray($classInstance->data);
});
