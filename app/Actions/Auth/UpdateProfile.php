<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;

final readonly class UpdateProfile
{
    /**
     * Update user profile information.
     *
     * @param  array{name?: string, email?: string, username?: ?string}  $data
     */
    public function handle(User $user, array $data): User
    {
        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $user;
    }
}
