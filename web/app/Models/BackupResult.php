<?php

namespace App\Models;

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
