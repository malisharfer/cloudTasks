<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('/login', function () {
    return Socialite::driver('azure')->redirect();
})->name('customLogin');

Route::get('/auth/callback', function () {
    $user = Socialite::driver('azure')->stateless()->user();
    $user = User::updateOrCreate([
        'name' => $user->name,
        'email' => $user->email,
        'role' => 'Admin',
    ], );
    Auth::login($user);

    return redirect('/');
});
