<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Notifiable;
use App\Notifications\Email;
use App\Enums\Requests\AuthenticationType;
use App\Enums\Requests\Status;
use App\Enums\Requests\ServiceType;

class Request extends Model
{
    use HasFactory, Notifiable;

    public function routeNotificationForMail(Notification $notification): array|string
    {
        $email_suffix = config('mail.email_suffix');
        $email = $this->submit_username . $email_suffix;
        return $email;
    }

    public function fullname(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->last_name . ' ' . $this->first_name,
        );
    }

    public function updateStatus(Status $status)
    {
        $this->update(['status' => $status]);
        $this->sendEmail();
    }

    public function sendEmail()
    {
        $view = 'mail.request.updateStatus';
        $title = 'The request was ' . $this->status->value;
        $this->notify(new Email($this, $title, $view));
    }    

    protected $casts = [
        'status' => Status::class,
        'service_type' => ServiceType::class,
        'authentication_type' => AuthenticationType::class,
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
        'status',
        'description',
    ];
}