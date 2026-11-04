<?php

namespace App\Models;

use App\Enums\UpdateLogType;
use App\Models\Concerns\HasInstance;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
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
])]
class UpdateLog extends Model
{
    use  HasInstance;

    protected function casts(): array
    {
        return [
            'instance_id' => 'int',
            'operation_id' => 'int',
            'timemodified' => 'datetime',
            'type' => UpdateLogType::class,
        ];
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }
}
