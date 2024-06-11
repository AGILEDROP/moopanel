<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeltaReport extends Model
{
    use HasFactory;

    protected $table = 'delta_reports';

    protected $fillable = [
        'name', 
        'user_id',
        'first_instance_id',
        'second_instance_id',
        'first_instance_config_received',
        'second_instance_config_received',
    ];
}
