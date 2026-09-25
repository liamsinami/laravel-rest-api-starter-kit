<?php

declare(strict_types=1);

namespace App\Actions\Fcm;

use App\Models\FcmToken;
use App\Models\User;

final readonly class RegisterFcmToken
{
    public function handle(User $user, string $token, string $deviceType = 'android', ?string $deviceName = null): FcmToken
    {
        return $user->registerFcmToken($token, $deviceType, $deviceName);
    }
}
