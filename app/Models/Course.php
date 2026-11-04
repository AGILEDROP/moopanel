<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'moodle_course_id',
    'instance_id',
    'category_id',
    'name',
    'is_scheduled',
])]
class Course extends Model
{
    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
