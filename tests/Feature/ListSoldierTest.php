<?php

use App\Models\Soldier;
use App\Models\Team;
use App\Models\User;
use App\Resources\SoldierResource\Pages\ListSoldiers;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;

it('should return users by team_id', function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager');

    $team = Team::factory()->create(['id' => 100]);
    $soldier = Soldier::factory()->create(['team_id' => $team->id]);
    User::factory()->create(['userable_id' => $soldier->id]);

    $team = Team::factory()->create(['id' => 101]);
    $soldier = Soldier::factory()->create(['team_id' => $team->id]);
    User::factory()->create(['userable_id' => $soldier->id]);

    Livewire::withQueryParams(['team_id' => 100])
        ->test(ListSoldiers::class)
        ->assertCountTableRecords(1);
});
