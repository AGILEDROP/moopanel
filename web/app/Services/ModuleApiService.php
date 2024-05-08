<?php

namespace App\Services;

use App\Models\Instance;
use App\Models\Plugin;
use App\Models\Sync;
use App\Models\Update;
use App\Models\UpdateLog;
use Carbon\Carbon;
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
        // create function for both (with updates and without)
        return Http::withHeader('X-API-KEY', $apiKey)
            ->get($baseUrl.self::PLUGIN_PATH.'/plugins/updates');
    }

    private function instancePluginsCrudAction(mixed $results, Model|Instance $instance): void
    {
        $pluginPivotData = [];
        foreach ($results as $item) {
            $plugin = Plugin::updateOrCreate(
                ['name' => $item['plugin']],
                [
                    'display_name' => $item['display_name'],
                    'type' => $item['plugintype'],
                    'component' => $item['component'],
                    'is_standard' => $item['is_standard'],
                    'settings_section' => $item['settings_section'],
                    'directory' => $item['directory'],
                ]
            );

            if (isset($item['update_available'])) {
                $this->updatesCrudAction($item['update_available'], $instance->id, $plugin->id);
            }

            if (isset($item['update_log'])) {
                $this->updateLogCrudAction($item['update_log'], $instance->id, $plugin->id);
            }

            $pluginPivotData[$plugin->id] = [
                'version' => $item['version'],
                'enabled' => $item['enabled'],
            ];
        }

        $instance->plugins()->sync($pluginPivotData);

        // @todo: find more elegant solution!
        Sync::updateOrCreate([
            'instance_id' => $instance->id,
            'type' => Plugin::class,
        ], ['synced_at' => now()]);
        Sync::updateOrCreate([
            'instance_id' => $instance->id,
            'type' => UpdateLog::class,
            'subtype' => Plugin::class,
        ], ['synced_at' => now()]);
        Sync::updateOrCreate([
            'instance_id' => $instance->id,
            'type' => Update::class,
            'subtype' => Plugin::class,
        ], ['synced_at' => now()]);
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

    public function getCoreUpdates(Model|Instance $instance): PromiseInterface|Response
    {
        return Http::withHeader('X-API-KEY', Crypt::decrypt($instance->api_key))
            ->get($instance->url.self::PLUGIN_PATH.'/moodle_core');
    }

    public function syncInstanceCoreUpdates(Model|Instance $instance, bool $silent = false): void
    {
        DB::beginTransaction();
        try {
            $request = $this->getCoreUpdates($instance);
            if (! $request->ok()) {
                throw new \Exception("Core updates sync for instance {$instance->id} failed! Exit status {$request->status()}");
            }

            if ($request->json('update_available') !== null) {
                $this->updatesCrudAction($request->json('update_available'), $instance->id);
            }

            if ($request->json('update_log') !== null) {
                $this->updateLogCrudAction($request->json('update_log'), $instance->id);
            }

            //@todo: find more elegant solution!
            Sync::updateOrCreate([
                'instance_id' => $instance->id,
                'type' => Update::class,
                'subtype' => Instance::class,
            ], ['synced_at' => now()]);
            Sync::updateOrCreate([
                'instance_id' => $instance->id,
                'type' => UpdateLog::class,
                'subtype' => Instance::class,
            ], ['synced_at' => now()]);

            DB::commit();
            if (! $silent) {
                Notification::make()
                    ->title(__('Core data is synced.'))
                    ->success()
                    ->send();
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception->getMessage());

            if (! $silent) {
                Notification::make()
                    ->title(__('Core sync failed'))
                    ->danger()
                    ->send();
            }
        }
    }

    private function updatesCrudAction(mixed $results, int $instanceId, ?int $pluginId = null): void
    {
        $availableUpdateIds = [];
        foreach ($results as $item) {
            $update = Update::updateOrCreate([
                'instance_id' => $instanceId,
                'plugin_id' => $pluginId,
                'version' => $item['version'],
                'release' => $item['release'],
                'maturity' => $item['maturity'],
            ], [
                'url' => $item['url'],
                'download' => $item['download'],
                'downloadmd5' => $item['downloadmd5'],
            ]);
            $availableUpdateIds[] = $update->id;
        }

        Update::where([
            ['instance_id', '=', $instanceId],
            ['plugin_id', '=', $pluginId],
        ])->whereNotIn('id', $availableUpdateIds)->delete();
    }

    private function updateLogCrudAction(mixed $results, int $instanceId, ?int $pluginId = null): void
    {
        $updatesIds = [];
        foreach ($results as $item) {
            $update = UpdateLog::updateOrCreate([
                'operation_id' => $item['id'],
                'instance_id' => $instanceId,
                'plugin_id' => $pluginId,
            ], [
                'username' => $item['username'],
                'type' => $item['type'],
                'version' => $item['version'],
                'targetversion' => $item['targetversion'],
                'timemodified' => Carbon::createFromFormat('U', $item['timemodified'])->rawFormat('Y-m-d H:i:s'),
                'info' => $item['info'],
                'details' => $item['details'],
                'backtrace' => $item['backtrace'],
            ]
            );
            $updatesIds[] = $update->id;
        }

        UpdateLog::where([
            ['instance_id', '=', $instanceId],
            ['plugin_id', '=', $pluginId],
        ])->whereNotIn('id', $updatesIds)->delete();
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
