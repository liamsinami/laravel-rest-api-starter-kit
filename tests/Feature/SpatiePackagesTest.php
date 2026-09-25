<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

test('roles and permissions seeder creates admin role with all assigned permissions', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    $adminRole = Role::findByName(UserRole::Admin->value, 'web');

    expect($adminRole)->not->toBeNull()
        ->and($adminRole->hasPermissionTo('users.view'))->toBeTrue()
        ->and($adminRole->hasPermissionTo('backups.view'))->toBeTrue()
        ->and($adminRole->hasPermissionTo('activity_logs.view'))->toBeTrue();
});

test('admin user seeder creates default admin user with admin role', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(AdminUserSeeder::class);

    $admin = User::query()->where('email', 'admin@example.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->hasRole(UserRole::Admin->value))->toBeTrue()
        ->and($admin->can('users.view'))->toBeTrue()
        ->and($admin->can('backups.create'))->toBeTrue();
});

test('user changes are automatically logged by spatie activitylog', function (): void {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);

    $user->update([
        'name' => 'Updated Name',
    ]);

    $activity = Activity::query()->where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->attribute_changes['attributes']['name'] ?? null)->toBe('Updated Name')
        ->and($activity->attribute_changes['old']['name'] ?? null)->toBe('Original Name');
});

test('user profile endpoint returns roles and permissions in resource response', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(AdminUserSeeder::class);

    $admin = User::query()->where('email', 'admin@example.com')->first();

    $this->actingAs($admin, 'sanctum')
        ->getJson(route('api.v1.user.show'))
        ->assertApiSuccess('User profile retrieved successfully.')
        ->assertJson([
            'success' => true,
            'data' => [
                'email' => 'admin@example.com',
                'roles' => ['admin'],
            ],
        ])
        ->assertJsonStructure([
            'data' => [
                'roles',
                'permissions',
            ],
        ]);
});
