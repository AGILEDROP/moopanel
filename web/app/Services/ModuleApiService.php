<?php

namespace App\Services;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ModuleApiService
{
    public function postApiKeyStatus(string $baseUrl, string $apiKey, ?int $keyExpirationDate): PromiseInterface|Response
    {
        return Http::withHeaders([
            'X-API-KEY' => $apiKey,
        ])->post($baseUrl.'/api_key_status', wrapData([
            'key_expiration_date' => $keyExpirationDate,
        ]));
    }

    public function getInstanceData(string $baseUrl, string $apiKey): PromiseInterface|Response
    {
        return Http::withHeader('X-API-KEY', $apiKey)
            ->get($baseUrl);
    }
}
