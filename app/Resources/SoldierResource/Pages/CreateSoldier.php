<?php

namespace App\Resources\SoldierResource\Pages;

use App\Models\User;
use App\Resources\SoldierResource;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;

class CreateSoldier extends CreateRecord
{
    use HasWizard;

    protected static string $resource = SoldierResource::class;

    public function beforeCreate()
    {
        $userName = User::where('last_name', $this->data['user']['last_name'])
            ->where('first_name', $this->data['user']['first_name'])
            ->pluck('last_name', 'first_name');

        if ($userName = $userName->get($this->data['user']['first_name']) == $this->data['user']['last_name']) {
            Notification::make()
                ->warning()
                ->title(__('This name already exists in the system!'))
                ->body(__('Add an identifier to the name so that it is not the same as another name. For example: ')
                .$this->data['user']['first_name'].' '.$this->data['user']['last_name'].'2')
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function afterCreate()
    {
        $user = $this->record->user;
        $this->data['shifts_assignment'] == 1 ? $user->assignRole('soldier', 'shifts-assignment') : $user->assignRole('soldier');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public static function getSteps(): array
    {
        return [
            Step::make('Personal Information')
                ->label(__('Personal Information'))
                ->schema([SoldierResource::personalDetails()]),
            Step::make('Soldier details')
                ->label(__('Soldier details'))
                ->schema([
                    Section::make()->schema(SoldierResource::soldierDetails())->columns(),
                ]),
            Step::make('Reserve days')
                ->label(__('Reserve dates'))
                ->visible(fn (Get $get) => $get('is_reservist'))
                ->schema([
                    Section::make()->schema(SoldierResource::reserveDays())->columns(),
                ]),
            Step::make('Additional settings')
                ->label(__('Additional settings'))
                ->schema([
                    Section::make()->schema(SoldierResource::constraints())->columns(),
                ]),
            Step::make('Constraints limit')
                ->label(__('Constraints limit'))
                ->schema([
                    Section::make()->schema(SoldierResource::constraintsLimit())->columns(),
                ]),
        ];
    }
}
