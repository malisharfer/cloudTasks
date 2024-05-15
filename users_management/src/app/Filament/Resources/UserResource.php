<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function shouldRegisterNavigation(): bool
    {
        $user = self::getUserFromAzure();

        return $user->role === 'Admin';
    }

    public static function getUserFromAzure()
    {
        return auth()->user();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('name'))
                    ->required(),
                Forms\Components\TextInput::make('phone')
                    ->label(__('phone'))
                    ->tel()
                    ->maxLength(10)
                    ->required(),
                Forms\Components\Select::make('role')
                    ->options([
                        'Admin' => __('Admin'),
                        'User' => __('User'),
                    ])
                    ->label(__('role'))
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->label(__('email'))
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required(),
                Forms\Components\TextInput::make('password')
                    ->label(__('password'))
                    ->password()
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
            Tables\Columns\TextColumn::make('name')
                ->label(__('name'))
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('phone')
                ->label(__('phone'))
                ->searchable(),
            Tables\Columns\TextColumn::make('role')
                ->label(__('role'))
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('email')
                ->label(__('email'))
                ->copyable()
                ->copyMessage('Email address copied')
                ->copyMessageDuration(1500)
                ->searchable(),
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
