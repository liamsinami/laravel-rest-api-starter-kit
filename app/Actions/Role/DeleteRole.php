<?php

declare(strict_types=1);

namespace App\Actions\Role;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final readonly class DeleteRole
{
    /**
     * Delete a role.
     */
    public function handle(Role $role): bool
    {
        return DB::transaction(function () use ($role): bool {
            $deleted = (bool) $role->delete();

            app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

            return $deleted;
        });
    }
}
