<?php

use Database\Seeders\PermissionSeeder;

it('allows admin to view soldiers', function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager')
        ->get('/soldiers')
        ->assertStatus(200);
});

it('does not allow team commander to view teams', function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('team-commander')
        ->get('/teams')
        ->assertStatus(403);
});
