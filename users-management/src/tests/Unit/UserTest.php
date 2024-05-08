<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User; 
use Tests\TestCase;


class UserTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    function test_a_user_can_be_authenticated()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);
    }

    public function test_fillable_attributes()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $expected = ['name','email', 'role',  'password'];
        $this->assertSame($expected, $user->getFillable());
    }

    public function test_hidden_attributes()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $expected = ['password', 'remember_token'];
        $this->assertSame($expected, $user->getHidden());
    }
}
