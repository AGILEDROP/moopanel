<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UpdateRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'update_request_id',
        'status',
        'model_id',
        'component',
        'version',
        'release',
        'download',
        'zip_path',
        'error'
    ];

    public function update_request()
    {
        return $this->belongsTo(UpdateRequest::class);
    }
}
