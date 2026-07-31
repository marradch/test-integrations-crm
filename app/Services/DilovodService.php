<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Events\IntegrationErrorOccurred;

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
                'phones' => [
                    [
                        'pr'   => $data['phone'] ?? '', // Номер телефону
                        'kind' => 'phone',              // Тип контакту
                    ],
                ],
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
                        "details" => json_encode($detailsData, JSON_UNESCAPED_UNICODE)
                    ],
                ],
            ]);

            Log::info('Dilovod response:', $response->json());

            if (!$response->successful()) {
                Log::error('Fail to create client in dilovod', [
                    'body' => $response->body()
                ]);
                event(new IntegrationErrorOccurred(self::class, 'Fail to create client in dilovod', $data));
            } else {
                $responseData = $response->json();
                if ($responseData['error'] ?? '') {
                    event(new IntegrationErrorOccurred(self::class, $responseData['error'], $data));
                }
            }

        } catch (\Throwable $e) {
            Log::error('Error in call dilovod api: ' . $e->getMessage());
            event(new IntegrationErrorOccurred(self::class, 'Error in call dilovod api:' . $e->getMessage(), $data));
        }
    }
}
