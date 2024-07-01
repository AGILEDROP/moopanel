<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'moodle_course_id',
        'instance_id',
        'category_id',
        'name',
    ];

    public function instance()
    {
        return $this->belongsTo(Instance::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
