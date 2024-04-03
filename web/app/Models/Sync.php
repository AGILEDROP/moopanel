<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sync extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'instance_id',
        'syncable_type',
        'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->synced_at = $model->freshTimestamp();
        });

        static::updating(function ($model) {
            $model->synced_at = $model->freshTimestamp();
        });
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }
}
