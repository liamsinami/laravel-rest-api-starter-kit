<?php

declare(strict_types=1);

namespace App\Actions\Attachment;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class DownloadAttachment
{
    public function handle(Attachment $attachment): BinaryFileResponse|StreamedResponse
    {
        $disk = $attachment->disk ?: (string) config('filesystems.default');

        if (! Storage::disk($disk)->exists($attachment->path)) {
            throw new NotFoundHttpException('Attachment file not found on disk.');
        }

        return Storage::disk($disk)->download(
            path: $attachment->path,
            name: $attachment->name,
            headers: [
                'Content-Type' => $attachment->mime ?: 'application/octet-stream',
            ]
        );
    }
}
