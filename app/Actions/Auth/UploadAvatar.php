<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final readonly class UploadAvatar
{
    public function __construct(private ImageService $imageService) {}

    /**
     * Upload, resize, and update user avatar image.
     */
    public function handle(User $user, UploadedFile $file): User
    {
        return DB::transaction(function () use ($user, $file): User {
            $targetDisk = (string) config('filesystems.default');

            if ($user->avatar && ! str_starts_with($user->avatar, 'http')) {
                $this->imageService->delete($user->avatar, $targetDisk);
            }

            $path = $this->imageService->storeThumbnail(
                file: $file,
                directory: 'avatars',
                size: 500,
                quality: 85,
                disk: $targetDisk,
            );

            $user->update([
                'avatar' => $path,
            ]);

            return $user;
        });
    }
}
