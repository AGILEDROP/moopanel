<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Plugin extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'display_name',
        'component',
        'is_standard',
        'settings_section',
        'directory',
    ];

    protected $appends = [
        'lastUpdateTime',
    ];

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
