<?php

declare(strict_types=1);

namespace App\Actions\Attachment;

use App\Models\Attachment;
use App\Services\ImageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final readonly class CreateAttachment
{
    public function __construct(private ImageService $imageService) {}

    public function handle(
        UploadedFile $file,
        ?string $name = null,
        ?string $description = null,
        bool $isPrivate = false,
        ?string $disk = null,
        ?string $userId = null,
        ?Model $attachable = null,
    ): Attachment {
        $targetDisk = $disk ?: (string) config('filesystems.default');
        $directory = (string) config('attachment.directory', 'attachments');
        $dateFolder = $directory.'/'.now()->format('Y/m');

        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin';
        $mimeType = $file->getClientMimeType() ?: 'application/octet-stream';

        $autoResize = (bool) config('attachment.image.auto_resize', true);
        $maxWidth = (int) config('attachment.image.max_width', 1920);
        $maxHeight = (int) config('attachment.image.max_height', 1920);
        $quality = (int) config('attachment.image.quality', 80);

        if ($autoResize && $this->imageService->isResizable($file)) {
            $relativePath = $this->imageService->store(
                file: $file,
                directory: $dateFolder,
                maxWidth: $maxWidth,
                maxHeight: $maxHeight,
                quality: $quality,
                disk: $targetDisk,
            );
        } else {
            $randomName = Str::uuid()->toString().'.'.$extension;
            $relativePath = $dateFolder.'/'.$randomName;

            Storage::disk($targetDisk)->putFileAs(
                $dateFolder,
                $file,
                $randomName
            );
        }

        $fileSize = (int) (Storage::disk($targetDisk)->size($relativePath) ?: $file->getSize() ?: 0);

        return DB::transaction(function () use (
            $userId,
            $name,
            $originalName,
            $description,
            $relativePath,
            $mimeType,
            $fileSize,
            $targetDisk,
            $isPrivate,
            $attachable
        ): Attachment {
            /** @var Attachment $attachment */
            $attachment = Attachment::query()->create([
                'user_id' => $userId,
                'name' => $name ?: $originalName,
                'description' => $description,
                'path' => $relativePath,
                'disk' => $targetDisk,
                'mime' => $mimeType,
                'size' => $fileSize,
                'is_private' => $isPrivate,
            ]);

            if ($attachable !== null) {
                if (method_exists($attachable, 'attachAttachment')) {
                    $attachable->attachAttachment($attachment);
                } else {
                    $attachable->morphToMany(Attachment::class, 'attachable', 'attachables')->syncWithoutDetaching([$attachment->id]);
                }
            }

            return $attachment;
        });
    }
}
