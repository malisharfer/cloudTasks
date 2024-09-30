<?php

namespace App\Resources;

use App\Models\Soldier;
use App\Models\Task;
use App\Resources\ProfileResource\Pages;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
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
                Section::make([])
                    ->schema([
                        Fieldset::make(__('Personal Information'))
                            ->relationship('user')
                            ->schema([
                                TextInput::make('first_name')->label(__('First name'))->required(),
                                TextInput::make('last_name')->label(__('Last name'))->required(),
                            ]),
                        Section::make([
                            Select::make('qualifications')
                                ->label(__('Qualifications'))
                                ->placeholder(__('Select an option'))
                                ->options(Task::all()->pluck('name', 'name')),
                            DatePicker::make('enlist_date')->label(__('Enlist date'))->seconds(false),
                        ])->columns(2),
                        Section::make([
                            Toggle::make('is_permanent')->Label(__('Is permanent')),
                            Toggle::make('has_exemption')->label(__('Has exemption')),
                            Toggle::make('is_trainee')->label(__('Is trainee')),
                            Toggle::make('is_mabat')->label(__('Is mabat')),
                        ])->columns(4),

                    ]),

            ]);

    }

    public static function table(Table $table): Table
    {

        return $table
            ->contentGrid([
                'md' => 1,
                'xl' => 1,
            ])
            ->columns([
                split::make([
                    Stack::make([
                        TextColumn::make('user.first_name')
                            ->label(__('Full name'))
                            ->formatStateUsing(function ($record) {
                                return $record->user->last_name.' '.$record->user->first_name;
                            })->weight(weight: FontWeight::SemiBold)->description(__('Full name'), position: 'above')->size(TextColumnSize::Large),
                        TextColumn::make('enlist_date')->weight(weight: FontWeight::SemiBold)->description(__('Enlist date'), position: 'above')->size(TextColumnSize::Large)->date(),
                        TextColumn::make('course')->weight(weight: FontWeight::SemiBold)->description(__('Course'), position: 'above')->size(TextColumnSize::Large),
                        TextColumn::make('max_shift')->weight(weight: FontWeight::SemiBold)->description(__('Max shift'), position: 'above')->size(TextColumnSize::Large),
                    ]),
                    Stack::make([

                        TextColumn::make('max_night')->weight(weight: FontWeight::SemiBold)->description(__('Max night'), position: 'above')->size(TextColumnSize::Large),
                        TextColumn::make('max_weekend')->weight(weight: FontWeight::SemiBold)->description(__('Max weekend'), position: 'above')->size(TextColumnSize::Large),
                        TextColumn::make('capacity')->weight(weight: FontWeight::SemiBold)->description(__('Capacity'), position: 'above')->size(TextColumnSize::Large),
                        TextColumn::make('capacity_hold')->weight(weight: FontWeight::SemiBold)->description(__('Capacity hold'), position: 'above')->size(TextColumnSize::Large),
                        TextColumn::make('qualifications')->weight(weight: FontWeight::SemiBold)->description(__('Qualifications'), position: 'above')->size(TextColumnSize::Large),
                    ]),
                ]),
            ])
            ->searchable(false)
            ->paginated(false)
            ->filters([
            ])
            ->actions([
                EditAction::make(),
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

    public static function getPluralModelLabel(): string
    {
        return __('My profile');
    }
}
