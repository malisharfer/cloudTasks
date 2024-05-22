<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\Users\Role;
use App\Exports\ExportUser;
use App\Filament\Resources\UserResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user->role === Role::Admin, 401);
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('switchView')
                ->label(__('change view'))
                ->url(fn () => request()->input('viewType', 'Table') === 'Table' ? UserResource::getUrl().'?viewType=Card' : UserResource::getUrl().'?viewType=Table'),
            Action::make(('export'))
                ->label(__('download'))
                ->action(function () {
                    return Excel::download(new ExportUser, 'Users.xlsx');
                })
                ->icon('heroicon-o-arrow-down-tray'),
        ];
    }
}
