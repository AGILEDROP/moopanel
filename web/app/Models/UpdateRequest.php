<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UpdateRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = null;
    public const STATUS_SUCCESS = true;
    public const STATUS_FAIL = false;

    public const TYPE_CORE = 'core';
    public const TYPE_PLUGIN = 'plugin';
    public const TYPE_PLUGIN_ZIP = 'plugin_zip';

    protected $fillable = [
        'type',
        'instance_id',
        'user_id',
        'status',
        'payload',
        'moodle_job_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function instance()
    {
        return $this->belongsTo(Instance::class);
    }

    public function items()
    {
        return $this->hasMany(UpdateRequestItem::class);
    }
}
