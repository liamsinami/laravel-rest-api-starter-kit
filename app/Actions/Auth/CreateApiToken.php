<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;

final readonly class CreateApiToken
{
    /**
     * Issue a new personal access token for the given user.
     *
     * @param  array<int, string>  $abilities
     */
    public function handle(User $user, string $deviceName, array $abilities = ['*']): string
    {
        return $user->createToken($deviceName, $abilities)->plainTextToken;
    }
}
