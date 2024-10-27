<?php

use App\Models\Team;
use App\Models\User;
use App\Resources\TeamResource\Pages\EditTeam;
use Database\Seeders\PermissionSeeder;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager');
});

it('should assign team-commander role to the commander', function () {
    $team = Team::factory()->create();
    $commander = User::factory()->create();

    livewire(EditTeam::class, [
        'record' => $team->id,
    ])
        ->set('data.commander_id', $commander->userable_id)
        ->call('save')
        ->assertHasNoFormErrors();
    $this->assertDatabaseHas('teams', [
        'id' => $team->id,
        'commander_id' => $commander->userable_id,
    ]);

    expect($commander->getRoleNames()->contains('team-commander'))->toBe(true);
});
