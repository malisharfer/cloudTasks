<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

class ShiftAssignmentNotification
{
    public function sendNotification()
    {
        $users = Shift::whereNotNull('soldier_id')
            ->whereBetween('start_date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->get()
            ->map(fn ($shift) => $shift->soldier_id)
            ->unique()
            ->values()
            ->toArray();

        $users = User::whereIn('userable_id', $users)->get();

        $users->map(function ($user) {
            Notification::make()
                ->title(__('View assigned shifts'))
                ->warning()
                ->body(__('Go to view the schedule of shifts assigned to you for this month.'))
                ->actions([Action::make(__('View your shift schedule'))
                    ->button()
                    ->url(route('filament.app.resources.my-shifts.index')), ])
                ->sendToDatabase($user, true);
        });
    }
}
