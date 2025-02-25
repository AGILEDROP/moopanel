<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BackupResult extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
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

        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'status' => 'boolean',
        'in_deletion_process' => 'boolean',
        'in_restore_process' => 'boolean',
        'filesize' => 'integer',
    ];

    protected $appends = ['updated_at_timestamp'];

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

    /**
     * Parse status from int to bool
     */
    public function parseStatus(int $status): ?bool
    {
        switch ($status) {
            case self::CHECK_STATUS_SUCCESS:
                return true;
            case self::CHECK_STATUS_PENDING:
                return null;
            case self::CHECK_STATUS_FAIL:
                return false;
            case self::CHECK_STATUS_NOT_FOUND:
                return false;
            default:
                return null;
        }
    }

    public function getUpdatedAtTimestampAttribute()
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

    public function instance()
    {
        return $this->belongsTo(Instance::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function backupStorage(): BelongsTo
    {
        return $this->belongsTo(BackupStorage::class);
    }
}
