<?php

namespace App\Services;

use App\Models\Instance;
use App\UseCases\Syncs\SyncTypeFactory;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ModuleApiService
{
    const PLUGIN_PATH = '/local/moopanel/server.php';

    public function postApiKeyStatus(string $baseUrl, string $apiKey, ?int $keyExpirationDate): PromiseInterface|Response
    {
        return Http::withHeaders([
            'X-API-KEY' => $apiKey,
        ])->post($baseUrl.self::PLUGIN_PATH.'/api_key_status', wrapData([
            'key_expiration_date' => $keyExpirationDate,
        ]));
    }

    public function triggerZipFileUpdates(string $baseUrl, string $apiKey, ?array $updates): PromiseInterface|Response
    {
        // @todo: path doesn't exist yet! I need post endpoint for this!
        return Http::withHeaders([
            'X-API-KEY' => $apiKey,
        ])->post($baseUrl.self::PLUGIN_PATH.'/zip_update', wrapData([
            'updates' => $updates,
        ]));
    }

    public function triggerPluginsUpdates(string $baseUrl, string $apiKey, ?array $updates): PromiseInterface|Response
    {
        return Http::withHeaders([
            'X-API-KEY' => $apiKey,
        ])->post($baseUrl.self::PLUGIN_PATH.'/plugins/update', wrapData([
            'updates' => $updates,
        ]));
    }

    public function getInstanceData(string $baseUrl, string $apiKey): PromiseInterface|Response
    {
        return Http::withHeader('X-API-KEY', $apiKey)
            ->get($baseUrl.self::PLUGIN_PATH);
    }

    public function getCoreUpdates(Model|Instance $instance): PromiseInterface|Response
    {
        return Http::withHeader('X-API-KEY', Crypt::decrypt($instance->api_key))
            ->get($instance->url.self::PLUGIN_PATH.'/moodle_core');
    }

    public function getPlugins(string $baseUrl, string $apiKey): PromiseInterface|Response
    {
        // create function for both (with updates and without)
        return Http::withHeader('X-API-KEY', $apiKey)
            ->get($baseUrl.self::PLUGIN_PATH.'/plugins/updates');
    }

    public function sync(Model|Instance $instance, string $syncType, bool $silent = false): bool
    {
        $sync = SyncTypeFactory::create($syncType, $instance);

        return $sync->run($silent);
    }

    public function getActiveMoodleUsersCount(Model|Instance $instance, int $timeStart, int $timeEnd): ?int
    {
        $request = Http::withHeader('X-API-KEY', Crypt::decrypt($instance->api_key))
            ->get($instance->url.self::PLUGIN_PATH.'/users/online', [
                'timeStart' => $timeStart,
                'timeEnd' => $timeEnd,
            ]);

        if (! $request->ok()) {
            Log::error("Unsuccessful active users fetch (instance id: {$instance->id})!");
        }

        return $request->json('online');
    }
}
