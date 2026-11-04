<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'instance_id',
    'active',
    'name',
    'storage_key',
    'url',
    'key',
    'secret',
    'bucket_name',
    'region',
])]
class BackupStorage extends Model
{
    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }

    public function backupResults()
    {
        return $this->hasMany(BackupResult::class);
    }
}
