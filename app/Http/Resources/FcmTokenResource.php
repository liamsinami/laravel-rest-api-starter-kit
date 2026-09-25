<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\FcmToken;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FcmToken
 */
final class FcmTokenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: int, device_type: string, device_name: ?string, last_used_at: ?string, created_at: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'device_type' => $this->device_type,
            'device_name' => $this->device_name,
            'last_used_at' => $this->last_used_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
