<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LogoutResponse as Responsable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class LogoutResponse implements Responsable
{
    public function toResponse($request): RedirectResponse
    {
        Auth::guard()->logout();
        $request->session()->flush();

        return redirect('/');
    }
}
