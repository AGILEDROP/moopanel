<?php

namespace App\Models;

use App\Observers\AccountObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

#[Fillable([
    'azure_id',
    'name',
    'username',
    'email',
    'type',
])]
#[Hidden([
    'azure_id',
])]
#[ObservedBy([AccountObserver::class])]
class Account extends Model
{
    public function universityMembers(): MorphToMany
    {
        return $this->morphToMany(UniversityMember::class, 'memberable', 'university_memberables');
    }
}
