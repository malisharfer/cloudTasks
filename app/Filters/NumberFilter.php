<?php

namespace App\Filters;

use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
            Fieldset::make(__($this->getlabel()))
                ->schema([
                    Select::make('range_condition')
                        ->hiddenLabel()
                        ->placeholder(__('Select condition'))
                        ->live()
                        ->options([
                            'equal' => __('Equal'),
                            'not_equal' => __('Not equal'),
                            'between' => __('Between'),
                            'greater_than' => __('Greater than'),
                            'greater_than_equal' => __('Greater than equal'),
                            'less_than' => __('Less than'),
                            'less_than_equal' => __('Less than equal'),
                        ]),
                    TextInput::make('range_equal')
                        ->hiddenLabel()
                        ->numeric()
                        ->visible(fn (Get $get): bool => $get('range_condition') === 'equal'),
                    TextInput::make('range_not_equal')
                        ->hiddenLabel()
                        ->numeric()
                        ->visible(fn (Get $get): bool => $get('range_condition') === 'not_equal'),
                    Grid::make([
                        'default' => 1,
                        'sm' => 2,
                    ])
                        ->schema([
                            TextInput::make('range_between_from')
                                ->hiddenLabel()
                                ->numeric(),
                            TextInput::make('range_between_to')
                                ->hiddenLabel()
                                ->numeric(),
                        ])
                        ->visible(fn (Get $get): bool => $get('range_condition') === 'between'),
                    TextInput::make('range_greater_than')
                        ->hiddenLabel()
                        ->numeric()
                        ->visible(fn (Get $get): bool => $get('range_condition') === 'greater_than'),
                    TextInput::make('range_greater_than_equal')
                        ->hiddenLabel()
                        ->numeric()
                        ->visible(fn (Get $get): bool => $get('range_condition') === 'greater_than_equal'),
                    TextInput::make('range_less_than')
                        ->hiddenLabel()
                        ->numeric()
                        ->visible(fn (Get $get): bool => $get('range_condition') === 'less_than'),
                    TextInput::make('range_less_than_equal')
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
