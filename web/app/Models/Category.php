<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'instance_id',
        'moodle_category_id',
        'moodle_category_parent_id',
        'name',
        'depth',
        'path',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function instance()
    {
        return $this->belongsTo(Instance::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
