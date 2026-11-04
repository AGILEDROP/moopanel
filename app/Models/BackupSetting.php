<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'instance_id',
    'auto_backups_enabled',
    'auto_deletion_enabled',
    'backup_interval',
    'backup_deletion_interval',
    'backup_last_run',
    'deletion_last_run',
])]
class BackupSetting extends Model
{
    protected function casts(): array
    {
        return [
            'auto_backups_enabled' => 'boolean',
            'backup_interval' => 'integer',
            'backup_deletion_interval' => 'integer',
            'backup_last_run' => 'datetime',
            'deletion_last_run' => 'datetime',
        ];
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }
}
