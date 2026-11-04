<?php

namespace App\Models;

use App\Models\Concerns\HasInstance;
use App\Observers\SyncObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(timestamps: false)]
#[Fillable([
    'instance_id',
    'type',
    'subtype',
    'synced_at',
])]
#[ObservedBy([SyncObserver::class])]
class Sync extends Model
{
    use HasInstance;

    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
        ];
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }
}
