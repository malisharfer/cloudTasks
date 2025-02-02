<?php

use App\Models\Soldier;
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

it('should unassign team-commander role from the commander', function () {
    $commander1 = User::factory()->create();
    $team = Team::factory()->create(['commander_id' => $commander1]);
    $commander1->assignRole('team-commander');
    $commander2 = User::factory()->create();

    livewire(EditTeam::class, [
        'record' => $team->id,
    ])
        ->set('data.commander_id', $commander2->userable_id)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($commander1->getRoleNames()->contains('team-commander'))->toBeFalse();
    expect($commander2->getRoleNames()->contains('team-commander'))->toBeTrue();
});

it('should assign team members', function () {
    $team = Team::factory()->create();
    $member1 = Soldier::factory()->create();
    $member2 = Soldier::factory()->create();

    livewire(
        EditTeam::class,
        ['record' => $team->id]
    )
        ->set('data.members', [$member1->id, $member2->id])
        ->call('save');

    expect($member1->refresh()->team_id)->toBe($team->id);
    expect($member2->refresh()->team_id)->toBe($team->id);
});

it('should unassign team members', function () {
    $team = Team::factory()->create();
    $member1 = Soldier::factory()->create(['team_id' => $team->id]);
    $member2 = Soldier::factory()->create();
    $member3 = Soldier::factory()->create();

    livewire(
        EditTeam::class,
        ['record' => $team->id]
    )
        ->set('data.members', [$member2->id, $member3->id])
        ->call('save');

    expect($member1->refresh()->team_id)->toBe(null);
});
