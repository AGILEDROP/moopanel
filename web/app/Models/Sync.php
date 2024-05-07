<?php

namespace App\Models;

use App\Models\Concerns\HasInstance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sync extends Model
{
    use HasFactory, HasInstance;

    public $timestamps = false;

    protected $fillable = [
        'instance_id',
        'type',
        'subtype',
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
