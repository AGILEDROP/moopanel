<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UpdateRequestItem extends Model
{
    use HasFactory;

    public const STATUS_PENDING = null;
    public const STATUS_SUCCESS = true;
    public const STATUS_FAIL = false;

    protected $fillable = [
        'update_request_id',
        'status',
        'model_id',
        'component',
        'version',
        'release',
        'download',
        'zip_path',
        'error'
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

    public function update_request()
    {
        return $this->belongsTo(UpdateRequest::class);
    }
}
