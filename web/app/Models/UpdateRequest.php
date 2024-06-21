<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'name',
        'type',
        'instance_id',
        'user_id',
        'status',
        'payload',
        'moodle_job_id',
    ];

    protected function statusName(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match ($this->status) {
                    self::STATUS_PENDING => __('Pending'),
                    self::STATUS_SUCCESS => __('Success'),
                    self::STATUS_FAIL => __('Fail'),
                    default => __('Unknown'),
                };
            }
        );
    }
        
    /**
     * Generate a name for the update request.
     *
     * @param  string $instanceName
     * @param  string $requestType
     * @return string
     */
    public static function generateName(string $instanceName, string $requestType): string
    {
        return $instanceName . '-' . $requestType . '-' . "update" . '-' . now()->format('d-m-y_H-i');
    }

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
