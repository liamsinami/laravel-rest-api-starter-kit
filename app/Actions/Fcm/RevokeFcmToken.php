<?php

declare(strict_types=1);

namespace App\Actions\Fcm;

use App\Models\User;

final readonly class RevokeFcmToken
{
    public function handle(User $user, string $token): bool
    {
        return $user->revokeFcmToken($token);
    }
}
