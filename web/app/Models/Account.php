<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'azure_id',
        'name',
        'username',
        'email',
    ];

    protected $hidden = [
        'azure_id',
    ];

    public function universityMembers(): MorphToMany
    {
        return $this->morphToMany(UniversityMember::class, 'memberable', 'university_memberables');
    }
}
