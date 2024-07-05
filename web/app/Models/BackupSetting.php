<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'instance_id',
        'auto_backups_enabled',
        'backup_interval',
        'backup_deletion_interval',
        'backup_last_run',
        'deletion_last_run',
    ];

    protected $casts = [
        'auto_backups_enabled' => 'boolean',
        'backup_interval' => 'integer',
        'backup_deletion_interval' => 'integer',
        'backup_last_run' => 'datetime',
        'deletion_last_run' => 'datetime',
    ];

    public function instance()
    {
        return $this->belongsTo(Instance::class);
    }
}
