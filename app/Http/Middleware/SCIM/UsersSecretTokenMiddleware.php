<?php

namespace App\Http\Middleware\SCIM;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UsersSecretTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() !== config('custom.scim_secret_token_users')) {
            abort(Response::HTTP_FORBIDDEN, __('You are not authorized'));
        }

        return $next($request);
    }
}
