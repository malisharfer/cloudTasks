<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    public function test_a_user_can_be_authenticated()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);
    }
}
