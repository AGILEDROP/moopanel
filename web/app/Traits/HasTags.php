<?php

namespace App\Traits;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasTags
{
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'taggables');
    }

    protected static function bootHasTags(): void
    {
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses(get_class()))) {
            self::forceDeleting(function ($model) {
                $model->tags()->sync([]);
            });
        } else {
            self::deleting(function ($model) {
                $model->tags()->sync([]);
            });
        }
    }
}
