<?php

namespace App\Livewire;

use App\Models\Shift;
use App\Models\Soldier;
use App\Services\ChangeAssignment;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Livewire\DatabaseNotifications;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

class MyDatabaseNotifications extends DatabaseNotifications
{
    #[On('confirmExchange')]
    public function confirmExchange($approverRole, $requestingSoldier, $approvingSoldier, $shiftA, $shiftB)
    {
        $this->confirmExchangeByRole($approverRole, $requestingSoldier, $approvingSoldier, $shiftA, $shiftB);
    }

    protected function confirmExchangeByRole($approverRole, $requestingSoldier, $approvingSoldier, $shiftA, $shiftB)
    {
        $approverRole ?
            $this->commanderConfirmExchange($requestingSoldier, $approvingSoldier, $shiftA, $shiftB) :
            $this->soldierConfirmExchange($requestingSoldier, $approvingSoldier, $shiftA, $shiftB);
    }

    protected function commanderConfirmExchange($requestingSoldier, $approvingSoldier, $shiftA, $shiftB)
    {
        $requestingSoldier = Soldier::find($requestingSoldier);
        $approvingSoldier = Soldier::find($approvingSoldier);
        $shiftA = Shift::find($shiftA);
        $shiftB = Shift::find($shiftB);
        $changeAssignment = new ChangeAssignment($shiftA);
        $changeAssignment->exchange($shiftB);
        $this->dispatch('filament-fullcalendar--refresh');
        if ($requestingSoldier->team->commander->id !== $approvingSoldier->id) {
            $this->sendNotification(
                __('Approve exchange shift request'),
                __(
                    'Commander notification of approving exchange shift request for the requesting soldier',
                    [
                        'requestingSoldierName' => $requestingSoldier->user->displayName,
                        'shiftAName' => $shiftA->task->name,
                        'shiftAStart' => $shiftA->start_date,
                        'shiftAEnd' => $shiftA->end_date,
                        'approvingSoldierName' => $approvingSoldier->user->displayName,
                        'shiftBName' => $shiftB->task->name,
                        'shiftBStart' => $shiftB->start_date,
                        'shiftBEnd' => $shiftB->end_date,
                        'commanderName' => $requestingSoldier->team->commander->user->displayName,
                    ]
                ),
                [],
                $requestingSoldier->user
            );
            $this->sendNotification(
                __('Approve exchange shift request'),
                __(
                    'Commander notification of approving exchange shift request for the approving soldier',
                    [
                        'approvingSoldierName' => $approvingSoldier->user->displayName,
                        'requestingSoldierName' => $requestingSoldier->user->displayName,
                        'shiftBName' => $shiftB->task->name,
                        'shiftBStart' => $shiftB->start_date,
                        'shiftBEnd' => $shiftB->end_date,
                        'shiftAName' => $shiftA->task->name,
                        'shiftAStart' => $shiftA->start_date,
                        'shiftAEnd' => $shiftA->end_date,
                        'commanderName' => $requestingSoldier->team->commander->user->displayName,
                    ]
                ),
                [],
                $approvingSoldier->user
            );
        } else {
            $this->sendNotification(
                __('Approve exchange shift request'),
                __(
                    'Commander notification of approving exchange shift request for the requesting soldier',
                    [
                        'requestingSoldierName' => $requestingSoldier->user->displayName,
                        'shiftAName' => $shiftA->task->name,
                        'shiftAStart' => $shiftA->start_date,
                        'shiftAEnd' => $shiftA->end_date,
                        'approvingSoldierName' => $approvingSoldier->user->displayName,
                        'shiftBName' => $shiftB->task->name,
                        'shiftBStart' => $shiftB->start_date,
                        'shiftBEnd' => $shiftB->end_date,
                        'commanderName' => $requestingSoldier->team->commander->user->displayName,
                    ]
                ),
                [],
                $requestingSoldier->user
            );
        }
    }

