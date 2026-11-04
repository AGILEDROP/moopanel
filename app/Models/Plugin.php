<?php

namespace App\Models;

use App\Models\Concerns\HasInstancesRelationship;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'name',
    'type',
    'display_name',
    'component',
    'is_standard',
    'settings_section',
    'directory',
])]
#[Appends([
    'lastUpdateTime',
])]
class Plugin extends Model
{
    use HasInstancesRelationship;

    public function instances(): BelongsToMany
    {
        return $this->belongsToMany(Instance::class)->withPivot(['enabled', 'version']);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(Update::class);
    }

    public function newestAvailableUpdate(): ?Model
    {
        return $this->updates()->orderBy('version', 'desc')->first();
    }

    public function updateLog(): HasMany
    {
        return $this->hasMany(UpdateLog::class);
    }

    public function lastUpdate(): HasOne
    {
        return $this->hasOne(UpdateLog::class)->latest('timemodified');
    }

    public function getLastUpdateTimeAttribute(): ?string
    {
        return ($this->lastUpdate()->exists()) ? $this->lastUpdate->timemodified : null;
    }

    public function getNewestAvailableUpdateVersionAttribute()
    {
        return $this->updates()->exists() ? $this->newestAvailableUpdate()->version : null;
    }

    public function getNewestAvailableUpdateReleaseAttribute()
    {
        return $this->updates()->exists() ? $this->newestAvailableUpdate()->release : null;
    }
}
