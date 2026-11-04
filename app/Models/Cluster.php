<?php

namespace App\Models;

use App\Models\Concerns\HasImage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'name',
    'img_path',
    'master_id',
])]
class Cluster extends Model
{
    use HasImage;

    public function instances(): HasMany
    {
        return $this->hasMany(Instance::class, 'cluster_id', 'id');
    }

    public function master(): HasOne
    {
        return $this->hasOne(Instance::class, 'id', 'master_id');
    }

    public function availableCoreUpdates()
    {
        return Update::whereIn('instance_id', $this->instances()->pluck('id'))->whereNull('plugin_id')->get();
    }

    public function availablePluginUpdates()
    {
        return Update::whereIn('instance_id', $this->instances()->pluck('id'))->whereNotNull('plugin_id')->get();
    }
}
