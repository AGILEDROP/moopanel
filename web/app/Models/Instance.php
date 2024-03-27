<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Instance extends Model
{
    use HasFactory;

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

    public function universityMember(): BelongsTo
    {
        return $this->belongsTo(UniversityMember::class, 'university_member_id');
    }

    public function enterpriseApplications(): BelongsToMany
    {
        return $this->belongsToMany(EnterpriseApplication::class, 'enterprise_application_instance');
    }
}
