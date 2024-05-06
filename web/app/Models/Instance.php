<?php

namespace App\Models;

use App\Models\Concerns\HasImage;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Instance extends Model implements HasAvatar
{
    use HasFactory, HasImage;

    protected $fillable = [
        'university_member_id',
        'cluster_id',
        'name',
        'short_name',
        'url',
        'img_path',
        'theme',
        'version',
        'api_key',
        'key_expiration_date',
        'status',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected static function booted(): void
    {
        // Could also create the observer class for this!
        //@todo: ask who should have access to all instances instances (probably best to add access to roles)!
        static::created(function (Instance $instance) {
            $instance->users()->attach(User::pluck('id')->toArray());
        });
    }

    public function universityMember(): BelongsTo
    {
        return $this->belongsTo(UniversityMember::class, 'university_member_id');
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    public function plugins(): BelongsToMany
    {
        return $this->belongsToMany(Plugin::class)->withPivot(['enabled', 'version']);
    }

    public function availableUpdates(): HasMany
    {
        return $this->hasMany(Update::class);
    }

    public function availableCoreUpdate(): HasMany
    {
        return $this->updates()->whereNull('plugin_id')->latest();
    }

    public function availableCoreUpdates(): HasMany
    {
        return $this->hasMany(Update::class)->whereNull('plugin_id');
    }

    public function availablePluginUpdates(): HasMany
    {
        return $this->hasMany(Update::class)->whereNotNull('plugin_id');
    }

    public function updateLog(): HasMany
    {
        return $this->hasMany(UpdateLog::class);
    }

    public function coreUpdateLog(): HasMany
    {
        return $this->updateLog()->whereNull('plugin_id');
    }

    public function pluginUpdateLog(): HasMany
    {
        return $this->hasMany(UpdateLog::class)->whereNotNull('plugin_id');
    }

    public function syncs(): HasMany
    {
        return $this->hasMany(Sync::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function getDefaultImageNameAttribute(): string
    {
        return 'short_name';
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->image;
    }
}
