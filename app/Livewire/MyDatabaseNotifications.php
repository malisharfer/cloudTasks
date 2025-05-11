<?php

namespace App\Livewire;

use App\Filament\Notifications\MyNotification;
use App\Models\Constraint;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\User;
use App\Services\ChangeAssignment;
use Filament\Facades\Filament;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Livewire\DatabaseNotifications;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\On;

class MyDatabaseNotifications extends DatabaseNotifications
{
    public function getUser(): Model|Authenticatable|null
    {
        return Filament::auth()->user();
    }

    public function getPollingInterval(): ?string
    {
        return Filament::getDatabaseNotificationsPollingInterval();
    }

    public function getTrigger(): View
    {
        return view('filament-panels::components.topbar.database-notifications-trigger');
    }

    #[On('confirmExchange')]
    public function confirmExchange($approverRole, $soldierAId, $soldierBId, $shiftAId, $shiftBId, $requesterId)
    {
        $this->confirmExchangeByRole($approverRole, $soldierAId, $soldierBId, $shiftAId, $shiftBId, $requesterId);
    }

    protected function confirmExchangeByRole($approverRole, $soldierAId, $soldierBId, $shiftAId, $shiftBId, $requesterId)
    {
        match ($approverRole) {
            'shifts-assignment' => $this->shiftAssignmentConfirmExchange($soldierAId, $soldierBId, $shiftAId, $shiftBId, $requesterId),
            'team-commander', 'department-commander' => $this->commanderConfirmExchange($soldierAId, $soldierBId, $shiftAId, $shiftBId),
            'soldier' => $this->soldierConfirmExchange($soldierAId, $soldierBId, $shiftAId, $shiftBId)
        };
    }

    protected function shiftAssignmentConfirmExchange($soldierAId, $soldierBId, $shiftAId, $shiftBId, $requesterId)
    {
        $soldierA = Soldier::find($soldierAId);
        $soldierB = Soldier::find($soldierBId);
        $shiftA = Shift::find($shiftAId);
        $shiftB = Shift::find($shiftBId);
        $this->shiftAssignmentExchange($shiftA, $shiftB);
        $this->deleteNonRelevantNotifications($shiftAId.'-'.$shiftBId);
        $this->sendNotification(
            __('Exchange shift'),
            __(
                'Shifts assignment notification of exchanging shifts for first soldier',
                [
                    'soldierAName' => $soldierA->user->displayName,
                    'shiftAName' => $shiftA->task()->withTrashed()->first()->name,
                    'shiftAStart' => $shiftA->start_date,
                    'shiftAEnd' => $shiftA->end_date,
                    'soldierBName' => $soldierB->user->displayName,
                    'shiftBName' => $shiftB->task()->withTrashed()->first()->name,
                    'shiftBStart' => $shiftB->start_date,
                    'shiftBEnd' => $shiftB->end_date,
                    'shiftsAssignmentName' => auth()->user()->displayName,
                ]
            ),
            [],
            $soldierA->user
        );
        $this->sendNotification(
            __('Exchange shift'),
            __(
                'Shifts assignment notification of exchanging shifts for second soldier',
                [
                    'soldierBName' => $soldierB->user->displayName,
                    'shiftBName' => $shiftB->task()->withTrashed()->first()->name,
                    'shiftBStart' => $shiftB->start_date,
                    'shiftBEnd' => $shiftB->end_date,
                    'soldierAName' => $soldierA->user->displayName,
                    'shiftAName' => $shiftA->task()->withTrashed()->first()->name,
                    'shiftAStart' => $shiftA->start_date,
                    'shiftAEnd' => $shiftA->end_date,
                    'shiftsAssignmentName' => auth()->user()->displayName,
                ]
            ),
            [],
            $soldierB->user
        );
        $this->sendNotification(
            __('Exchange shift'),
            __(
                'Shifts assignment notification of exchanging shifts for commander',
                [
                    'commanderName' => User::find($requesterId)->displayName,
                    'shiftAName' => $shiftA->task()->withTrashed()->first()->name,
                    'soldierAName' => $soldierA->user->displayName,
                    'shiftAStart' => $shiftA->start_date,
                    'shiftAEnd' => $shiftA->end_date,
                    'soldierBName' => $soldierB->user->displayName,
                    'shiftBName' => $shiftB->task()->withTrashed()->first()->name,
                    'shiftBStart' => $shiftB->start_date,
                    'shiftBEnd' => $shiftB->end_date,
                    'shiftsAssignmentName' => auth()->user()->displayName,
                ]
            ),
            [],
            User::find($requesterId)
        );
        $this->getShiftsAssignments()
            ->filter(fn ($shiftsAssignment) => $shiftsAssignment->id !== auth()->user()->id)
            ->map(
                fn ($shiftsAssignment) => $this->sendNotification(
                    __('Exchange shift'),
                    __(
                        'Shifts assignment notification of exchanging shifts for shifts assignment',
                        [
                            'shiftsAssignmentName' => $shiftsAssignment->displayName,
                            'shiftAName' => $shiftA->task()->withTrashed()->first()->name,
                            'soldierAName' => $soldierA->user->displayName,
                            'shiftAStart' => $shiftA->start_date,
                            'shiftAEnd' => $shiftA->end_date,
                            'soldierBName' => $soldierB->user->displayName,
                            'shiftBName' => $shiftB->task()->withTrashed()->first()->name,
                            'shiftBStart' => $shiftB->start_date,
                            'shiftBEnd' => $shiftB->end_date,
                            'shiftsAssignment2Name' => auth()->user()->displayName,
                        ]
                    ),
                    [],
                    $shiftsAssignment
                )
            );
    }

