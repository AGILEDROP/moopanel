<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'instance_id',
    'course_id',
    'moodle_course_id',
    'manual_trigger_timestamp',
    'user_id',
    'url',
    'status',
    'password',
    'message',
    'filesize',
    'backup_storage_id',
    'password',
    'in_deletion_process',
    'in_restore_process',
    'moodle_job_id',
])]
#[Appends([
    'updated_at_timestamp',
])]
class BackupResult extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = null;
    public const STATUS_SUCCESS = true;
    public const STATUS_FAILED = false;
    public const JOB_KEY_CREATE = 'backup_course';
    public const JOB_KEY_DELETE = 'backup_course_delete';
    public const JOB_KEY_RESTORE = 'backup_course_restore';
    public const CHECK_STATUS_SUCCESS = 1;
    public const CHECK_STATUS_PENDING = 2;
    public const CHECK_STATUS_FAIL = 3;
    public const CHECK_STATUS_NOT_FOUND = 4;

    protected function casts(): array
    {
        return [
            'filesize' => 'integer',
            'in_deletion_process' => 'boolean',
            'in_restore_process' => 'boolean',
            'status' => 'boolean',
        ];
    }

    /**
     * Parse status from int to bool
     */
    public function parseStatus(int $status): ?bool
    {
        return match ($status) {
            self::CHECK_STATUS_SUCCESS => true,
            self::CHECK_STATUS_FAIL,
            self::CHECK_STATUS_NOT_FOUND => false,
            default => null,
        };
    }

    public function getUpdatedAtTimestampAttribute(): float|int|string
    {
        return Carbon::parse($this->attributes['updated_at'])->timestamp;
    }

    protected function statusName(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match ($this->status) {
                    self::STATUS_PENDING => __('Pending'),
                    self::STATUS_SUCCESS => __('Success'),
                    self::STATUS_FAILED => __('Fail'),
                    default => __('Unknown'),
                };
            }
        );
    }

    protected function type(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match ($this->manual_trigger_timestamp) {
                    null => __('Automatic'),
                    default => __('Manual'),
                };
            }
        );
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function backupStorage(): BelongsTo
    {
        return $this->belongsTo(BackupStorage::class);
    }
}
