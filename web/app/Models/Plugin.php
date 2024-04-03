<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plugin extends Model
{
    use HasFactory;

    protected $fillable = [
        'instance_id',
        'type',
        'name',
        'display_name',
        'component',
        'version',
        'enabled',
        'is_standard',
        'available_updates',
        'settings_section',
        'directory',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }
}
