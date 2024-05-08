<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use App\Models\User;
use App\Services\GetUsers;
use App\Enums\Users\Role;


class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user->role === Role::Admin , 401);
    }

    public function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
            Action::make('switchView')
                ->label(__('change view'))
                ->url(fn () =>request()->input('viewType', 'Table') === 'Table' ?  url()->current() . '?viewType=Card': url()->current() . '?viewType=Table')
        ];
    }
}
