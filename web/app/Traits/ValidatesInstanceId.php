<?php

namespace App\Traits;

use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use Illuminate\Validation\ValidationException;

trait ValidatesInstanceId
{
    /**
     * Validates instance_id in the incoming requests
     */
    private function validateInstanceId(): void
    {
        $instanceId = $this->route('instance_id');

        if ($instanceId === null) {
            throw ValidationException::withMessages([
                'instance_id' => 'Missing instance_id',
            ]);
        }

        if (! Instance::withoutGlobalScope(InstanceScope::class)->where('id', (int) $instanceId)->exists()) {
            throw ValidationException::withMessages([
                'instance_id' => 'Invalid instance_id',
            ]);
        }
    }
}
