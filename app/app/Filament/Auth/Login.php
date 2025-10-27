<?php

namespace App\Filament\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseAuth;
use Illuminate\Validation\ValidationException;

class Login extends BaseAuth
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('first_name')
                    ->label(__('First name'))
                    ->required()
                    ->autofocus(),
                TextInput::make('last_name')
                    ->label(__('Last name'))
                    ->required(),
                TextInput::make('password')
                    ->label(__('Personal number'))
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->length(7)
                    ->rule('regex:/^\d{7}$/')
                    ->required(),
            ])
            ->statePath('data');
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.password' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }
}
