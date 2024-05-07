<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasInstance
{
    public static function bootHasInstance(): void
    {
        static::addGlobalScope('tenant_scope', function (Builder $builder) {
            if (auth()->check() && filament()->getTenant()) {
                $builder->whereBelongsTo(filament()->getTenant());
            }
        });
    }
}
