<?php

namespace App\Filament\Resources;

use App\Enums\Options;
use App\Enums\Users\Role;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Services\GetUsers;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user->role === Role::Admin;
    }

    public static function getEloquentQuery(): Builder
    {
        $services = app(GetUsers::class);
        $group_ids = [config('services.azure.group_id_clients'), config('services.azure.group_id_admins')];
        $users = $services->getAllGroupMembers($group_ids);
        $user = new User();
        $user->updateUsersInDB($users);

        return parent::getEloquentQuery();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('name'))
                    ->required(),
                TextInput::make('email')
                    ->label(__('email'))
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required(),
                Select::make('role')
                    ->options(Options::getOptions(Role::cases()))
                    ->label(__('role'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $viewType = request()->input('viewType', 'Table');

        if ($viewType === 'Card') {
            $table = $table->contentGrid(['md' => 2, 'xl' => 3])->columns(
                array_merge(self::commonColumns(), [Split::make([])])
            );
        } else {
            $table = $table->striped()->columns(self::commonColumns());
        }

        return $table
            ->filters([
            ])
            ->actions([
            ])
            ->bulkActions([
            ]);
    }

    public static function commonColumns(): array
    {
        return [
            TextColumn::make('name')->label(__('Name'))->sortable()->searchable(),
            TextColumn::make('role')->label(__('Role'))->sortable()->searchable(),
            TextColumn::make('email')->label(__('Email'))->sortable()->searchable()->copyable()->copyMessage(__('Email address copied'))->copyMessageDuration(1500),
        ];
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('user');
    }

    public static function getPluralModelLabel(): string
    {
        return __('users');
    }
}
