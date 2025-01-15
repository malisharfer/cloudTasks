<?php

use Database\Seeders\PermissionSeeder;

it('allows admin to view soldiers', function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager')
        ->get('/soldiers')
        ->assertStatus(200);
});

it('allows team commander to see the teams under him', function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('team-commander')
        ->get('/teams')
        ->assertStatus(200);
});
