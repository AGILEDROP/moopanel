<?php

namespace App\Models;

use App\Enums\UpdateMaturity;
use App\Enums\UpdateType;
use App\Models\Concerns\HasInstance;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Update extends Model
{
    use HasFactory, HasInstance;

    protected $fillable = [
        'instance_id',
        'plugin_id',
        'version',
        'release',
        'maturity',
        'url',
        'type',
        'download',
        'downloadmd5',
    ];

    protected $casts = [
        'instance_id' => 'int',
        'maturity' => UpdateMaturity::class,
        'type' => UpdateType::class,
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }

    public function versionDate(): Attribute
    {
        return Attribute::make(
            get: function () {
                $version = substr((string) $this->version, 0, 8);

                $versionYear = substr($version, 0, -4);
                $versionMonth = substr($version, 4, -2);
                $versionDay = substr($version, 6);

                return Carbon::createFromDate($versionYear, $versionMonth, $versionDay)->toDateString();
            }
        );
    }
}
