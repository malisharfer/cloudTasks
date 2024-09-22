<?php

namespace App\Filters;

use Filament\Forms;
use Filament\Forms\Get;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Illuminate\Database\Eloquent\Builder;

class NumberFilter extends Filter
{
    protected function setUp(): void
    {
        parent::setup();

        $this->form([
            Forms\Components\Fieldset::make($this->getlabel())
                ->schema([
                    Forms\Components\Select::make('range_condition')
                        ->hiddenLabel()
                        ->placeholder('Select condition')
                        ->live()
                        ->options([
                            'equal' => 'equal',
                            'not_equal' => 'not_equal',
                            'between' => 'between',
                            'greater_than' => 'greater_than',
                            'greater_than_equal' => 'greater_than_equal',
                            'less_than' => 'less_than',
                            'less_than_equal' => 'less_than_equal',
                        ]),
                    Forms\Components\TextInput::make('range_equal')
                        ->hiddenLabel()
                        ->numeric()
                        ->visible(fn (Get $get): bool => $get('range_condition') === 'equal'),
                    Forms\Components\TextInput::make('range_not_equal')
                        ->hiddenLabel()
                        ->numeric()
                        ->visible(fn (Get $get): bool => $get('range_condition') === 'not_equal'),
                    Forms\Components\Grid::make([
                        'default' => 1,
                        'sm' => 2,
                    ])
                        ->schema([
                            Forms\Components\TextInput::make('range_between_from')
                                ->hiddenLabel()
                                ->numeric(),
                            Forms\Components\TextInput::make('range_between_to')
                                ->hiddenLabel()
                                ->numeric(),
                        ])
                        ->visible(fn (Get $get): bool => $get('range_condition') === 'between'),
                    Forms\Components\TextInput::make('range_greater_than')
                        ->hiddenLabel()
                        ->numeric()
                        ->visible(fn (Get $get): bool => $get('range_condition') === 'greater_than'),
                    Forms\Components\TextInput::make('range_greater_than_equal')
                        ->hiddenLabel()
                        ->numeric()
                        ->visible(fn (Get $get): bool => $get('range_condition') === 'greater_than_equal'),
                    Forms\Components\TextInput::make('range_less_than')
                        ->hiddenLabel()
                        ->numeric()
                        ->visible(fn (Get $get): bool => $get('range_condition') === 'less_than'),
                    Forms\Components\TextInput::make('range_less_than_equal')
                        ->hiddenLabel()
                        ->numeric()
                        ->visible(fn (Get $get): bool => $get('range_condition') === 'less_than_equal'),
                ])
                ->columns(1),
        ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['range_equal'],
                        fn (Builder $query, $value): Builder => $query->where($this->getName(), '=', $value),
                    )
                    ->when(
                        $data['range_not_equal'],
                        fn (Builder $query, $value): Builder => $query->where($this->getName(), '!=', $value),
                    )
                    ->when(
                        $data['range_between_from'] && $data['range_between_to'],
                        function (Builder $query, $value) use ($data) {
                            $query->where($this->getName(), '>=', $data['range_between_from'])->where($this->getName(), '<=', $data['range_between_to']);
                        },
                    )
                    ->when(
                        $data['range_greater_than'],
                        fn (Builder $query, $value): Builder => $query->where($this->getName(), '>', $value),
                    )
                    ->when(
                        $data['range_greater_than_equal'],
                        fn (Builder $query, $value): Builder => $query->where($this->getName(), '>=', $value),
                    )
                    ->when(
                        $data['range_less_than'],
                        fn (Builder $query, $value): Builder => $query->where($this->getName(), '<', $value),
                    )
                    ->when(
                        $data['range_less_than_equal'],
                        fn (Builder $query, $value): Builder => $query->where($this->getName(), '<=', $value),
                    );
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];

                if ($data['range_between_from'] || $data['range_between_to']) {
                    $indicators[] = Indicator::make(__(':label is between :fromValue and :toValue', [
                        'label' => $this->getLabel(),
                        'fromValue' => $data['range_between_from'],
                        'toValue' => $data['range_between_to'],
                    ]))
                        ->removeField('range_between_from')
                        ->removeField('range_between_to');
                }

                if ($data['range_equal']) {
                    $indicators[] = Indicator::make(__(':label is equal to :value', [
                        'label' => $this->getLabel(),
                        'value' => $data['range_equal'],
                    ]))
                        ->removeField('range_equal');
                }

                if ($data['range_not_equal']) {
                    $indicators[] = Indicator::make(__(':label is not equal to :value', [
                        'label' => $this->getLabel(),
                        'value' => $data['range_not_equal'],
                    ]))
                        ->removeField('range_not_equal');
                }

                if ($data['range_greater_than']) {
                    $indicators[] = Indicator::make(__(':label is greater than :value', [
                        'label' => $this->getLabel(),
                        'value' => $data['range_greater_than'],
                    ]))
                        ->removeField('range_greater_than');
                }

                if ($data['range_greater_than_equal']) {
                    $indicators[] = Indicator::make(__(':label is greater than or equal to :value', [
                        'label' => $this->getLabel(),
                        'value' => $data['range_greater_than_equal'],
                    ]))
                        ->removeField('range_greater_than_equal');
                }

                if ($data['range_less_than']) {
                    $indicators[] = Indicator::make(__(':label is less than :value', [
                        'label' => $this->getLabel(),
                        'value' => $data['range_less_than'],
                    ]))
                        ->removeField('range_less_than');
                }

                if ($data['range_less_than_equal']) {
                    $indicators[] = Indicator::make(__(':label is less than or equal to :value', [
                        'label' => $this->getLabel(),
                        'value' => $data['range_less_than_equal'],
                    ]))
                        ->removeField('range_less_than_equal');
                }

                return $indicators;
            });
    }
}
