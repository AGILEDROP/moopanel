<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class InstanceScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereHas('users', function ($q) {
            $q->where('users.id', auth()->id());
        });
    }
}
