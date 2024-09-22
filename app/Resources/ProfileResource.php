<?php

namespace App\Resources;

use App\Models\Soldier;
use App\Models\Task;
use App\Resources\ProfileResource\Pages;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProfileResource extends Resource
{
    protected static ?string $model = Soldier::class;

    protected static bool $shouldRegisterNavigation = false;

    public static ?string $label = 'My profile';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('')
                    ->schema([
                        Fieldset::make('Personal Information')
                            ->relationship('user')
                            ->schema([
                                TextInput::make('first_name')
                                    ->required(),
                                TextInput::make('last_name')
                                    ->required(),
                            ]),
                        Checkbox::make('is_permanent'),
                        DatePicker::make('enlist_date')
                            ->seconds(false),
                        Checkbox::make('has_exemption'),
                        Checkbox::make('is_trainee'),
                        Checkbox::make('is_mabat'),
                        Select::make('qualifications')
                            ->placeholder('select qualification')
                            ->options(Task::all()->pluck('name', 'name')),
                    ]),

            ]);

    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                split::make([
                    Stack::make([
                        TextColumn::make('user.first_name')->description('First name', position: 'above')->size(TextColumn\TextColumnSize::Large),
                        TextColumn::make('user.last_name')->description('Last name', position: 'above')->size(TextColumn\TextColumnSize::Large),
                        TextColumn::make('enlist_date')->description('Enlist date', position: 'above')->size(TextColumn\TextColumnSize::Large),
                    ]),
                    Stack::make([
                        TextColumn::make('course')->description('Course', position: 'above')->size(TextColumn\TextColumnSize::Large),
                        TextColumn::make('max_shift')->description('Max shift', position: 'above')->size(TextColumn\TextColumnSize::Large),
                        TextColumn::make('max_night')->description('Max night', position: 'above')->size(TextColumn\TextColumnSize::Large),
                        TextColumn::make('max_weekend')->description('Max weekend', position: 'above')->size(TextColumn\TextColumnSize::Large),
                    ]),
                    Stack::make([
                        TextColumn::make('capacity')->description('Capacity', position: 'above')->size(TextColumn\TextColumnSize::Large),
                        TextColumn::make('capacity_hold')->description('Capacity hold', position: 'above')->size(TextColumn\TextColumnSize::Large),
                        TextColumn::make('qualifications')->description('Qualifications', position: 'above')->size(TextColumn\TextColumnSize::Large),
                    ]),
                ]),
            ])
            ->searchable(false)
            ->paginated(false)
            ->filters([
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return Soldier::query()->where('id', auth()->user()->userable->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProfiles::route('/'),
            'edit' => Pages\EditProfile::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return true;
    }
}