    protected function soldierConfirmExchange($requestingSoldier, $approvingSoldier, $shiftA, $shiftB)
    {
        $requestingSoldier = Soldier::find($requestingSoldier);
        $approvingSoldier = Soldier::find($approvingSoldier);
        $shiftA = Shift::find($shiftA);
        $shiftB = Shift::find($shiftB);
        $commander = $requestingSoldier->team->commander->user;
        $this->sendNotification(
            __('Request for shift exchange'),
            __(
                'Request for shift exchange from commander',
                [
                    'commanderName' => $commander->displayName,
                    'requestingSoldierName' => $requestingSoldier->user->displayName,
                    'shiftAName' => $shiftA->task->name,
                    'shiftAStart' => $shiftA->start_date,
                    'shiftAEnd' => $shiftA->end_date,
                    'approvingSoldierName' => $approvingSoldier->user->displayName,
                    'shiftBName' => $shiftB->task->name,
                    'shiftBStart' => $shiftB->start_date,
                    'shiftBEnd' => $shiftB->end_date,
                ]
            ),
            [
                NotificationAction::make('confirm')
                    ->label(__('Confirm'))
                    ->color('success')
                    ->icon('heroicon-s-hand-thumb-up')
                    ->button()
                    ->dispatch('confirmExchange', [
                        'approverRole' => 'commander',
                        'requestingSoldier' => $requestingSoldier->id,
                        'approvingSoldier' => $approvingSoldier->id,
                        'shiftA' => $shiftA->id,
                        'shiftB' => $shiftB->id,
                    ])
                    ->close(),
                NotificationAction::make('deny')
                    ->label(__('Deny'))
                    ->color('danger')
                    ->icon('heroicon-m-hand-thumb-down')
                    ->button()
                    ->dispatch('denyExchange', [
                        'rejectorRole' => 'commander',
                        'requestingSoldier' => $requestingSoldier->id,
                        'rejectingSoldier' => $approvingSoldier->id,
                        'shiftA' => $shiftA->id,
                        'shiftB' => $shiftB->id,
                    ])
                    ->close(),
            ],
            $commander
        );
    }

    #[On('denyExchange')]
    public function denyExchange($rejectorRole, $requestingSoldier, $rejectingSoldier, $shiftA, $shiftB): void
    {
        $this->denyExchangeByRole($rejectorRole, $requestingSoldier, $rejectingSoldier, $shiftA, $shiftB);
    }

    protected function denyExchangeByRole($rejectorRole, $requestingSoldier, $rejectingSoldier, $shiftA, $shiftB)
    {
        $rejectorRole ?
            $this->commanderDenyExchange($requestingSoldier, $rejectingSoldier, $shiftA, $shiftB) :
            $this->soldierDenyExchange($requestingSoldier, $rejectingSoldier, $shiftA, $shiftB);
    }

    protected function commanderDenyExchange($requestingSoldier, $rejectingSoldier, $shiftA, $shiftB)
    {
        $requestingSoldier = Soldier::find($requestingSoldier);
        $rejectingSoldier = Soldier::find($rejectingSoldier);
        $shiftA = Shift::find($shiftA);
        $shiftB = Shift::find($shiftB);
        if ($requestingSoldier->team->commander->id !== $rejectingSoldier->id) {
            $this->sendNotification(
                __('Deny exchange shift request'),
                __(
                    'Commander notification of rejection exchange shift request for the requesting soldier',
                    [
                        'requestingSoldierName' => $requestingSoldier->user->displayName,
                        'shiftAName' => $shiftA->task->name,
                        'shiftAStart' => $shiftA->start_date,
                        'shiftAEnd' => $shiftA->end_date,
                        'rejectingSoldierName' => $rejectingSoldier->user->displayName,
                        'shiftBName' => $shiftB->task->name,
                        'shiftBStart' => $shiftB->start_date,
                        'shiftBEnd' => $shiftB->end_date,
                        'commanderName' => $requestingSoldier->team->commander->user->displayName,
                    ]
                ),
                [],
                $requestingSoldier->user
            );
            $this->sendNotification(
                __('Deny exchange shift request'),
                __(
                    'Commander notification of rejection exchange shift request for the rejection soldier',
                    [
                        'rejectingSoldierName' => $rejectingSoldier->user->displayName,
                        'requestingSoldierName' => $requestingSoldier->user->displayName,
                        'shiftBName' => $shiftB->task->name,
                        'shiftBStart' => $shiftB->start_date,
                        'shiftBEnd' => $shiftB->end_date,
                        'shiftAName' => $shiftA->task->name,
                        'shiftAStart' => $shiftA->start_date,
                        'shiftAEnd' => $shiftA->end_date,
                        'commanderName' => $requestingSoldier->team->commander->user->displayName,
                    ]
                ),
                [],
                $rejectingSoldier->user
            );
        } else {
            $this->sendNotification(
                __('Deny exchange shift request'),
                __(
                    'Commander notification of rejection exchange shift request for the requesting soldier',
                    [
                        'requestingSoldierName' => $requestingSoldier->user->displayName,
                        'shiftAName' => $shiftA->task->name,
                        'shiftAStart' => $shiftA->start_date,
                        'shiftAEnd' => $shiftA->end_date,
                        'rejectingSoldierName' => $rejectingSoldier->user->displayName,
                        'shiftBName' => $shiftB->task->name,
                        'shiftBStart' => $shiftB->start_date,
                        'shiftBEnd' => $shiftB->end_date,
                        'commanderName' => $requestingSoldier->team->commander->user->displayName,
                    ]
                ),
                [],
                $requestingSoldier->user
            );
        }
    }

