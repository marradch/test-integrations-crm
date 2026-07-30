<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SalesDriveService
{
    protected string $url;
    protected string $apiKey;

    public function __construct()
    {
        $this->url = rtrim(env('SALESDRIVE_URL', ''), '/') . '/handler/';
        $this->apiKey = env('SALESDRIVE_API_KEY', '');
    }

    /**
     * Send minimal payload (name and phone) to SalesDrive
     * @param array $data ['fName' => '', 'phone' => '']
     * @return \Illuminate\Contracts\Http\Client\Response
     */
    public function send(array $data)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-Api-Key' => $this->apiKey,
        ];

        $payload = array_merge([
            'getResultData' => '1',
            'fName' => $data['fName'] ?? '',
            'phone' => $data['phone'] ?? '',
        ], $data['extra'] ?? []);

        return Http::withHeaders($headers)
            ->timeout(15)
            ->post($this->url, $payload);
    }
}
