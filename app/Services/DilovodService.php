<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DilovodService {
    protected string $apiKey;
    protected string $url;

    public function __construct()
    {
        $this->apiKey = env('DILOVOD_API_KEY', '');
        $this->url = env('DILOVOD_URL', '');
    }

    public function createClient(array $data) {
        try {
            $detailsData = [
                [
                    'tp'  => 'phone',
                    'val' => $leadData['phone'] ?? '',
                ]
            ];
            $response = Http::post($this->url, [
                'version' => '0.25',
                'key'     => $this->apiKey,
                'action'  => 'saveObject',
                'params'  => [
                    'header' => [
                        'id'         => 'catalogs.persons',
                        'name'       => [
                            'ru' => $data['fName'] ?? '',
                            'uk' => $data['fName'] ?? '',
                        ],
                        'personType' => $data['personType'] ?? 1004000000000035,
                        //"details" => json_encode($detailsData, JSON_UNESCAPED_UNICODE)
                    ],
                ],
            ]);

            Log::info('Dilovod response:', $response->json());

            if (!$response->successful()) {
                Log::error('Fail to create client in dilovod', [
                    'body' => $response->body()
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('Error in call dilovod api: ' . $e->getMessage());
        }
    }
}
