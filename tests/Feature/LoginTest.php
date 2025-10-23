<?php

use App\Filament\Auth\Login;
use Livewire\Livewire;

it('redirects unauthorized users to the login page', function () {
    $this
        ->get('/')
        ->assertRedirect('/login');
});

it('should return an error if data is missing', function () {
    Livewire::test(Login::class)
        ->fillForm([
            'first_name' => '',
            'last_name' => 'last_name',
            'password' => '1234567',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['first_name' => ['required']]);
});

it('cannot authenticate with incorrect credentials', function () {
    Livewire::test(Login::class)
        ->fillForm([
            'first_name' => 'first_name',
            'last_name' => 987,
            'password' => '1234567',
        ])
        ->call('authenticate')
        ->assertHasFormErrors();
});
