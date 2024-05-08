<?php

namespace App\Enums\Requests;

enum AuthenticationType: string 
{
    case MicrosoftAuth = 'Microsoft auth';
    case PhoneCall = 'phone call';
}