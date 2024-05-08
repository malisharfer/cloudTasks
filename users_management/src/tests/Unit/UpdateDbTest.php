<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use App\Http\Controllers\CustomLoginController;
use App\Models\User;
use Tests\TestCase;


class UpdateDbTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function testUpdateDb()
    {
        $users = User::factory()->count(5)->create();

        $controller = new CustomLoginController();
        $controller->updateDB($users->toArray());

        $this->assertEquals(5, User::count());
    }
}