<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\ApiErrorCode;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureEmailVerified
{
    /**
     * Ensure the authenticated user has verified their email address.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::unauthorized();
        }

        if (method_exists($user, 'hasVerifiedEmail') && ! $user->hasVerifiedEmail()) {
            return ApiResponse::forbidden(
                message: 'Your email address is not verified. Please verify your email to continue.',
                errorCode: ApiErrorCode::EmailNotVerified,
            );
        }

        return $next($request);
    }
}
