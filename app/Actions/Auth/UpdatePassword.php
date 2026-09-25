<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

final readonly class UpdatePassword
{
    /**
     * Update user password.
     */
    public function handle(User $user, string $newPassword): User
    {
        $user->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();

        return $user;
    }
}
