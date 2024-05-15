<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Exports\ExportUser;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function mount(): void
    {
        $user = UserResource::getUserFromAzure();
        abort_unless($user->role === 'Admin', 401);
    }

    public function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
            Action::make('switchView')
                ->label(__('change view'))
                ->url(fn () => request()->input('viewType', 'Table') === 'Table' ? url()->current().'?viewType=Card' : url()->current().'?viewType=Table'),
            Action::make(('export'))
                ->label(__('download'))
                ->action(function () {
                    return Excel::download(new ExportUser, 'Users.xlsx');
                })
                ->icon('bi-download'),
        ];
    }
}
