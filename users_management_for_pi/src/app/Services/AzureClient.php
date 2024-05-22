<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;

class AzureClient
{
    public function __construct(protected PendingRequest $httpClient, protected string $client_id, protected string $client_secret, protected string $authority)
    {
    }

    public function accessToken()
    {
        return new AzureAccessToken($this->client_id, $this->client_secret, $this->authority);
    }

    public function users()
    {
        return new Users($this->httpClient);
    }
}
