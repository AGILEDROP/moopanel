<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Concerns\HasImage;
use App\Models\Scopes\InstanceScope;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ScopedBy([InstanceScope::class])]
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
        'configuration_path',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected $casts = [
        'status' => Status::class,
    ];

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

    public function updates(): HasMany
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

    public function activeMoodleUsersLog(): HasMany
    {
        return $this->hasMany(ActiveMoodleUsersLog::class);
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

    public function update_requests(): HasMany
    {
        return $this->hasMany(UpdateRequest::class);
    }

    public function backup_results(): HasMany
    {
        return $this->hasMany(BackupResult::class);
    }

    public function backup_settings(): HasOne
    {
        return $this->hasOne(BackupSetting::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function backupStorages(): HasMany
    {
        return $this->hasMany(BackupStorage::class);
    }

    /**
     * Check if there is a pending update request for the instance.
     */
    public function hasPendingUpdateRequest(?string $type = null): bool
    {
        if (! is_null($type)) {
            return UpdateRequest::where('instance_id', $this->id)
                ->where('status', UpdateRequest::STATUS_PENDING)
                ->where('type', $type)
                ->exists();
        }

        return UpdateRequest::where('instance_id', $this->id)
            ->where('status', UpdateRequest::STATUS_PENDING)
            ->exists();
    }
}
