<?php

namespace App\Services;


use App\Services\AzureClient;
use DateTime;
use Illuminate\Http\Client\RequestException;

class UserAdder
{
    protected $azureClient;

    public function __construct(AzureClient $azureClient)
    {
        $this->azureClient = $azureClient;
    }

    public function addUser($user_details)
    {
        $mail_nick_name = $this->getMailNickName($user_details);
        $request = $this->getRequestDetails($mail_nick_name, $user_details->expiration_date);
        try {
            $response = $this->azureClient->users()->addUser($request);
            return (object) ['username' => $response['userPrincipalName'], 'password' => $request['passwordProfile']['password']];
        } catch (RequestException $e) {
            if (str_contains($e->getMessage(), "Another object with the same value for property userPrincipalName")) {
                return (object) ['username' => $request['userPrincipalName'], 'password' => ""];
            } else {
                return (object) ['username' => $request['userPrincipalName'], 'password' => null];
            }
        }
    }

    public function getRequestDetails($mail_nick_name, $expiration_date)
    {
        return [
            'accountEnabled' => true,
            'displayName' => $mail_nick_name,
            'mailNickname' => $mail_nick_name,
            'userPrincipalName' => $mail_nick_name . config('mail.email_suffix'),
            'passwordProfile' => [
                'forceChangePasswordNextSignIn' => true,
                'password' => $this->generateStrongPassword()
            ],
            'department' => config('services.azure.department'),
            'Employeetype' => $expiration_date,
        ];
    }

    public function getMailNickName($details)
    {
        return strtoupper(substr($details->first_name, 0, 1) . substr($details->last_name, 0, 1) . substr($details->identity, -6));
    }

    public  function generateStrongPassword($length = 12)
    {
        $bytes = random_bytes($length);
        $password = substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $length);
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
            return $this->generateStrongPassword($length);
        }
        return $password;
    }
}