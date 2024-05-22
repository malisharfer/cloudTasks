<?php

namespace Tests\Feature;

use App\Enums\Users\Role;
use App\Http\Controllers\CustomLoginController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class CustomLoginControllerTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            '*' => Http::response(['access_token' => 'fake_access_token',
                'displayName' => 'fake_display_name',
                'value' => [
                    ['@odata.type' => '#microsoft.graph.user', 'displayName' => 'User2', 'mail' => 'user2@example.com'],
                    ['@odata.type' => '#microsoft.graph.user', 'displayName' => 'User1', 'mail' => 'user1@example.com'],
                ]], 200),
        ]);

        Config::set('services.azure.client_id', 'client_id');
        Config::set('services.azure.client_secret', 'client_secret');
        Config::set('services.azure.tenant', 'tenant_id');
        Config::set('services.azure.graph_url', 'graph_url');
        Config::set('services.azure.group_id_clients', 'group_id_clients');
        Config::set('services.azure.group_id_admins', 'group_id_admins');
    }

    public function test_user_from_login()
    {
        $socialiteUser = (object) ['name' => 'User1', 'email' => 'user1@example.com', 'role' => Role::User];
        Socialite::shouldReceive('driver->stateless->user')->once()->andReturn($socialiteUser);
        $controller = new CustomLoginController();
        $response = $controller->UserFromLogin();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertInstanceOf(User::class, Auth::user());
        $this->assertEquals('http://localhost', $response->getTargetUrl());

        $this->assertDatabaseHas('users', [
            'name' => 'User1',
            'email' => 'user1@example.com',
            'role' => 'Admin',
        ]);
        $this->assertDatabaseHas('users', [
            'name' => 'User2',
            'email' => 'user2@example.com',
            'role' => 'Admin',
        ]);
    }

    public function test_user_from_login_with_user_and_define_role()
    {
        $user = (object) ['name' => 'User2', 'email' => 'user2@example.com', 'role' => null];
        Socialite::shouldReceive('driver->stateless->user')->once()->andReturn($user);
        $controller = new CustomLoginController();
        $response = $controller->UserFromLogin();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertInstanceOf(User::class, Auth::user());
        $this->assertEquals('http://localhost', $response->getTargetUrl());

        $this->assertDatabaseHas('users', [
            'name' => 'User2',
            'email' => 'user2@example.com',
            'role' => 'Admin',
        ]);
    }

    public function test_user_from_login_with_user_login_not_exist_in_get_users()
    {
        $user = (object) ['name' => 'User3', 'email' => 'user3@example.com', 'role' => 'User'];
        Socialite::shouldReceive('driver->stateless->user')->once()->andReturn($user);
        $controller = new CustomLoginController();
        $response = $controller->UserFromLogin();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertInstanceOf(User::class, Auth::user());
        $this->assertEquals('http://localhost', $response->getTargetUrl());

        $this->assertDatabaseMissing('users', [
            'name' => 'User3',
            'email' => 'user3@example.com',
            'role' => 'Admin',
        ]);
    }

    public function test_user_from_login_with_user_null()
    {
        Auth::logout();
        $user = (object) ['name' => 'User1', 'email' => 'user1@example.com', 'role' => 'User'];
        Socialite::shouldReceive('driver->stateless->user')->once()->andReturn($user);
        $controller = new CustomLoginController();
        $response = $controller->UserFromLogin();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertInstanceOf(User::class, Auth::user());
        $this->assertEquals('http://localhost', $response->getTargetUrl());

        $this->assertDatabaseHas('users', [
            'name' => 'User2',
            'email' => 'user2@example.com',
            'role' => 'Admin',
        ]);
        $this->assertDatabaseHas('users', [
            'name' => 'User1',
            'email' => 'user1@example.com',
            'role' => 'Admin',
        ]);
    }

    public function test_user_from_login_with_user_null_not_exist_in_get_users()
    {
        Auth::logout();
        $user = (object) ['name' => 'User3', 'email' => 'user3@example.com', 'role' => 'User'];
        Socialite::shouldReceive('driver->stateless->user')->once()->andReturn($user);
        $controller = new CustomLoginController();
        $response = $controller->UserFromLogin();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertNotInstanceOf(User::class, Auth::user());
        $this->assertEquals('http://localhost/login', $response->getTargetUrl());

        $this->assertDatabaseMissing('users', ['name' => 'User3', 'email' => 'user3@example.com', 'role' => 'Admin']);
        $this->assertDatabaseMissing('users', ['name' => 'User2', 'email' => 'user2@example.com', 'role' => 'Admin']);
        $this->assertDatabaseMissing('users', ['name' => 'User1', 'email' => 'user1@example.com', 'role' => 'Admin']);
    }
}
