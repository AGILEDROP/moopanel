<?php

namespace App\Models;

use App\Models\Concerns\HasInstance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActiveMoodleUsersLog extends Model
{
    use HasFactory, HasInstance;

    public $timestamps = false;

    protected $table = 'active_moodle_users_log';

    protected $fillable = [
        'instance_id',
        'active_num',
        'start_date',
        'end_date',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }
}
