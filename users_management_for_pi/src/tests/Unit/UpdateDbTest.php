<?php

namespace Tests\Unit;

use App\Http\Controllers\CustomLoginController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class UpdateDbTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_update_db()
    {
        $users = User::factory()->count(5)->create();

        $controller = new CustomLoginController();
        $controller->updateDB($users->toArray());

        $this->assertEquals(5, User::count());
    }
}