    protected function soldierDenyExchange($requestingSoldier, $rejectingSoldier, $shiftA, $shiftB)
    {
        $requestingSoldier = Soldier::find($requestingSoldier);
        $rejectingSoldier = Soldier::find($rejectingSoldier);
        $shiftA = Shift::find($shiftA);
        $shiftB = Shift::find($shiftB);
        $this->sendNotification(
            __('Deny exchange shift request'),
            __(
                'Soldier notification of rejection exchange shift request',
                [
                    'requestingSoldierName' => $requestingSoldier->user->displayName,
                    'shiftBName' => $shiftB->task->name,
                    'shiftBStart' => $shiftB->start_date,
                    'shiftBEnd' => $shiftB->end_date,
                    'shiftAName' => $shiftA->task->name,
                    'shiftAStart' => $shiftA->start_date,
                    'shiftAEnd' => $shiftA->end_date,
                    'rejectingSoldierName' => $rejectingSoldier->user->displayName,
                ]
            ),
            [],
            $requestingSoldier->user
        );
    }

    #[On('confirmChange')]
    public function confirmChange($approverRole, $shift, $soldierId)
    {
        $this->confirmChangeByRole($approverRole, $shift, $soldierId);
    }

    protected function confirmChangeByRole($approverRole, $shift, $soldierId)
    {
        $approverRole ?
            $this->commanderConfirmChange($shift, $soldierId) :
            $this->soldierConfirmChange($shift, $soldierId);
    }

    protected function commanderConfirmChange($shift, $soldierId)
    {
        $shift = Shift::find($shift);
        $requestingSoldier = Soldier::find($shift->soldier_id);
        $approvingSoldier = Soldier::find($soldierId);
        Shift::where('id', $shift->id)->update(['soldier_id' => $soldierId]);
        $this->dispatch('filament-fullcalendar--refresh');
        if ($requestingSoldier->team->commander->id !== $approvingSoldier->id) {
            $this->sendNotification(
                __('Approve change shift request'),
                __(
                    'Commander notification of approving change shift request for the requesting soldier',
                    [
                        'requestingSoldierName' => $requestingSoldier->user->displayName,
                        'shiftName' => $shift->task->name,
                        'shiftStart' => $shift->start_date,
                        'shiftEnd' => $shift->end_date,
                        'commanderName' => $requestingSoldier->team->commander->user->displayName,
                    ]
                ),
                [],
                $requestingSoldier->user
            );
            $this->sendNotification(
                __('Approve change shift request'),
                __(
                    'Commander notification of approving change shift request for the approving soldier',
                    [
                        'approvingSoldierName' => $approvingSoldier->user->displayName,
                        'requestingSoldierName' => $requestingSoldier->user->displayName,
                        'shiftName' => $shift->task->name,
                        'shiftStart' => $shift->start_date,
                        'shiftEnd' => $shift->end_date,
                        'commanderName' => $requestingSoldier->team->commander->user->displayName,
                    ]
                ),
                [],
                $approvingSoldier->user
            );
        } else {
            $this->sendNotification(
                __('Approve change shift request'),
                __(
                    'Commander notification of approving change shift request for the requesting soldier',
                    [
                        'requestingSoldierName' => $requestingSoldier->user->displayName,
                        'shiftName' => $shift->task->name,
                        'shiftStart' => $shift->start_date,
                        'shiftEnd' => $shift->end_date,
                        'commanderName' => $requestingSoldier->team->commander->user->displayName,
                    ]
                ),
                [],
                $requestingSoldier->user
            );
        }
    }

