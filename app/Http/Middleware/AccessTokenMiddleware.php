<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AccessTokenMiddleware
{
    private const string QUERY_KEY = 'pat';

    /**
     * Convert `?pat=` query parameter to Authorization header if present.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->query(self::QUERY_KEY);

        if (is_string($token) && $token !== '') {
            $request->headers->set('Authorization', "Bearer {$token}");
            $request->query->remove(self::QUERY_KEY);
        }

        return $next($request);
    }
}
