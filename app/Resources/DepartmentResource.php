<?php

namespace App\Resources;

use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use App\Resources\DepartmentResource\Pages;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function getModelLabel(): string
    {
        return __('Department');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Departments');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make([
                    TextInput::make('name')
                        ->label(__('Name'))
                        ->required(),
                    Select::make('commander_id')
                        ->label(__('Commander'))

                        ->relationship('commander', 'id')
                        ->options(
                            fn () => Cache::remember('users', 30 * 60, function () {
                                return User::all();
                            })->mapWithKeys(function ($user) {
                                return [$user->userable_id => $user->displayName];
                            })
                        )
                        ->searchable(),
                ])->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('commander.user')
                    ->formatStateUsing(function ($state) {
                        return $state->last_name.', '.$state->first_name;
                    })
                    ->label(__(key: 'Commander'))
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
                        ->label(__('Add team'))
                        ->color('primary')
                        ->icon('heroicon-o-users')
                        ->url(fn (Department $record): string => route('filament.app.resources.teams.create', ['department_id' => $record->id])),
                    Action::make('View teams')
                        ->label(__('View teams'))
                        ->color('success')
                        ->icon('heroicon-o-users')
                        ->badge(fn ($record) => Team::where('department_id', $record->id)->count())
                        ->url(fn (Department $record): string => route('filament.app.resources.teams.index', ['department_id' => $record->id])),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
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
