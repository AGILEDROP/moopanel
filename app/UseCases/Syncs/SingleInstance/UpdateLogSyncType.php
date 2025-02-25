<?php

namespace App\UseCases\Syncs\SingleInstance;

use App\Models\Sync;
use Illuminate\Database\Eloquent\Model;

class UpdateLogSyncType extends BaseSyncType
{
    const TYPE = 'update-log';

    public function getLatest(): ?Model
    {
        // Update log data is fetched from two endpoints (first on plugin/updates where plugins are synced and second
        // on /core_updates where core data is synced). When checking the last sync you should return older of those
        // two sync types (core & plugins), and only if both sync types already exists in DB.
        $coreSyncs = Sync::where([['instance_id', '=', $this->instance->id], ['type', '=', CoreSyncType::TYPE]]);
        $pluginsSyncs = Sync::where([['instance_id', '=', $this->instance->id], ['type', '=', PluginsSyncType::TYPE]]);
        if ($coreSyncs->exists() && $pluginsSyncs->exists()) {
            $lastCoreSync = $coreSyncs->latest('synced_at')->first();
            $lastPluginsSync = $pluginsSyncs->latest('synced_at')->first();
            $lastSync = ($lastCoreSync->synced_at < $lastPluginsSync->synced_at) ? $lastCoreSync : $lastPluginsSync;
        }

        return $lastSync ?? null;
    }

    public function run(bool $silent = false): bool
    {
        $coreSync = $this->moduleApiService->sync($this->instance, CoreSyncType::TYPE, true);
        $pluginSync = $this->moduleApiService->sync($this->instance, PluginsSyncType::TYPE, true);
        $status = $coreSync && $pluginSync;
        if (! $silent) {
            if ($status) {
                $this->getSuccessNotification();
            } else {
                $this->getFailNotification();
            }
        }

        return $status;
    }
}
