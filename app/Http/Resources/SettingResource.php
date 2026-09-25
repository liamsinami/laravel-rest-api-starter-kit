<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Setting
 */
final class SettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: string, key: string, value: mixed, group: string, type: string, is_public: bool, description: ?string, updated_at: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'key' => $this->key,
            'value' => $this->getCastedValue(),
            'group' => $this->group,
            'type' => $this->type,
            'is_public' => (bool) $this->is_public,
            'description' => $this->description,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
