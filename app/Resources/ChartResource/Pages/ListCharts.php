<?php

namespace App\Resources\ChartResource\Pages;

use App\Filament\Widgets\ChartWidget;
use App\Resources\ChartResource;
use Filament\Resources\Pages\ListRecords;

class ListCharts extends ListRecords
{
    protected static string $resource = ChartResource::class;

    protected function getHeaderWidgets(): array
    {

        return [
            ChartWidget::make([
                'data'=> collect([
                    5.5 => collect([
                        'Dan' => 3,
                        'John' => 3,
                        'Gal' => 4.25,
                        'Or' => 5,
                    ]),
                    3.25 => collect([
                        'Yossi' => 3,
                        'Mor' => 3.05,
                    ])
                ]),
                'headerLable'=>__('Points')
            ]),
            ChartWidget::make([
                'data'=> collect([
                    5.5 => collect([
                        'Dan' => 3,
                        'John' => 3,
                        'Gal' => 4.25,
                        'Or' => 5,
                    ]),
                    3.25 => collect([
                        'Yossi' => 3,
                        'Mor' => 3.05,
                    ])
                ]),
                'headerLable'=>__('Weekends')
            ]),
            ChartWidget::make([
                'data'=> collect([
                    5.5 => collect([
                        'Dan' => 3,
                        'John' => 3,
                        'Gal' => 4.25,
                        'Or' => 5,
                    ]),
                    3.25 => collect([
                        'Yossi' => 3,
                        'Mor' => 3.05,
                    ])
                ]),
                'headerLable'=>__('Nights')
            ]),
            ChartWidget::make([
                'data'=> collect([
                    5.5 => collect([
                        'Dan' => 3,
                        'John' => 3,
                        'Gal' => 4.25,
                        'Or' => 5,
                    ]),
                    3.25 => collect([
                        'Yossi' => 3,
                        'Mor' => 3.05,
                    ])
                ]),
                'headerLable'=>__('Regulars')
            ]),
            ChartWidget::make([
                'data'=> collect([
                    5.5 => collect([
                        'Dan' => 3,
                        'John' => 3,
                        'Gal' => 4.25,
                        'Or' => 5,
                    ]),
                    3.25 => collect([
                        'Yossi' => 3,
                        'Mor' => 3.05,
                    ])
                ]),
                'headerLable'=>__('Alerts')
            ]),
            ChartWidget::make([
                'data'=> collect([
                    5.5 => collect([
                        'Dan' => 3,
                        'John' => 3,
                        'Gal' => 4.25,
                        'Or' => 5,
                    ]),
                    3.25 => collect([
                        'Yossi' => 3,
                        'Mor' => 3.05,
                    ])
                ]),
                'headerLable'=>__('In parallels')
            ]),
        ];
    }
}
