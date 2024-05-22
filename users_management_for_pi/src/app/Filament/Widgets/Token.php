<?php

namespace App\Filament\Widgets;

use App\Models\Token as AccessToken;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Artisan;

class Token extends Widget
{
    protected static string $view = 'filament.widgets.token';

    protected static ?string $pollingInterval = '1s';

    protected static bool $isLazy = false;

    public $accessToken;

    public $token;

    public function mount()
    {
        $this->loadAccessToken();
    }

    public function loadAccessToken()
    {
        $this->token = new AccessToken();
        $this->accessToken = $this->token->getToken();
    }

    public function formatTimeAgo()
    {
        $creationDateTime = new \DateTime($this->token->getAccessTokenCreatedAt());
        $currentDateTime = new \DateTime();
        $interval = $currentDateTime->diff($creationDateTime);

        return ($interval->i > 0) ? $interval->format('%i minutes') : (($interval->s > 0) ? $interval->format('%s seconds') : '');
    }

    public function AccessExpirationTime()
    {
        $expirationDateTime = new \DateTime($this->formatTimeAgo());
        $currentDateTime = new \DateTime();
        $interval = $currentDateTime->diff($expirationDateTime);
        $remainingMinutes = ($this->token->getAccessTokenExpiration() / 60) - $interval->i;
        $remainingSeconds = $this->token->getAccessTokenExpiration() - ($interval->s + ($interval->i * 60));

        return ($remainingSeconds <= 0) ? 0 : (($remainingSeconds <= 60) ? ($remainingSeconds.' seconds') : ((int) $remainingMinutes).' minutes');
    }

    public function createAccessToken()
    {
        Artisan::call('app:create-access-token');
        $this->loadAccessToken();
    }

    public function revoke()
    {
        AccessToken::truncate();
        $this->loadAccessToken();
    }

    public static function canView(): bool
    {
        $super_admin = config('services.azure.super_admin');

        return auth()->user()->name == $super_admin;
    }
}
