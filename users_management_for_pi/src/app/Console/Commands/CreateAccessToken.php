<?php

namespace App\Console\Commands;

use App\Services\AzureClient;
use Illuminate\Console\Command;

class CreateAccessToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-access-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create azure access token';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $azure_client = app(AzureClient::class);
        $token = $azure_client->accessToken()->createToken();
        $token->updateToken();
    }
}
