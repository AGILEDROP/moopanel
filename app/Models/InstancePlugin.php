<?php

namespace App\Models;

use App\Models\Concerns\HasInstance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class InstancePlugin extends Model
{
    use HasFactory, HasInstance;

    protected $table = 'instance_plugin';

    protected $appends = [
        'lastUpdateTime',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class, 'instance_id', 'id');
    }

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class, 'plugin_id', 'id');
    }

    public function updates(): HasManyThrough
    {
        return $this->hasManyThrough(Update::class, Plugin::class, 'id', 'plugin_id', 'plugin_id', 'id');
    }

    public function updateLog(): HasManyThrough
    {
        return $this->hasManyThrough(UpdateLog::class, Plugin::class, 'id', 'plugin_id', 'plugin_id', 'id');
    }

    public function lastUpdate(): HasOneThrough
    {
        $lastUpdateId = ($this->updateLog()->count() > 0) ? $this->updateLog()->latest('timemodified')->first()->id : null;

        return $this->hasOneThrough(UpdateLog::class, Plugin::class, 'id', 'plugin_id', 'plugin_id', 'id')->where('update_logs.id', $lastUpdateId);
    }

    public function getLastUpdateTimeAttribute(): ?string
    {
        return ($this->lastUpdate()->exists()) ? $this->lastUpdate->timemodified : null;
    }
}
