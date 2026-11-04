<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'type',
    'instance_id',
    'user_id',
    'status',
    'payload',
    'moodle_job_id',
])]
class UpdateRequest extends Model
{
    public const STATUS_PENDING = null;
    public const STATUS_SUCCESS = true;
    public const STATUS_FAIL = false;
    public const TYPE_CORE = 'core';
    public const TYPE_PLUGIN = 'plugin';
    public const TYPE_PLUGIN_ZIP = 'plugin_zip';
    public const CHECK_STATUS_SUCCESS = 1;
    public const CHECK_STATUS_PENDING = 2;
    public const CHECK_STATUS_FAIL = 3;
    public const CHECK_STATUS_NOT_FOUND = 4;

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
     */
    public static function generateName(string $instanceName, string $requestType): string
    {
        return $instanceName.'-'.$requestType.'-'.'update'.'-'.now()->format('d-m-y_H-i');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(UpdateRequestItem::class);
    }
}
