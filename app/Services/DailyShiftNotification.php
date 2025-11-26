<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\Soldier;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;

class DailyShiftNotification
{
    public function beforeShift()
    {
        Shift::whereNotNull('soldier_id')
            ->whereDate('start_date', Carbon::today())
            ->get()
            ->map(function (Shift $shift) {
                $user = User::where('userable_id', $shift->soldier_id)->get();
                Notification::make()
                    ->title(__('Your shifts for today').':')
                    ->warning()
                    ->body(
                        __('Assigned to shift today', [
                            'today' => Carbon::parse($shift->start_date)->format('d/m/y'),
                            'user' => Soldier::find($shift->soldier_id)->user->displayName,
                            'task' => $shift->task()->withTrashed()->first()->name,
                            'startShift' => Carbon::parse($shift->start_date)->format('H:i'),
                        ])
                    )
                    ->sendToDatabase($user, true);
            });
    }
}
