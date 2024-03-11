<?php

namespace App\Http\Middleware\SCIM;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountsSecretTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() !== config('custom.scim_secret_token_accounts')) {
            abort(403, __('You are not authorized'));
        }

        return $next($request);
    }
}
