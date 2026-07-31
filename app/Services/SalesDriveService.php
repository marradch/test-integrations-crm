<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Events\SalesDriveLeadCreated;
use App\Events\IntegrationErrorOccurred;
use Illuminate\Support\Facades\Log;

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

        $response = Http::withHeaders($headers)
            ->timeout(15)
            ->post($this->url, $payload);

        if ($response->successful()) {
            $responseData = $response->json();
            Log::info('Sales Drive response:', $responseData);

            if (($responseData['success'] ?? false)) {
                event(new SalesDriveLeadCreated($payload));
            } else {
                event(new IntegrationErrorOccurred(self::class, 'SalesDrive return error state', $payload));
                Log::warning('SalesDrive return error state:', $responseData ?? []);
            }
        } else {
            event(new IntegrationErrorOccurred(self::class, 'SalesDrive return error state', $payload));
            Log::error('Error in call SalesDrive API:', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        }

        return $response;
    }
}
