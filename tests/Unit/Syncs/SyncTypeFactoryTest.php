<?php

namespace Tests\Unit\Syncs;

use App\Models\Instance;
use App\UseCases\Syncs\SingleInstance\CoreSyncType;
use App\UseCases\Syncs\SingleInstance\CourseSyncType;
use App\UseCases\Syncs\SingleInstance\InfoSyncType;
use App\UseCases\Syncs\SingleInstance\PluginsSyncType;
use App\UseCases\Syncs\SingleInstance\UpdateLogSyncType;
use App\UseCases\Syncs\SyncTypeFactory;
use InvalidArgumentException;
use Tests\TestCase;

class SyncTypeFactoryTest extends TestCase
{
    public function test_factory_creates_supported_sync_types(): void
    {
        $instance = Instance::factory()->create();

        $this->assertInstanceOf(CoreSyncType::class, SyncTypeFactory::create(CoreSyncType::TYPE, $instance));
        $this->assertInstanceOf(InfoSyncType::class, SyncTypeFactory::create(InfoSyncType::TYPE, $instance));
        $this->assertInstanceOf(PluginsSyncType::class, SyncTypeFactory::create(PluginsSyncType::TYPE, $instance));
        $this->assertInstanceOf(CourseSyncType::class, SyncTypeFactory::create(CourseSyncType::TYPE, $instance));
        $this->assertInstanceOf(UpdateLogSyncType::class, SyncTypeFactory::create(UpdateLogSyncType::TYPE, $instance));
    }

    public function test_factory_throws_for_invalid_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SyncTypeFactory::create('unknown', Instance::factory()->create());
    }
}
