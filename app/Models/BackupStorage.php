<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupStorage extends Model
{
    use HasFactory;

    protected $fillable = [
        'instance_id',
        'active',
        'name',
        'storage_key',
        'url',
        'key',
        'secret',
        'bucket_name',
        'region',
    ];

    public function instance()
    {
        return $this->belongsTo(Instance::class);
    }

    public function backupResults()
    {
        return $this->hasMany(BackupResult::class);
    }
}
