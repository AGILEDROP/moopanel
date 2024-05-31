<?php

namespace App\Helpers;

use App\Enums\Status;
use App\Models\Instance;
use App\Models\Plugin;
use App\Models\Update;
use App\Models\UpdateLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SyncHelper
{
    public function updatesCrudAction(mixed $results, int $instanceId, ?int $pluginId = null): void
    {
        $availableUpdateIds = [];
        foreach ($results as $item) {
            $update = Update::withoutGlobalScopes()->updateOrCreate([
                'instance_id' => $instanceId,
                'plugin_id' => $pluginId,
                'version' => $item['version'],
                'release' => $item['release'],
                'maturity' => $item['maturity'],
            ], [
                'type' => $item['type'],
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

    public function updateLogCrudAction(mixed $results, int $instanceId, ?int $pluginId = null): void
    {
        $updatesIds = [];
        foreach ($results as $item) {
            $update = UpdateLog::withoutGlobalScopes()->updateOrCreate([
                'operation_id' => $item['id'],
                'instance_id' => $instanceId,
                'plugin_id' => $pluginId,
            ], [
                'username' => $item['username'],
                'type' => $item['type'],
                'version' => $item['version'],
                'targetversion' => $item['targetversion'],
                'timemodified' => Carbon::createFromTimestamp($item['timemodified'])->rawFormat('Y-m-d H:i:s'),
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

    public function instancePluginsCrudAction(mixed $results, Model|Instance $instance): void
    {
        $pluginPivotData = [];
        foreach ($results as $item) {
            $plugin = Plugin::withoutGlobalScopes()->updateOrCreate(
                [
                    'component' => $item['component'],
                ],
                [
                    'name' => $item['plugin'],
                    'display_name' => $item['display_name'],
                    'type' => $item['plugintype'],
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
    }

    public function setInstanceStatusToDisconnected(Instance $instance): void
    {
        if ($instance->status !== Status::Disconnected) {
            $instance->update(['status' => Status::Disconnected]);
        }
    }
}
