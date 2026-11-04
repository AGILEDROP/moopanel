<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'update_request_id',
    'status',
    'model_id',
    'component',
    'version',
    'release',
    'download',
    'zip_path',
    'zip_name',
    'error',
])]
class UpdateRequestItem extends Model
{
    public const STATUS_PENDING = null;
    public const STATUS_SUCCESS = true;
    public const STATUS_FAIL = false;

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

    public function update_request(): BelongsTo
    {
        return $this->belongsTo(UpdateRequest::class);
    }
}
