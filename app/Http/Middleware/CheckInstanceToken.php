<?php

namespace App\Http\Middleware;

use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class CheckInstanceToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $instanceId = $request->route('instance_id');

        if ($instanceId === null) {
            throw ValidationException::withMessages([
                'instance_id' => 'Missing instance_id',
            ]);
        }

        $instance = Instance::withoutGlobalScope(InstanceScope::class)->where('id', (int) $instanceId)->first();

        if (! $instance) {
            throw ValidationException::withMessages([
                'instance_id' => 'Invalid instance_id',
            ]);
        }

        $apiKey = Crypt::decrypt($instance->api_key);

        if ($request->header('X-API-KEY') !== $apiKey) {
            return response()->json(
                [
                    'message' => 'You are not authorized',
                ],
                403
            );

            abort(403, __('You are not authorized'));
        }

        return $next($request);
    }
}
