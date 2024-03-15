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
        'name',
        'url',
        'img_path',
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
