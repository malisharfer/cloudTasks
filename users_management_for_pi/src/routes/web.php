<?php

use App\Http\Controllers\CustomLoginController;
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

Route::get('/auth/callback', [CustomLoginController::class, 'UserFromLogin']);
