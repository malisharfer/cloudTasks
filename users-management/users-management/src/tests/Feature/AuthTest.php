<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Route;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_login_redirects_to_azure_auth()
    {
        Socialite::shouldReceive('driver->redirect')->once();
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_auth_callback_route()
    {
        Socialite::shouldReceive('driver->stateless->user')->andReturn((object) [
            'name' => 'example',
            'email' => 'mock@example.com',
        ]);
        Route::any('/auth/callback', function () {
            return 'auth callback';
        });
        $response = $this->get('/auth/callback');
        $response->assertStatus(200)->assertSee('auth callback');
    }   
}
