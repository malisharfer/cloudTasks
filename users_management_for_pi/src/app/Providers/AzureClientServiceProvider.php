<?php

namespace App\Providers;

use App\Models\Token;
use App\Services\AzureClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AzureClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AzureClient::class, function ($app) {

            $client_id = config('services.azure.client_id');
            $client_secret = config('services.azure.client_secret');
            $tenant_id = config('services.azure.tenant');
            $authority = 'https://login.microsoftonline.com/'.$tenant_id.'/oauth2/token?api-version=1.0';
            $token = new Token();
            $token = $token->getToken();
            $client = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
            ])->baseUrl(config('services.azure.graph_url'));

            return new AzureClient($client, $client_id, $client_secret, $authority);
        });
    }
}
