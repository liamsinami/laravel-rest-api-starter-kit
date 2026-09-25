<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

final readonly class RevokeApiToken
{
    /**
     * Revoke personal access token(s) for the user.
     */
    public function handle(User $user, bool $all = false): int
    {
        if ($all) {
            return $user->tokens()->delete();
        }

        $currentToken = $user->currentAccessToken();

        if ($currentToken instanceof PersonalAccessToken) {
            $currentToken->delete();

            return 1;
        }

        return 0;
    }
}
