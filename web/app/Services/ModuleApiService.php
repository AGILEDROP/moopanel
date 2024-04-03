<?php

namespace App\Services;

use App\Models\Instance;
use App\Models\Plugin;
use App\Models\Sync;
use Filament\Notifications\Notification;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
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

    public function getInstanceData(string $baseUrl, string $apiKey): PromiseInterface|Response
    {
        return Http::withHeader('X-API-KEY', $apiKey)
            ->get($baseUrl.self::PLUGIN_PATH);
    }

    public function getPlugins(string $baseUrl, string $apiKey): PromiseInterface|Response
    {
        return Http::withHeader('X-API-KEY', $apiKey)
            ->get($baseUrl.self::PLUGIN_PATH.'/plugins');
    }

    private function instancePluginsCrudAction(mixed $results, Model|Instance $instance): void
    {
        $pluginsIds = [];
        // Create or update plugins.
        foreach ($results as $item) {
            $plugin = Plugin::updateOrCreate(
                ['instance_id' => $instance->id, 'name' => $item['plugin']],
                [
                    'display_name' => $item['display_name'],
                    'type' => $item['plugintype'],
                    'component' => $item['component'],
                    'version' => $item['version'],
                    'enabled' => $item['enabled'],
                    'is_standard' => $item['is_standard'],
                    'available_updates' => $item['available_updates'],
                    'settings_section' => $item['settings_section'],
                    'directory' => $item['directory'],
                ]
            );
            $pluginsIds[] = $plugin->id;
        }
        // Delete plugins.
        Plugin::where('instance_id', $instance->id)->whereNotIn('id', $pluginsIds)->delete();
    }

    public function syncInstancePlugins(Model|Instance $instance, bool $silent = false): void
    {
        DB::beginTransaction();
        try {
            $request = $this->getPlugins($instance->url, Crypt::decrypt($instance->api_key));
            if (! $request->ok()) {
                throw new \Exception("Plugins sync failed due to unsuccessful plugins update (instance id: {$instance->id})!");
            }

            // Create, update or delete plugins.
            $this->instancePluginsCrudAction($request->json('plugins'), $instance);
            // Store sync to instance.
            Sync::updateOrCreate([
                'instance_id' => $instance->id,
                'syncable_type' => Plugin::class,
            ], ['synced_at' => now()]);

            DB::commit();
            if (! $silent) {
                Notification::make()
                    ->title(__('Plugins data is synced.'))
                    ->success()
                    ->send();
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception->getMessage());

            if (! $silent) {
                Notification::make()
                    ->title(__('Plugin sync failed'))
                    ->danger()
                    ->send();
            }
        }
    }

    public function syncAllPlugins(): void
    {
        DB::beginTransaction();
        try {
            $instances = Instance::all();
            foreach ($instances as $instance) {
                $request = $this->getPlugins($instance->url, Crypt::decrypt($instance->api_key));
                if (! $request->ok()) {
                    throw new \Exception("Plugins sync failed due to unsuccessful plugins update (instance id: {$instance->id})!");
                }

                // Create, update or delete plugins.
                $this->instancePluginsCrudAction($request->json('plugins'), $instance);
            }

            // Store sync to instance.
            Sync::updateOrCreate([
                'instance_id' => null,
                'syncable_type' => Plugin::class,
            ], ['synced_at' => now()]);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception->getMessage());
        }
    }

    public function getOnlineUsers(Model|Instance $instance): PromiseInterface|Response
    {
        return Http::withHeader('X-API-KEY', Crypt::decrypt($instance->api_key))
            ->get($instance->url.self::PLUGIN_PATH.'/users/online');
    }

    public function getOnlineUsersCount(Model|Instance $instance): ?int
    {
        $request = $this->getOnlineUsers($instance);
        if (! $request->ok()) {
            Log::error("Unsuccessful active users fetch (instance id: {$instance->id})!");
        }

        return $request->json('number_of_users');
    }
}
