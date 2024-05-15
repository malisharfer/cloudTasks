<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class CustomAuthentication extends Middleware
{
    protected function redirectTo($request): ?string
    {
        return route('customLogin');
    }
}
