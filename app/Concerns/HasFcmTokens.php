<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\FcmToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Trait HasFcmTokens
 *
 * @mixin Model
 */
trait HasFcmTokens
{
    /**
     * Relationship to user's registered FCM device tokens.
     *
     * @return HasMany<FcmToken, $this>
     */
    public function fcmTokens(): HasMany
    {
        return $this->hasMany(FcmToken::class);
    }

    /**
     * Specifies the user's FCM token(s) for NotificationChannels\Fcm.
     *
     * @return array<int, string>
     */
    public function routeNotificationForFcm(): array
    {
        /** @var array<int, string> $tokens */
        $tokens = $this->fcmTokens()->pluck('token')->values()->toArray();

        return $tokens;
    }

    /**
     * Register or update an FCM device token for this user.
     */
    public function registerFcmToken(string $token, string $deviceType = 'android', ?string $deviceName = null): FcmToken
    {
        /** @var FcmToken $fcmToken */
        $fcmToken = FcmToken::query()->updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $this->id,
                'device_type' => $deviceType,
                'device_name' => $deviceName,
                'last_used_at' => now(),
            ]
        );

        return $fcmToken;
    }

    /**
     * Revoke / delete a specific FCM device token.
     */
    public function revokeFcmToken(string $token): bool
    {
        return (bool) $this->fcmTokens()->where('token', $token)->delete();
    }

    /**
     * Revoke all FCM device tokens for this user.
     */
    public function revokeAllFcmTokens(): int
    {
        return $this->fcmTokens()->delete();
    }
}
