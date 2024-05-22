<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Token extends Model
{
    use HasFactory;

    public function __construct($token = null)
    {
        $this->access_token = $token->access_token ?? null;
        $this->expiration = $token->expires_in ?? null;
    }

    protected $fillable = [
        'access_token',
        'expiration',
    ];

    public function getToken()
    {
        $access_token = $this->pluck('access_token')->first() ? Crypt::decryptString($this->pluck('access_token')->first()) : '';

        return $access_token;
    }

    public function getAccessTokenExpiration()
    {
        $expiration = $this->pluck('expiration')->first();

        return $expiration;
    }

    public function getAccessTokenCreatedAt()
    {
        $created_at = $this->pluck('created_at')->first();

        return $created_at;
    }

    public function updateToken()
    {
        $this->truncate();
        $this->create([
            'access_token' => Crypt::encryptString($this->access_token),
            'expiration' => $this->expiration,
        ]);
    }
}
