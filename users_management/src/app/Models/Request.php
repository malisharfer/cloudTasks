<?php

namespace App\Models;

use App\Enums\Requests\AuthenticationType;
use App\Enums\Requests\ServiceType;
use App\Enums\Requests\Status;
use App\Notifications\Email;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class Request extends Model
{
    use HasFactory, Notifiable;

    public function routeNotificationForMail(Notification $notification): array|string
    {
        $email_suffix = config('mail.email_suffix');
        $email = $this->submit_username.$email_suffix;

        return $email;
    }

    public function fullname(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->last_name.' '.$this->first_name,
        );
    }

    public function updateStatus(Status $status)
    {
        $this->update(['status' => $status, 'update_status_date' => Carbon::now()->format('Y-m-d')]);
        $view = 'mail.request.update-status';
        $title = 'The request was '.$this->status->value;
        $this->notify(new Email($this, $title, $view));
    }

    public function scopeCheckApprovalExpiration($query)
    {
        return $query->where('update_status_date', '<=', now()->subWeek())
            ->whereIn('status', [Status::Approved, Status::Denied])
            ->get();
    }

    public static function requestDeletion()
    {

        static::checkApprovalExpiration()->each->delete();
    }

    protected $casts = [
        'status' => Status::class,
        'service_type' => ServiceType::class,
        'authentication_type' => AuthenticationType::class,
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
    ];

    protected $attributes = [
        'validity' => 365,
        'status' => Status::New,
    ];

    protected $fillable = [
        'identity',
        'first_name',
        'last_name',
        'phone',
        'email',
        'unit',
        'sub',
        'authentication_type',
        'service_type',
        'validity',
        'description',
    ];
}
