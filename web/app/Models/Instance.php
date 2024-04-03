<?php

namespace App\Models;

use App\Traits\HasTags;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Instance extends Model
{
    use HasFactory, HasTags;

    protected $fillable = [
        'university_member_id',
        'site_name',
        'url',
        'logo',
        'theme',
        'version',
        'api_key',
        'key_expiration_date',
        'status',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected $with = ['tags'];

    public function universityMember(): BelongsTo
    {
        return $this->belongsTo(UniversityMember::class, 'university_member_id');
    }

    public function plugins(): HasMany
    {
        return $this->hasMany(Plugin::class);
    }

    public function syncs(): HasMany
    {
        return $this->hasMany(Sync::class);
    }
}
