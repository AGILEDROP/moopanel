<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EnterpriseApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function instances(): BelongsToMany
    {
        return $this->belongsToMany(Instance::class, 'enterprise_application_instance');
    }
}
