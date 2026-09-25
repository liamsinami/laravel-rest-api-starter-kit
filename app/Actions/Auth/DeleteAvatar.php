<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final readonly class DeleteAvatar
{
    /**
     * Delete user avatar and remove from storage.
     */
    public function handle(User $user): User
    {
        return DB::transaction(function () use ($user): User {
            $disk = (string) config('filesystems.default');

            if ($user->avatar && ! str_starts_with($user->avatar, 'http') && Storage::disk($disk)->exists($user->avatar)) {
                Storage::disk($disk)->delete($user->avatar);
            }

            $user->update([
                'avatar' => null,
            ]);

            return $user;
        });
    }
}
