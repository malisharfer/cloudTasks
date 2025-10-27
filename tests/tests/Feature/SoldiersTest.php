<?php

use App\Models\Soldier;
use App\Models\User;

it('deleting a soldier deletes his details', function () {
    $soldier = Soldier::factory()->create();
    $user = User::factory()->create([
        'userable_id' => $soldier->id,
    ]);
    $soldier->delete();
    $this->assertModelMissing($user);
});
