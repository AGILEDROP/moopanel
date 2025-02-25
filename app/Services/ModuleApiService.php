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

    public function triggerPluginZipFileUpdates(string $baseUrl, string $apiKey, ?array $payload): PromiseInterface|Response
    {
        return Http::withHeaders([
            'X-API-KEY' => $apiKey,
        ])->post($baseUrl.self::PLUGIN_PATH.'/plugins/installzip', wrapData($payload));
    }

    public function triggerPluginsUpdates(string $baseUrl, string $apiKey, ?array $payload): PromiseInterface|Response
    {
        return Http::withHeaders([
            'X-API-KEY' => $apiKey,
        ])->post($baseUrl.self::PLUGIN_PATH.'/plugins/updates', wrapData($payload));
    }

    public function triggerCourseBackup(string $baseUrl, string $apiKey, ?array $payload): PromiseInterface|Response
    {
        /* Http::fake([
            'github.com/*' => Http::response([
                'backups' => [
                    [
                        'id' => 56,
                        'message' => 'true',
                    ],
                ],
                'errors' => [],
            ], 200),
        ]);

        // Then, make an actual request, which will be intercepted by the fake.
        // For demonstration, let's assume you're making a GET request to "https://github.com/api/data"
        $response = Http::get('https://github.com/api/data');

        return $response; */
        return Http::withHeaders([
            'X-API-KEY' => $apiKey,
        ])->post($baseUrl.self::PLUGIN_PATH.'/backups', wrapData($payload));
    }

    public function triggerCourseBackupRestore(string $baseUrl, string $apiKey, ?array $payload): PromiseInterface|Response
    {
        /* Http::fake([
            'github.com/*' => Http::response([
                'status' => true,
                'message' => 'Backup restore request successfuly accepted!',
            ], 200),
        ]);

        // Then, make an actual request, which will be intercepted by the fake.
        // For demonstration, let's assume you're making a GET request to "https://github.com/api/data"
        $response = Http::get('https://github.com/api/data');

        return $response; */
        return Http::withHeaders([
            'X-API-KEY' => $apiKey,
        ])->put($baseUrl.self::PLUGIN_PATH.'/backups', wrapData($payload));
    }

    public function triggerCourseBackupDeletion(string $baseUrl, string $apiKey, ?array $payload): PromiseInterface|Response
    {
        return Http::withHeaders([
            'X-API-KEY' => $apiKey,
        ])->delete($baseUrl.self::PLUGIN_PATH.'/backups', wrapData($payload));
    }

    public function triggerTaskCheck(string $baseUrl, string $apiKey, ?array $payload): PromiseInterface|Response
    {
        return Http::withHeaders([
            'X-API-KEY' => $apiKey,
        ])->get($baseUrl.self::PLUGIN_PATH.'/tasks/check', $payload);
    }

    /**
     * Get instance courses and course categories
     *
     * @param  mixed  $baseUrl
     * @param  mixed  $apiKey
     */
    public function getCourses(string $baseUrl, string $apiKey): PromiseInterface|Response
    {
        return Http::withHeaders([
            'X-API-KEY' => $apiKey,
        ])->get($baseUrl.self::PLUGIN_PATH.'/courses?displaycategories&displaycourses');
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

    public function triggerCoreUpdate(Model|Instance $instance, ?array $payload): PromiseInterface|Response
    {
        /* Http::fake([
            'github.com/*' => Http::response([
                'status' => true,
                'message' => 'Core update in progress',
            ], 200),
        ]);

        // Then, make an actual request, which will be intercepted by the fake.
        // For demonstration, let's assume you're making a GET request to "https://github.com/api/data"
        $response = Http::get('https://github.com/api/data');

        return $response; */
        return Http::withHeader('X-API-KEY', Crypt::decrypt($instance->api_key))
            ->post($instance->url.self::PLUGIN_PATH.'/moodle_core', wrapData($payload));
    }

    public function getPlugins(string $baseUrl, string $apiKey): PromiseInterface|Response
    {
        // create function for both (with updates and without)
        return Http::withHeader('X-API-KEY', $apiKey)
            ->get($baseUrl.self::PLUGIN_PATH.'/plugins?displayupdates&displayupdateslog');
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
