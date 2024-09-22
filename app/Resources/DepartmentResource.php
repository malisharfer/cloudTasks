<?php

namespace App\Resources;

use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use App\Resources\DepartmentResource\Pages;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required(),
                Select::make('commander_id')
                    ->relationship('commander', 'id')
                    ->options(
                        fn () => Cache::remember('users', 30 * 60, function () {
                            return User::all();
                        })->mapWithKeys(function ($user) {
                            return [$user->userable_id => $user->displayName];
                        })
                    )
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('commander.user')
                    ->formatStateUsing(function ($state) {
                        return $state->last_name.', '.$state->first_name;
                    })
                    ->label('Commander')
                    ->searchable()
                    ->sortable(),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                if (request()->input('commander_id')) {
                    return $query->where('commander_id', request()->input('commander_id'));
                }
                if (request()->input('department_id')) {
                    return $query->where('id', request()->input('department_id'));
                }
            })
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('teams')
                        ->label('Add team')
                        ->color('primary')
                        ->icon('heroicon-o-users')
                        ->url(fn (Department $record): string => route('filament.app.resources.teams.create', ['department_id' => $record->id])),
                    Action::make('View teams')
                        ->color('success')
                        ->icon('heroicon-o-users')
                        ->badge(fn ($record) => Team::where('department_id', $record->id)->count())
                        ->url(fn (Department $record): string => route('filament.app.resources.teams.index', ['department_id' => $record->id])),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
        ];
    }
}