    protected function soldierConfirmChange($shift, $soldierId)
    {
        $shift = Shift::find($shift);
        $requestingSoldier = Soldier::find($shift->soldier_id);
        $approvingSoldier = Soldier::find($soldierId);

        $commander = $requestingSoldier->team->commander->user;
        $this->sendNotification(
            __('Request for shift change'),
            __(
                'Request for shift change from commander',
                [
                    'commanderName' => $commander->displayName,
                    'requestingSoldierName' => $requestingSoldier->user->displayName,
                    'shiftName' => $shift->task->name,
                    'shiftStart' => $shift->start_date,
                    'shiftEnd' => $shift->end_date,
                    'approvingSoldierName' => $approvingSoldier->user->displayName,
                ]
            ),
            [
                NotificationAction::make('confirm')
                    ->label(__('Confirm'))
                    ->color('success')
                    ->icon('heroicon-s-hand-thumb-up')
                    ->button()
                    ->dispatch('confirmChange', [
                        'approverRole' => 'commander',
                        'shift' => $shift->id,
                        'soldierId' => $soldierId,
                    ])
                    ->close(),
                NotificationAction::make('deny')
                    ->label(__('Deny'))
                    ->color('danger')
                    ->icon('heroicon-m-hand-thumb-down')
                    ->button()
                    ->dispatch('denyChange', [
                        'rejectorRole' => 'commander',
                        'shift' => $shift->id,
                        'soldierId' => $soldierId,
                    ])
                    ->close(),
            ],
            $commander
        );
    }

    #[On('denyChange')]
    public function denyChange($rejectorRole, $shift, $soldierId): void
    {
        $this->denyChangeByRole($rejectorRole, $shift, $soldierId);
    }

    protected function denyChangeByRole($rejectorRole, $shift, $soldierId)
    {
        $rejectorRole ?
            $this->commanderDenyChange($shift, $soldierId) :
            $this->soldierDenyChange($shift, $soldierId);
    }

    protected function commanderDenyChange($shift, $soldierId)
    {
        $shift = Shift::find($shift);
        $requestingSoldier = Soldier::find($shift->soldier_id);
        $approvingSoldier = Soldier::find($soldierId);
        if ($requestingSoldier->team->commander->id !== $approvingSoldier->id) {
            $this->sendNotification(
                __('Deny change shift request'),
                __(
                    'Commander notification of rejection change shift request for the requesting soldier',
                    [
                        'requestingSoldierName' => $requestingSoldier->user->displayName,
                        'shiftName' => $shift->task->name,
                        'shiftStart' => $shift->start_date,
                        'shiftEnd' => $shift->end_date,
                        'approvingSoldierName' => $approvingSoldier->user->displayName,
                        'commanderName' => $requestingSoldier->team->commander->user->displayName,
                    ]
                ),
                [],
                $requestingSoldier->user
            );
            $this->sendNotification(
                __('Deny change shift request'),
                __(
                    'Commander notification of rejection change shift request for the approving soldier',
                    [
                        'approvingSoldierName' => $approvingSoldier->user->displayName,
                        'requestingSoldierName' => $requestingSoldier->user->displayName,
                        'shiftName' => $shift->task->name,
                        'shiftStart' => $shift->start_date,
                        'shiftEnd' => $shift->end_date,
                        'commanderName' => $requestingSoldier->team->commander->user->displayName,
                    ]
                ),
                [],
                $approvingSoldier->user
            );
        } else {
            $this->sendNotification(
                __('Deny change shift request'),
                __(
                    'Commander notification of rejection change shift request for the requesting soldier',
                    [
                        'requestingSoldierName' => $requestingSoldier->user->displayName,
                        'shiftName' => $shift->task->name,
                        'shiftStart' => $shift->start_date,
                        'shiftEnd' => $shift->end_date,
                        'approvingSoldierName' => $approvingSoldier->user->displayName,
                        'commanderName' => $requestingSoldier->team->commander->user->displayName,
                    ]
                ),
                [],
                $requestingSoldier->user
            );
        }
    }

    protected function soldierDenyChange($shift, $soldierId)
    {
        $shift = Shift::find($shift);
        $requestingSoldier = Soldier::find($shift->soldier_id);
        $rejectingSoldier = Soldier::find($soldierId);
        $this->sendNotification(
            __('Deny change shift request'),
            __(
                'Soldier notification of rejection change shift request',
                [
                    'requestingSoldierName' => $requestingSoldier->user->displayName,
                    'shiftName' => $shift->task->name,
                    'shiftStart' => $shift->start_date,
                    'shiftEnd' => $shift->end_date,
                    'rejectingSoldierName' => $rejectingSoldier->user->displayName,
                ]
            ),
            [],
            $requestingSoldier->user
        );
    }

    protected function sendNotification($title, $body, $actions, $user)
    {
        Notification::make()
            ->title($title)
            ->persistent()
            ->body(
                $body
            )
            ->actions(
                $actions
            )
            ->sendToDatabase($user, true);
    }
}
