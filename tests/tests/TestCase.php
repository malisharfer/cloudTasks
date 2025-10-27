<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    public function asUser($role, $user = null): static
    {
        $this->actingAs(isset($user) ? $user->assignRole($role) : User::factory()->create()->assignRole($role));

        return $this;
    }
}
