<?php

namespace Tests\Feature;

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\CustomAuthentication;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class CustomAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_redirects_to_custom_login_route()
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Unauthenticated.');

        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->andReturn(false);

        $auth = Mockery::mock(AuthFactory::class);
        $auth->shouldReceive('guard')->andReturn($guard);

        $middleware = new CustomAuthentication($auth);
        $request = Request::create('/test');

        $next = function ($request) {
            return 'Next middleware called';
        };

        $middleware->handle($request, $next);
    }
}
