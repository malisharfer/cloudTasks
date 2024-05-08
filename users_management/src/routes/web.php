<?php

use Illuminate\Support\Facades\Route;
use App\Filament\Resources\RequestResource\Pages\ExportRequests;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\CustomLoginController;


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

Route::get('requests/export', ExportRequests::class)->name('requests.export');

Route::get('/login', function () {
    return Socialite::driver('azure')->redirect();
})->name('customLogin');

Route::get('/auth/callback',[CustomLoginController::class, 'UserFromLogin'] );
