<?php

namespace Tests\Feature;

namespace Tests\Unit\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Http\Responses\LogoutResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Mockery;

class LogoutResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_out_user_and_redirects_to_admin_page()
    {
        $requestMock = Mockery::mock(Request::class);
        Auth::shouldReceive('guard->logout')->once();
        $requestMock->shouldReceive('session->flush')->once();
        $logoutResponse = new LogoutResponse();
        $response = $logoutResponse->toResponse($requestMock);
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
