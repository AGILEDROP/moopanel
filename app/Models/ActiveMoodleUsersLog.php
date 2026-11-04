<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'active_moodle_users_log', timestamps: false)]
#[Fillable([
    'instance_id',
    'active_num',
    'start_date',
    'end_date',
])]
class ActiveMoodleUsersLog extends Model
{
    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }
}
