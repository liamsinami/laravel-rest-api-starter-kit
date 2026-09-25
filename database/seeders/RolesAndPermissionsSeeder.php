<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // Create Permissions
        foreach (UserRole::allPermissions() as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Create Roles and assign permissions
        foreach (UserRole::cases() as $userRole) {
            $role = Role::query()->firstOrCreate([
                'name' => $userRole->value,
                'guard_name' => 'web',
            ]);

            $permissions = Permission::query()->whereIn('name', $userRole->permissions())
                ->where('guard_name', 'web')
                ->get();

            $role->syncPermissions($permissions);
        }
    }
}
