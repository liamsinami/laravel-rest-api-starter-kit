<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;

final readonly class DeleteUser
{
    /**
     * Delete the given user.
     */
    public function handle(User $user): bool
    {
        return (bool) $user->delete();
    }
}
