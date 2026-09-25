<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->assignRole(UserRole::Admin->value);
    Sanctum::actingAs($user);
});

test('can retrieve list of all roles', function (): void {
    $this->getJson(route('api.v1.roles.index'))
        ->assertApiSuccess('Roles retrieved successfully.')
        ->assertJsonPath('data.0.name', 'admin')
        ->assertJsonPath('data.0.label', 'Administrator')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'label',
                    'description',
                    'is_immutable',
                    'permissions',
                ],
            ],
        ]);
});

test('can retrieve single role details', function (): void {
    $role = Role::findByName('admin', 'web');

    $this->getJson(route('api.v1.roles.show', $role))
        ->assertApiSuccess('Role retrieved successfully.')
        ->assertJsonPath('data.name', 'admin')
        ->assertJsonPath('data.label', 'Administrator')
        ->assertJsonPath('data.is_immutable', true);
});

test('can create a new role with permissions', function (): void {
    $payload = [
        'name' => 'editor',
        'permissions' => ['users.view', 'users.create'],
    ];

    $response = $this->postJson(route('api.v1.roles.store'), $payload);

    $response->assertApiSuccess('Role created successfully.', 201)
        ->assertJsonPath('data.name', 'editor')
        ->assertJsonPath('data.permissions', ['users.view', 'users.create']);

    $this->assertDatabaseHas('roles', [
        'name' => 'editor',
        'guard_name' => 'web',
    ]);
});

test('cannot create role with existing name', function (): void {
    $this->postJson(route('api.v1.roles.store'), [
        'name' => 'admin',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('can update an existing role name and permissions', function (): void {
    /** @var Role $role */
    $role = Role::query()->create(['name' => 'moderator', 'guard_name' => 'web']);
    $role->syncPermissions(['users.view']);

    $response = $this->putJson(route('api.v1.roles.update', $role), [
        'name' => 'senior_moderator',
        'permissions' => ['users.view', 'users.edit'],
    ]);

    $response->assertApiSuccess('Role updated successfully.')
        ->assertJsonPath('data.name', 'senior_moderator')
        ->assertJsonPath('data.permissions', ['users.view', 'users.edit']);

    $this->assertDatabaseHas('roles', [
        'id' => $role->id,
        'name' => 'senior_moderator',
    ]);
});

test('cannot update an immutable role', function (): void {
    $adminRole = Role::findByName('admin', 'web');

    $response = $this->putJson(route('api.v1.roles.update', $adminRole), [
        'name' => 'super_admin_renamed',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'This role is immutable and cannot be modified.',
        ]);

    $this->assertDatabaseHas('roles', [
        'id' => $adminRole->id,
        'name' => 'admin',
    ]);
});

test('cannot delete an immutable role', function (): void {
    $adminRole = Role::findByName('admin', 'web');

    $response = $this->deleteJson(route('api.v1.roles.destroy', $adminRole));

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'This role is immutable and cannot be deleted.',
        ]);

    $this->assertDatabaseHas('roles', [
        'id' => $adminRole->id,
        'name' => 'admin',
    ]);
});

test('can delete a role', function (): void {
    /** @var Role $role */
    $role = Role::query()->create(['name' => 'temporary_role', 'guard_name' => 'web']);

    $this->deleteJson(route('api.v1.roles.destroy', $role))
        ->assertApiSuccess('Role deleted successfully.');

    $this->assertDatabaseMissing('roles', [
        'id' => $role->id,
    ]);
});

test('can retrieve list of all permissions', function (): void {
    $this->getJson(route('api.v1.permissions.index'))
        ->assertApiSuccess('Permissions retrieved successfully.')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'guard_name',
                ],
            ],
        ]);
});

test('regular users cannot access role and permission management endpoints', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson(route('api.v1.roles.index'))
        ->assertForbidden();

    $this->postJson(route('api.v1.roles.store'), [])
        ->assertForbidden();

    $this->getJson(route('api.v1.permissions.index'))
        ->assertForbidden();
});
