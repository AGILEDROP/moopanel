<?php

namespace Tests\Unit\Models;

use App\Models\BackupResult;
use Carbon\Carbon;
use Tests\TestCase;

class BackupResultTest extends TestCase
{
    public function test_parse_status_maps_external_status_values(): void
    {
        $result = new BackupResult();

        $this->assertTrue($result->parseStatus(BackupResult::CHECK_STATUS_SUCCESS));
        $this->assertNull($result->parseStatus(BackupResult::CHECK_STATUS_PENDING));
        $this->assertFalse($result->parseStatus(BackupResult::CHECK_STATUS_FAIL));
        $this->assertFalse($result->parseStatus(BackupResult::CHECK_STATUS_NOT_FOUND));
    }

    public function test_status_name_accessor_returns_readable_value(): void
    {
        $this->assertSame('Pending', (new BackupResult(['status' => null]))->status_name);
        $this->assertSame('Success', (new BackupResult(['status' => true]))->status_name);
        $this->assertSame('Fail', (new BackupResult(['status' => false]))->status_name);
    }

    public function test_type_accessor_distinguishes_automatic_and_manual_backups(): void
    {
        $this->assertSame('Automatic', (new BackupResult(['manual_trigger_timestamp' => null]))->type);
        $this->assertSame('Manual', (new BackupResult(['manual_trigger_timestamp' => 123]))->type);
    }

    public function test_updated_at_timestamp_accessor_returns_unix_timestamp(): void
    {
        $timestamp = Carbon::parse('2026-05-08 15:16:17');
        $result = new BackupResult(['updated_at' => $timestamp->toDateTimeString()]);

        $this->assertSame($timestamp->timestamp, $result->updated_at_timestamp);
    }
}
