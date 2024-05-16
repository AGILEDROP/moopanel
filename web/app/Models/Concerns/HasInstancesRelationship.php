<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasInstancesRelationship
{
    public static function bootHasInstancesRelationship(): void
    {
        static::addGlobalScope('tenant_relationship_scope', function (Builder $builder) {
            if (auth()->check() && filament()->getTenant()) {
                $builder->whereHas('instances', function ($q) {
                    $q->where('instances.id', filament()->getTenant()->id);
                });
            }
        });
    }
}
