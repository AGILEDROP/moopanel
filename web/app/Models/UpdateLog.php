<?php

namespace App\Models;

use App\Enums\UpdateLogType;
use App\Models\Concerns\HasInstance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpdateLog extends Model
{
    use HasFactory, HasInstance;

    protected $fillable = [
        'operation_id',
        'instance_id',
        'plugin_id',
        'username',
        'type',
        'version',
        'targetversion',
        'info',
        'details',
        'backtrace',
        'timemodified',
    ];

    protected $casts = [
        'operation_id' => 'int',
        'instance_id' => 'int',
        'type' => UpdateLogType::class,
        'timemodified' => 'datetime',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }
}
