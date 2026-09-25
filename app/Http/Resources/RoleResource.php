<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role;

/**
 * @mixin Role
 */
final class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: int, name: string, label: string, description: string, is_immutable: bool, permissions: array<int, string>, created_at: ?string}
     */
    public function toArray(Request $request): array
    {
        $roleEnum = UserRole::tryFrom($this->name);

        /** @var array<int, string> $permissionNames */
        $permissionNames = $this->permissions->pluck('name')->map(fn (mixed $item): string => (string) $item)->values()->all();

        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'label' => $roleEnum?->label() ?? ucfirst(str_replace('_', ' ', $this->name)),
            'description' => $roleEnum?->description() ?? '',
            'is_immutable' => UserRole::isImmutable($this->name),
            'permissions' => $permissionNames,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
