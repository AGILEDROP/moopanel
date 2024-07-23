<?php

namespace App\Http\Middleware;

use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class CheckRefererToken
{
    /**
     * Validates Request URL and checks if there exists instance with that URL - than checks if API key is correct
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $refererUrl = $request->schemeAndHttpHost();

        if ($refererUrl === null) {
            throw ValidationException::withMessages([
                'referer_url' => 'Missing referer URL',
            ]);
        }

        if (! filter_var($refererUrl, FILTER_VALIDATE_URL)) {
            throw ValidationException::withMessages([
                'referer' => 'Invalid referer URL',
            ]);
        }

        $instance = Instance::withoutGlobalScope(InstanceScope::class)->where('url', $refererUrl)->first();

        if (! $instance) {
            throw ValidationException::withMessages([
                'referer_url' => 'Cant find instance with this referer URL',
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
