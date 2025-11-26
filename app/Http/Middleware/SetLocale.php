<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next): mixed
    {
        $language = request()->cookie('user-language');

        if ($language) {
            app()->setLocale($language);
        }

        return $next($request);
    }
}
