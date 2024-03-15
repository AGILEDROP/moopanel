<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class UniversityMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'acronym',
        'name',
        'years_of_enrollment',
    ];

    public function accounts(): MorphToMany
    {
        return $this->morphedByMany(Account::class, 'memberable', 'university_memberables');
    }

    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'memberable', 'university_memberables');
    }
}