    protected function shiftAssignmentExchange($shiftAId, $shiftBId)
    {
        $changeAssignment = new ChangeAssignment($shiftAId);
        $changeAssignment->exchange($shiftBId);
        $this->dispatch('filament-fullcalendar--refresh');
    }

    protected function commanderConfirmExchange($soldierAId, $soldierBId, $shiftAId, $shiftBId)
    {
        $shiftA = Shift::find($shiftAId);
        $shiftB = Shift::find($shiftBId);
        $this->getShiftsAssignments()
            ->map(
                fn ($shiftsAssignment) => $this->sendNotification(
                    __('Request for shift exchange'),
                    __(
                        'Request for shift exchange from shifts assignments',
                        [
                            'shiftsAssignmentName' => $shiftsAssignment->displayName,
                            'soldierAName' => Soldier::find($soldierAId)->user->displayName,
                            'shiftAName' => $shiftA->task()->withTrashed()->first()->name,
                            'shiftAStart' => $shiftA->start_date,
                            'shiftAEnd' => $shiftA->end_date,
                            'soldierBName' => Soldier::find($soldierBId)->user->displayName,
                            'shiftBName' => $shiftB->task()->withTrashed()->first()->name,
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
                                'approverRole' => 'shifts-assignment',
                                'soldierAId' => $shiftA->soldier_id,
                                'soldierBId' => $shiftB->soldier_id,
                                'shiftAId' => $shiftA->id,
                                'shiftBId' => $shiftB->id,
                                'requesterId' => auth()->user()->id,
                            ])
                            ->close(),
                        NotificationAction::make('deny')
                            ->label(__('Deny'))
                            ->color('danger')
                            ->icon('heroicon-m-hand-thumb-down')
                            ->button()
                            ->dispatch('denyExchange', [
                                'rejectorRole' => 'shifts-assignment',
                                'soldierAId' => $shiftA->soldier_id,
                                'soldierBId' => $shiftB->soldier_id,
                                'shiftAId' => $shiftA->id,
                                'shiftBId' => $shiftB->id,
                                'requesterId' => auth()->user()->id,
                                'sendToSoldiers' => true,
                            ])
                            ->close(),
                    ],
                    $shiftsAssignment,
                    $shiftA->id.'-'.$shiftB->id
                )
            );
    }

    protected function soldierConfirmExchange($soldierAId, $soldierBId, $shiftAId, $shiftBId)
    {
        $soldierA = Soldier::find($soldierAId);
        $soldierB = Soldier::find($soldierBId);
        $shiftA = Shift::find($shiftAId);
        $shiftB = Shift::find($shiftBId);
        $commander = $soldierA->team->commander->user;
        $this->sendNotification(
            __('Request for shift exchange'),
            __(
                'Request for shift exchange from commander',
                [
                    'commanderName' => $commander->displayName,
                    'soldierAName' => $soldierA->user->displayName,
                    'shiftAName' => $shiftA->task()->withTrashed()->first()->name,
                    'shiftAStart' => $shiftA->start_date,
                    'shiftAEnd' => $shiftA->end_date,
                    'soldierBName' => $soldierB->user->displayName,
                    'shiftBName' => $shiftB->task()->withTrashed()->first()->name,
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
                        'approverRole' => 'team-commander',
                        'soldierAId' => $soldierA->id,
                        'soldierBId' => $soldierB->id,
                        'shiftAId' => $shiftA->id,
                        'shiftBId' => $shiftB->id,
                        'requesterId' => auth()->user()->id,
                    ])
                    ->close(),
                NotificationAction::make('deny')
                    ->label(__('Deny'))
                    ->color('danger')
                    ->icon('heroicon-m-hand-thumb-down')
                    ->button()
                    ->dispatch('denyExchange', [
                        'rejectorRole' => 'team-commander',
                        'soldierAId' => $soldierA->id,
                        'soldierBId' => $soldierB->id,
                        'shiftAId' => $shiftA->id,
                        'shiftBId' => $shiftB->id,
                        'requesterId' => auth()->user()->id,
                        'sendToSoldiers' => true,
                    ])
                    ->close(),
            ],
            $commander
        );
    }

    #[On('denyExchange')]
    public function denyExchange($rejectorRole, $soldierAId, $soldierBId, $shiftAId, $shiftBId, $requesterId, $sendToSoldiers): void
    {
        $this->denyExchangeByRole($rejectorRole, $soldierAId, $soldierBId, $shiftAId, $shiftBId, $requesterId, $sendToSoldiers);
    }

    protected function denyExchangeByRole($rejectorRole, $soldierAId, $soldierBId, $shiftAId, $shiftBId, $requesterId, $sendToSoldiers)
    {
        match ($rejectorRole) {
            'shifts-assignment' => $this->shiftAssignmentDenyExchange($soldierAId, $soldierBId, $shiftAId, $shiftBId, $requesterId, $sendToSoldiers),
            'team-commander', 'department-commander' => $this->commanderDenyExchange($soldierAId, $soldierBId, $shiftAId, $shiftBId),
            'soldier' => $this->soldierDenyExchange($soldierAId, $soldierBId, $shiftAId, $shiftBId)
        };
    }

    protected function shiftAssignmentDenyExchange($soldierAId, $soldierBId, $shiftAId, $shiftBId, $requesterId, $sendToSoldiers)
    {
        $soldierA = Soldier::find($soldierAId);
        $soldierB = Soldier::find($soldierBId);
        $shiftA = Shift::find($shiftAId);
        $shiftB = Shift::find($shiftBId);
        $this->deleteNonRelevantNotifications($shiftAId.'-'.$shiftBId);
        if ($sendToSoldiers) {
            $this->sendNotification(
                __('Deny exchange shift request'),
                __(
                    'Shifts assignment notification of deny exchanging shifts for first soldier',
                    [
                        'soldierAName' => $soldierA->user->displayName,
                        'shiftAName' => $shiftA->task()->withTrashed()->first()->name,
                        'shiftAStart' => $shiftA->start_date,
                        'shiftAEnd' => $shiftA->end_date,
                        'shiftBName' => $shiftB->task()->withTrashed()->first()->name,
                        'soldierBName' => $soldierB->user->displayName,
                        'shiftBStart' => $shiftB->start_date,
                        'shiftBEnd' => $shiftB->end_date,
                        'shiftsAssignmentName' => auth()->user()->displayName,
                    ]
                ),
                [],
                $soldierA->user
            );
            $this->sendNotification(
                __('Deny exchange shift request'),
                __(
                    'Shifts assignment notification of deny exchanging shifts for second soldier',
                    [
                        'soldierBName' => $soldierB->user->displayName,
                        'soldierAName' => $soldierA->user->displayName,
                        'shiftAName' => $shiftA->task()->withTrashed()->first()->name,
                        'shiftAStart' => $shiftA->start_date,
                        'shiftAEnd' => $shiftA->end_date,
                        'shiftBName' => $shiftB->task()->withTrashed()->first()->name,
                        'shiftBStart' => $shiftB->start_date,
                        'shiftBEnd' => $shiftB->end_date,
                        'shiftsAssignmentName' => auth()->user()->displayName,
                    ]
                ),
                [],
                $soldierB->user
            );
        }
        $this->sendNotification(
            __('Deny exchange shift request'),
            __(
                'Shifts assignment notification of deny exchanging shifts for commander',
                [
                    'commanderName' => User::find($requesterId)->displayName,
                    'shiftAName' => $shiftA->task()->withTrashed()->first()->name,
                    'soldierAName' => $soldierA->user->displayName,
                    'shiftAStart' => $shiftA->start_date,
                    'shiftAEnd' => $shiftA->end_date,
                    'shiftBName' => $shiftB->task()->withTrashed()->first()->name,
                    'soldierBName' => $soldierB->user->displayName,
                    'shiftBStart' => $shiftB->start_date,
                    'shiftBEnd' => $shiftB->end_date,
                    'shiftsAssignmentName' => auth()->user()->displayName,
                ]
            ),
            [],
            User::find($requesterId)
        );
        $this->getShiftsAssignments()
            ->filter(fn ($shiftsAssignment) => $shiftsAssignment->id !== auth()->user()->id)
            ->map(
                fn ($shiftsAssignment) => $this->sendNotification(
                    __('Deny exchange shift request'),
                    __(
                        'Shifts assignment notification of deny exchanging shifts for shifts assignment',
                        [
                            'shiftsAssignmentName' => $shiftsAssignment->displayName,
                            'shiftAName' => $shiftA->task()->withTrashed()->first()->name,
                            'soldierAName' => $soldierA->user->displayName,
                            'shiftAStart' => $shiftA->start_date,
                            'shiftAEnd' => $shiftA->end_date,
                            'shiftBName' => $shiftB->task()->withTrashed()->first()->name,
                            'soldierBName' => $soldierB->user->displayName,
                            'shiftBStart' => $shiftB->start_date,
                            'shiftBEnd' => $shiftB->end_date,
                            'shiftsAssignment2Name' => auth()->user()->displayName,
                        ]
                    ),
                    [],
                    $shiftsAssignment
                )
            );
    }

    protected function commanderDenyExchange($soldierAId, $soldierBId, $shiftAId, $shiftBId)
    {
        $soldierA = Soldier::find($soldierAId);
        $soldierB = Soldier::find($soldierBId);
        $shiftA = Shift::find($shiftAId);
        $shiftB = Shift::find($shiftBId);
        if ($soldierA->team->commander->id !== $soldierB->id) {
            $this->sendNotification(
                __('Deny exchange shift request'),
                __(
                    'Commander notification of deny exchanging shifts for the first soldier',
                    [
                        'soldierAName' => $soldierA->user->displayName,
                        'shiftAName' => $shiftA->task()->withTrashed()->first()->name,
                        'shiftAStart' => $shiftA->start_date,
                        'shiftAEnd' => $shiftA->end_date,
                        'soldierBName' => $soldierB->user->displayName,
                        'shiftBName' => $shiftB->task()->withTrashed()->first()->name,
                        'shiftBStart' => $shiftB->start_date,
                        'shiftBEnd' => $shiftB->end_date,
                        'commanderName' => $soldierA->team->commander->user->displayName,
                    ]
                ),
                [],
                $soldierA->user
            );
            $this->sendNotification(
                __('Deny exchange shift request'),
                __(
                    'Commander notification of deny exchanging shifts for the second soldier',
                    [
                        'soldierBName' => $soldierB->user->displayName,
                        'soldierAName' => $soldierA->user->displayName,
                        'shiftBName' => $shiftB->task()->withTrashed()->first()->name,
                        'shiftBStart' => $shiftB->start_date,
                        'shiftBEnd' => $shiftB->end_date,
                        'shiftAName' => $shiftA->task()->withTrashed()->first()->name,
                        'shiftAStart' => $shiftA->start_date,
                        'shiftAEnd' => $shiftA->end_date,
                        'commanderName' => $soldierA->team->commander->user->displayName,
                    ]
                ),
                [],
                $soldierB->user
            );
        } else {
            $this->sendNotification(
                __('Deny exchange shift request'),
                __(
                    'Commander notification of deny exchanging shifts for the first soldier',
                    [
                        'soldierAName' => $soldierA->user->displayName,
                        'shiftAName' => $shiftA->task()->withTrashed()->first()->name,
                        'shiftAStart' => $shiftA->start_date,
                        'shiftAEnd' => $shiftA->end_date,
                        'soldierBName' => $soldierB->user->displayName,
                        'shiftBName' => $shiftB->task()->withTrashed()->first()->name,
                        'shiftBStart' => $shiftB->start_date,
                        'shiftBEnd' => $shiftB->end_date,
                        'commanderName' => $soldierA->team->commander->user->displayName,
                    ]
                ),
                [],
                $soldierA->user
            );
        }
    }

    protected function soldierDenyExchange($soldierAId, $soldierBId, $shiftAId, $shiftBId)
    {
        $soldierA = Soldier::find($soldierAId);
        $soldierB = Soldier::find($soldierBId);
        $shiftA = Shift::find($shiftAId);
        $shiftB = Shift::find($shiftBId);
        $this->sendNotification(
            __('Deny exchange shift request'),
            __(
                'Soldier notification of deny exchange shift request',
                [
                    'soldierAName' => $soldierA->user->displayName,
                    'shiftBName' => $shiftB->task()->withTrashed()->first()->name,
                    'shiftBStart' => $shiftB->start_date,
                    'shiftBEnd' => $shiftB->end_date,
                    'shiftAName' => $shiftA->task()->withTrashed()->first()->name,
                    'shiftAStart' => $shiftA->start_date,
                    'shiftAEnd' => $shiftA->end_date,
                    'soldierBName' => $soldierB->user->displayName,
                ]
            ),
            [],
            $soldierA->user
        );
    }

    #[On('confirmChange')]
    public function confirmChange($approverRole, $shiftId, $soldierId, $requesterId)
    {
        $this->confirmChangeByRole($approverRole, $shiftId, $soldierId, $requesterId);
    }

    protected function confirmChangeByRole($approverRole, $shiftId, $soldierId, $requesterId)
    {
        match ($approverRole) {
            'shifts-assignment' => $this->shiftAssignmentConfirmChange($shiftId, $soldierId, $requesterId),
            'team-commander', 'department-commander' => $this->commanderConfirmChange($shiftId, $soldierId),
            'soldier' => $this->soldierConfirmChange($shiftId, $soldierId)
        };
    }

    protected function shiftAssignmentConfirmChange($shiftId, $soldierId, $requesterId)
    {
        $this->dispatch('filament-fullcalendar--refresh');
        $shift = Shift::find($shiftId);
        $soldierA = Soldier::find($shift->soldier_id);
        $soldierB = Soldier::find($soldierId);
        $this->deleteNonRelevantNotifications($shiftId.'-'.$shift->soldier_id.'-'.$soldierId);
        Shift::where('id', $shiftId)->update(['soldier_id' => $soldierId]);
        $this->sendNotification(
            __('Change shift'),
            __(
                'Shifts assignment notification of changing shifts for first soldier',
                [
                    'soldierName' => $soldierA->user->displayName,
                    'shiftName' => $shift->task()->withTrashed()->first()->name,
                    'shiftStart' => $shift->start_date,
                    'shiftEnd' => $shift->end_date,
                    'shiftsAssignmentName' => auth()->user()->displayName,
                ]
            ),
            [],
            $soldierA->user
        );
        $this->sendNotification(
            __('Change shift'),
            __(
                'Shifts assignment notification of changing shifts for second soldier',
                [
                    'soldierName' => $soldierB->user->displayName,
                    'shiftName' => $shift->task()->withTrashed()->first()->name,
                    'shiftStart' => $shift->start_date,
                    'shiftEnd' => $shift->end_date,
                    'shiftsAssignmentName' => auth()->user()->displayName,
                ]
            ),
            [],
            $soldierB->user
        );
        $this->sendNotification(
            __('Change shift'),
            __(
                'Shifts assignment notification of changing shifts for commander',
                [
                    'commanderName' => User::find($requesterId)->displayName,
                    'shiftName' => $shift->task()->withTrashed()->first()->name,
                    'soldierAName' => $soldierA->user->displayName,
                    'shiftStart' => $shift->start_date,
                    'shiftEnd' => $shift->end_date,
                    'soldierBName' => $soldierB->user->displayName,
                    'shiftsAssignmentName' => auth()->user()->displayName,
                ]
            ),
            [],
            User::find($requesterId)
        );
        $this->getShiftsAssignments()
            ->filter(fn ($shiftsAssignment) => $shiftsAssignment->id !== auth()->user()->id)
            ->map(
                fn ($shiftsAssignment) => $this->sendNotification(
                    __('Change shift'),
                    __(
                        'Shifts assignment notification of changing shifts for shifts assignment',
                        [
                            'shiftsAssignmentName' => $shiftsAssignment->displayName,
                            'shiftName' => $shift->task()->withTrashed()->first()->name,
                            'soldierAName' => $soldierA->user->displayName,
                            'shiftStart' => $shift->start_date,
                            'shiftEnd' => $shift->end_date,
                            'soldierBName' => $soldierB->user->displayName,
                            'shiftsAssignment2Name' => auth()->user()->displayName,
                        ]
                    ),
                    [],
                    $shiftsAssignment
                )
            );
    }

    protected function commanderConfirmChange($shiftId, $soldierId)
    {
        $shift = Shift::find($shiftId);
        $this->getShiftsAssignments()
            ->map(
                fn ($shiftsAssignment) => $this->sendNotification(
                    __('Request for shift change'),
                    __(
                        'Request for shift change from shifts assignments',
                        [
                            'shiftsAssignmentName' => $shiftsAssignment->displayName,
                            'shiftName' => $shift->task()->withTrashed()->first()->name,
                            'soldierAName' => Soldier::find($shift->soldier_id)->user->displayName,
                            'shiftStart' => $shift->start_date,
                            'shiftEnd' => $shift->end_date,
                            'soldierBName' => Soldier::find($soldierId)->user->displayName,
                        ]
                    ),
                    [
                        NotificationAction::make('confirm')
                            ->label(__('Confirm'))
                            ->color('success')
                            ->icon('heroicon-s-hand-thumb-up')
                            ->button()
                            ->dispatch('confirmChange', [
                                'approverRole' => 'shifts-assignment',
                                'shiftId' => $shift->id,
                                'soldierId' => $soldierId,
                                'requesterId' => auth()->user()->id,
                            ])
                            ->close(),
                        NotificationAction::make('deny')
                            ->label(__('Deny'))
                            ->color('danger')
                            ->icon('heroicon-m-hand-thumb-down')
                            ->button()
                            ->dispatch('denyChange', [
                                'rejectorRole' => 'shifts-assignment',
                                'shiftId' => $shift->id,
                                'soldierId' => $soldierId,
                                'requesterId' => auth()->user()->id,
                                'sendToSoldiers' => true,
                            ])
                            ->close(),
                    ],
                    $shiftsAssignment,
                    $shift->id.'-'.$shift->soldier_id.'-'.$soldierId
                )
            );
    }

    protected function soldierConfirmChange($shiftId, $soldierId)
    {
        $shift = Shift::find($shiftId);
        $soldierA = Soldier::find($shift->soldier_id);
        $soldierB = Soldier::find($soldierId);
        $commander = $soldierA->team->commander->user;
        $this->sendNotification(
            __('Request for shift change'),
            __(
                'Request for shift change from commander',
                [
                    'commanderName' => $commander->displayName,
                    'soldierAName' => $soldierA->user->displayName,
                    'shiftName' => $shift->task()->withTrashed()->first()->name,
                    'shiftStart' => $shift->start_date,
                    'shiftEnd' => $shift->end_date,
                    'soldierBName' => $soldierB->user->displayName,
                ]
            ),
            [
                NotificationAction::make('confirm')
                    ->label(__('Confirm'))
                    ->color('success')
                    ->icon('heroicon-s-hand-thumb-up')
                    ->button()
                    ->dispatch('confirmChange', [
                        'approverRole' => 'team-commander',
                        'shiftId' => $shift->id,
                        'soldierId' => $soldierId,
                        'requesterId' => auth()->user()->id,
                    ])
                    ->close(),
                NotificationAction::make('deny')
                    ->label(__('Deny'))
                    ->color('danger')
                    ->icon('heroicon-m-hand-thumb-down')
                    ->button()
                    ->dispatch('denyChange', [
                        'rejectorRole' => 'team-commander',
                        'shiftId' => $shift->id,
                        'soldierId' => $soldierId,
                        'requesterId' => auth()->user()->id,
                        'sendToSoldiers' => true,
                    ])
                    ->close(),
            ],
            $commander
        );
    }

    #[On('denyChange')]
    public function denyChange($rejectorRole, $shiftId, $soldierId, $requesterId, $sendToSoldiers): void
    {
        $this->denyChangeByRole($rejectorRole, $shiftId, $soldierId, $requesterId, $sendToSoldiers);
    }

    protected function denyChangeByRole($rejectorRole, $shiftId, $soldierId, $requesterId, $sendToSoldiers)
    {
        match ($rejectorRole) {
            'shifts-assignment' => $this->shiftAssignmentDenyChange($shiftId, $soldierId, $requesterId, $sendToSoldiers),
            'team-commander', 'department-commander' => $this->commanderDenyChange($shiftId, $soldierId),
            'soldier' => $this->soldierDenyChange($shiftId, $soldierId)
        };
    }

    protected function shiftAssignmentDenyChange($shiftId, $soldierId, $requesterId, $sendToSoldiers)
    {
        $shift = Shift::find($shiftId);
        $soldierA = Soldier::find($shift->soldier_id);
        $soldierB = Soldier::find($soldierId);
        $this->deleteNonRelevantNotifications($shiftId.'-'.$shift->soldier_id.'-'.$soldierId);
        if ($sendToSoldiers) {
            $this->sendNotification(
                __('Deny change shift request'),
                __(
                    'Shifts assignment notification of deny changing shifts for first soldier',
                    [
                        'soldierAName' => $soldierA->user->displayName,
                        'shiftName' => $shift->task()->withTrashed()->first()->name,
                        'shiftStart' => $shift->start_date,
                        'shiftEnd' => $shift->end_date,
                        'soldierBName' => $soldierB->user->displayName,
                        'shiftsAssignmentName' => auth()->user()->displayName,
                    ]
                ),
                [],
                $soldierA->user
            );
            $this->sendNotification(
                __('Deny change shift request'),
                __(
                    'Shifts assignment notification of deny changing shifts for second soldier',
                    [
                        'soldierBName' => $soldierB->user->displayName,
                        'soldierAName' => $soldierA->user->displayName,
                        'shiftName' => $shift->task()->withTrashed()->first()->name,
                        'shiftStart' => $shift->start_date,
                        'shiftEnd' => $shift->end_date,
                        'shiftsAssignmentName' => auth()->user()->displayName,
                    ]
                ),
                [],
                $soldierB->user
            );
        }
        $this->sendNotification(
            __('Deny change shift request'),
            __(
                'Shifts assignment notification of deny changing shifts for commander',
                [
                    'commanderName' => User::find($requesterId)->displayName,
                    'shiftName' => $shift->task()->withTrashed()->first()->name,
                    'soldierAName' => $soldierA->user->displayName,
                    'shiftStart' => $shift->start_date,
                    'shiftEnd' => $shift->end_date,
                    'soldierBName' => $soldierB->user->displayName,
                    'shiftsAssignmentName' => auth()->user()->displayName,
                ]
            ),
            [],
            User::find($requesterId)
        );
        $this->getShiftsAssignments()
            ->filter(fn ($shiftsAssignment) => $shiftsAssignment->id !== auth()->user()->id)
            ->map(
                fn ($shiftsAssignment) => $this->sendNotification(
                    __('Deny change shift request'),
                    __(
                        'Shifts assignment notification of deny changing shifts for shifts assignment',
                        [
                            'shiftsAssignmentName' => $shiftsAssignment->displayName,
                            'shiftName' => $shift->task()->withTrashed()->first()->name,
                            'soldierAName' => $soldierA->user->displayName,
                            'shiftStart' => $shift->start_date,
                            'shiftEnd' => $shift->end_date,
                            'soldierBName' => $soldierB->user->displayName,
                            'shiftsAssignment2Name' => auth()->user()->displayName,
                        ]
                    ),
                    [],
                    $shiftsAssignment
                )
            );
    }

    protected function commanderDenyChange($shiftId, $soldierId)
    {
        $shift = Shift::find($shiftId);
        $soldierA = Soldier::find($shift->soldier_id);
        $soldierB = Soldier::find($soldierId);
        if ($soldierA->team->commander->id !== $soldierB->id) {
            $this->sendNotification(
                __('Deny change shift request'),
                __(
                    'Commander notification of deny changing shift request for the first soldier',
                    [
                        'soldierAName' => $soldierA->user->displayName,
                        'shiftName' => $shift->task()->withTrashed()->first()->name,
                        'shiftStart' => $shift->start_date,
                        'shiftEnd' => $shift->end_date,
                        'soldierBName' => $soldierB->user->displayName,
                        'commanderName' => $soldierA->team->commander->user->displayName,
                    ]
                ),
                [],
                $soldierA->user
            );
            $this->sendNotification(
                __('Deny change shift request'),
                __(
                    'Commander notification of deny changing shift request for the second soldier',
                    [
                        'soldierBName' => $soldierB->user->displayName,
                        'soldierAName' => $soldierA->user->displayName,
                        'shiftName' => $shift->task()->withTrashed()->first()->name,
                        'shiftStart' => $shift->start_date,
                        'shiftEnd' => $shift->end_date,
                        'commanderName' => $soldierA->team->commander->user->displayName,
                    ]
                ),
                [],
                $soldierB->user
            );
        } else {
            $this->sendNotification(
                __('Deny change shift request'),
                __(
                    'Commander notification of deny changing shift request for the first soldier',
                    [
                        'soldierAName' => $soldierA->user->displayName,
                        'shiftName' => $shift->task()->withTrashed()->first()->name,
                        'shiftStart' => $shift->start_date,
                        'shiftEnd' => $shift->end_date,
                        'soldierBName' => $soldierB->user->displayName,
                        'commanderName' => $soldierA->team->commander->user->displayName,
                    ]
                ),
                [],
                $soldierA->user
            );
        }
    }

    protected function soldierDenyChange($shiftId, $soldierId)
    {
        $shift = Shift::find($shiftId);
        $soldierA = Soldier::find($shift->soldier_id);
        $soldierB = Soldier::find($soldierId);
        $this->sendNotification(
            __('Deny change shift request'),
            __(
                'Soldier notification of deny changing shift request',
                [
                    'soldierAName' => $soldierA->user->displayName,
                    'shiftName' => $shift->task()->withTrashed()->first()->name,
                    'shiftStart' => $shift->start_date,
                    'shiftEnd' => $shift->end_date,
                    'soldierBName' => $soldierB->user->displayName,
                ]
            ),
            [],
            $soldierA->user
        );
    }

    protected function deleteNonRelevantNotifications($commonKey)
    {
        \DB::table('notifications')
            ->where('data->commonKey', $commonKey)
            ->delete();
    }

    protected static function getShiftsAssignments()
    {
        return User::whereHas('roles', function ($query) {
            $query->where('name', 'shifts-assignment');
        })->get();
    }

    #[On('confirmConstraint')]
    public function confirmConstraint($user, $constraintName, $startDate, $endDate)
    {
        $this->confirmConstraintNotification($user, $constraintName, $startDate, $endDate);
    }

    protected function confirmConstraintNotification($user, $constraintName, $startDate, $endDate)
    {
        $constraint = new Constraint;
        $constraint->constraint_type = $constraintName;
        $constraint->start_date = $startDate;
        $constraint->end_date = $endDate;
        $constraint->soldier_id = User::find($user)->userable_id;
        $constraint->save();
        $this->sendNotification(
            __('Constraint request approved'),
            __('Commander approved create constraint', [
                'name' => User::find($user)->displayName,
                'constraintName' => __($constraintName),
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]),
            [],
            User::find($user)
        );
    }

    #[On('denyConstraint')]
    public function denyConstraint($user, $constraintName, $startDate, $endDate)
    {
        $this->denyConstraintNotification($user, $constraintName, $startDate, $endDate);
    }

    protected function denyConstraintNotification($user, $constraintName, $startDate, $endDate)
    {
        $this->sendNotification(
            __('Constraint request rejected'),
            __('Commander deny create constraint', [
                'name' => User::find($user)->displayName,
                'constraintName' => $constraintName,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]),
            [],
            User::find($user)
        );
    }

    #[On('confirmConstraintEdit')]
    public function confirmConstraintEdit($user, $data)
    {
        $this->confirmConstraintEditNotification($user, $data);
    }

    protected function confirmConstraintEditNotification($user, $data)
    {
        $columns = Schema::getColumnListing(strtolower(class_basename('constraints')));
        $filteredData = array_intersect_key($data['newConstraint'], array_flip($columns));
        $record = Constraint::find($data['oldConstraint']['id']);

        if ($record) {
            collect($filteredData)->map(function ($value, $key) use ($record) {
                $record->{$key} = $value;
            });
            $record->save();
        }

        $this->sendNotification(
            __('Your request to edit the constraint has been approved'),
            $data['oldConstraint']['constraint_type'] === $data['newConstraint']['constraint_type'] ?
            __('Commander approved edit constraint times', [
                'soldierName' => Soldier::find($data['oldConstraint']['soldier_id'])->user->displayName,
                'constraintName' => __($data['oldConstraint']['constraint_type']),
                'startDate' => $data['oldConstraint']['start_date'],
                'endDate' => $data['oldConstraint']['end_date'],
                'toStartDate' => $data['newConstraint']['start_date'],
                'toEndDate' => $data['newConstraint']['end_date'],
            ]) :
            __('Commander approved edit constraint type', [
                'soldierName' => Soldier::find($data['oldConstraint']['soldier_id'])->user->displayName,
                'constraintName' => __($data['oldConstraint']['constraint_type']),
                'startDate' => $data['oldConstraint']['start_date'],
                'endDate' => $data['oldConstraint']['end_date'],
                'toConstraintName' => __($data['newConstraint']['constraint_type']),
                'toStartDate' => $data['newConstraint']['start_date'],
                'toEndDate' => $data['newConstraint']['end_date'],
            ]),
            [],
            User::find($user)
        );
    }

    #[On('denyConstraintEdit')]
    public function denyConstraintEdit($user, $data)
    {
        $this->denyConstraintEditNotification($user, $data);
    }

    protected function denyConstraintEditNotification($user, $data)
    {
        $this->sendNotification(
            __('Your request to edit the constraint has been rejected'),
            $data['oldConstraint']['constraint_type'] === $data['newConstraint']['constraint_type'] ?
            __('Commander deny edit constraint times', [
                'soldierName' => Soldier::find($data['oldConstraint']['soldier_id'])->user->displayName,
                'constraintName' => __($data['oldConstraint']['constraint_type']),
                'startDate' => $data['oldConstraint']['start_date'],
                'endDate' => $data['oldConstraint']['end_date'],
                'toStartDate' => $data['newConstraint']['start_date'],
                'toEndDate' => $data['newConstraint']['end_date'],
            ]) :
            __('Commander deny edit constraint type', [
                'soldierName' => Soldier::find($data['oldConstraint']['soldier_id'])->user->displayName,
                'constraintName' => __($data['oldConstraint']['constraint_type']),
                'startDate' => $data['oldConstraint']['start_date'],
                'endDate' => $data['oldConstraint']['end_date'],
                'toConstraintName' => __($data['newConstraint']['constraint_type']),
                'toStartDate' => $data['newConstraint']['start_date'],
                'toEndDate' => $data['newConstraint']['end_date'],
            ]),
            [],
            User::find($user)
        );
    }

    protected function sendNotification($title, $body, $actions, $user, $commonKey = null)
    {
        MyNotification::make()
            ->commonKey($commonKey)
            ->title($title)
            ->persistent()
            ->body($body)
            ->actions($actions)
            ->sendToDatabase($user, true);
    }
}
