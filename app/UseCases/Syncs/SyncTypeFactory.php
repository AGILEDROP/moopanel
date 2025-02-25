<?php

namespace App\UseCases\Syncs;

use App\Models\Instance;
use App\UseCases\Syncs\SingleInstance\CoreSyncType;
use App\UseCases\Syncs\SingleInstance\CourseSyncType;
use App\UseCases\Syncs\SingleInstance\InfoSyncType;
use App\UseCases\Syncs\SingleInstance\PluginsSyncType;
use App\UseCases\Syncs\SingleInstance\UpdateLogSyncType;
use InvalidArgumentException;

class SyncTypeFactory
{
    public static function create(string $type, Instance $instance): SyncType
    {
        return match ($type) {
            CoreSyncType::TYPE => new CoreSyncType($instance),
            InfoSyncType::TYPE => new InfoSyncType($instance),
            PluginsSyncType::TYPE => new PluginsSyncType($instance),
            CourseSyncType::TYPE => new CourseSyncType($instance),
            UpdateLogSyncType::TYPE => new UpdateLogSyncType($instance),
            default => throw new InvalidArgumentException("Invalid sync type: {$type}"),
        };
    }
}
