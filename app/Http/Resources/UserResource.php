<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
final class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: string, name: string, email: string, username: ?string, avatar: ?string, avatar_url: ?string, email_verified_at: ?string, roles?: array<int, string>, permissions?: array<int, string>, created_at: ?string, updated_at: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->getAttribute('username'),
            'avatar' => $this->getAttribute('avatar'),
            'avatar_url' => $this->avatarUrl(),
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'roles' => $this->when(
                $this->relationLoaded('roles'),
                fn (): array => $this->getRoleNames()->values()->toArray()
            ),
            'permissions' => $this->when(
                $this->relationLoaded('permissions') || ($this->relationLoaded('roles') && $this->roles->every(fn ($role): bool => $role->relationLoaded('permissions'))),
                fn (): array => $this->getAllPermissions()->pluck('name')->values()->toArray()
            ),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
