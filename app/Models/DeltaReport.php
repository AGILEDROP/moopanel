<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'delta_reports')]
#[Fillable([
    'name',
    'user_id',
    'first_instance_id',
    'second_instance_id',
    'first_instance_config_received',
    'second_instance_config_received',
])]
class DeltaReport extends Model
{
}
