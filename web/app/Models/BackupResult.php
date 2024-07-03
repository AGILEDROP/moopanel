<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupResult extends Model
{
    use HasFactory;

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
    ];

    public const STATUS_PENDING = null;

    public const STATUS_SUCCESS = true;

    public const STATUS_FAILED = false;

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
}
