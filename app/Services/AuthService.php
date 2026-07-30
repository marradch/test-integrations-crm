<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AuthService
{
    public const API_URL = 'https://accounts.zoho.eu';

    public function getAuthToken(string $grantToken): void
    {
        if (!env('ZOHO_CLIENT_ID') || !env('ZOHO_CLIENT_SECRET')) {
            throw new \Exception('Zoho developers credentials are absent');
        }

        $response = Http::asForm()->post(self::API_URL . '/oauth/v2/token', [
            'grant_type' => 'authorization_code',
            'client_id' => env('ZOHO_CLIENT_ID'),
            'client_secret' => env('ZOHO_CLIENT_SECRET'),
            'code' => $grantToken,
        ]);

        if ($response->json() && $response->json('access_token')) {
            cache([
                'authData' => array_merge(
                    $response->json(),
                    ['created' => date('Y-m-d H:i:s')]
                )
            ]);
        } else if ($response->json() && $response->json('error')) {
            $message = $response->json('error');
            throw new \Exception("Credentials not created: $message");
        } else {
            throw new \Exception('Credentials not created');
        }
    }

    public function getActualizedAuthDataForUsingInApi()
    {
        if (Cache::missing('authData')) {
            throw new \Exception('Auth data not initiated');
        }

        if ($this->isTokenExpired()) {
            $this->refreshTokenData();
        }

        return cache('authData');
    }

    protected function isTokenExpired(): bool
    {
        $authData = cache('authData');

        $createdDateTime = new \DateTime($authData['created']);
        $expirationDateTime = $createdDateTime->add(new \DateInterval("PT{$authData['expires_in']}S"));

        $currentDateTime = new \DateTime();

        if ($currentDateTime < $expirationDateTime) {
            // valid
            return false;
        } else {
            return true;
        }
    }

    protected function refreshTokenData(): void
    {
        $authData = cache('authData');
        if (empty($authData['refresh_token'])) {
            throw new \Exception('Refresh token missing in cache');
        }
        $refreshToken = $authData['refresh_token'];

        $response = Http::asForm()->post(self::API_URL . '/oauth/v2/token', [
            'grant_type' => 'refresh_token',
            'client_id' => env('ZOHO_CLIENT_ID'),
            'client_secret' => env('ZOHO_CLIENT_SECRET'),
            'refresh_token' => $refreshToken,
        ]);

        if ($response->json() && $response->json('access_token')) {
            cache([
                'authData' => array_merge(
                    $response->json(),
                    [
                        'created' => date('Y-m-d H:i:s'),
                        'refresh_token' => $refreshToken,
                    ]
                )
            ]);
        } else if ($response->json() && $response->json('error')) {
            $message = $response->json('error');
            throw new \Exception("Credentials not created: $message");
        } else {
            throw new \Exception('Credentials not created');
        }
    }
}
