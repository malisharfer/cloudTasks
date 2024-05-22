<?php

namespace App\Services;

use App\Models\Token;
use Illuminate\Support\Facades\Http;

class AzureAccessToken
{
    public function __construct(protected string $client_id, protected string $client_secret, protected string $authority)
    {
    }

    public function createToken()
    {
        $response = Http::asForm()->post($this->authority, [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'scope' => 'offline_access',
            'resource' => 'https://graph.microsoft.com/',
            'grant_type' => 'client_credentials',
        ])->json();

        $response = json_decode(json_encode($response));

        return new Token($response);
    }
}
