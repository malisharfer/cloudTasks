<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Filament\Tables\Actions\Action;
use Illuminate\Http\Request;
use App\Services\GetUsers;
use App\Enums\Users\Role;
use App\Enums\Options;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user->role === Role::Admin;
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

        if ($viewType === 'Card'){
            $table = $table->contentGrid(['md' => 2, 'xl' => 3])->columns(
                array_merge(self::commonColumns(), [Split::make([])])
            );
        }
        else{
            $table = $table->striped()->columns(self::commonColumns());
        }
        return $table
            ->filters([
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                // ]),
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
            // 'create' => Pages\CreateUser::route('/create'),
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
