<?php

namespace App\Models;

use App\Models\Scopes\InstanceScope;
use App\Observers\UniversityMemberObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

#[Fillable([
    'code',
    'acronym',
    'name',
    'sis_base_url',
    'sis_current_year',
    'sis_student_years',
])]
#[ObservedBy([UniversityMemberObserver::class])]
class UniversityMember extends Model
{
    use HasFactory;

    public function accounts(): MorphToMany
    {
        return $this->morphedByMany(Account::class, 'memberable', 'university_memberables')
            ->withPivot('app_role_assignment_id');
    }

    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'memberable', 'university_memberables')
            ->withPivot('app_role_assignment_id');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(Instance::class, 'university_member_id');
    }

    public function hasAnyInstance(): bool
    {
        return $this->instances()->withoutGlobalScope(InstanceScope::class)->exists();
    }
}
