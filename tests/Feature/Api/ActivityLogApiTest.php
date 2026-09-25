<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->create(['name' => 'Logger User']);
    $this->user->assignRole(UserRole::Admin->value);
    Sanctum::actingAs($this->user);
});

test('can list paginated activity logs', function (): void {
    activity('auth')
        ->causedBy($this->user)
        ->performedOn($this->user)
        ->log('User performed an action');

    $response = $this->getJson(route('api.v1.activity-logs.index'))
        ->assertApiSuccess('Activity logs retrieved successfully.')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'log_name',
                    'description',
                    'causer',
                ],
            ],
            'meta' => [
                'current_page',
                'total',
            ],
        ]);

    expect($response->json('meta.total'))->toBeGreaterThanOrEqual(1);
});

test('can filter activity logs by log_name', function (): void {
    activity('system_alert')
        ->causedBy($this->user)
        ->log('Alert triggered');

    $response = $this->getJson(route('api.v1.activity-logs.index', ['log_name' => 'system_alert']))
        ->assertApiSuccess('Activity logs retrieved successfully.');

    expect($response->json('data.0.log_name'))->toBe('system_alert');
});

test('can retrieve single activity log detail', function (): void {
    $activity = activity('custom')
        ->causedBy($this->user)
        ->performedOn($this->user)
        ->log('Custom event happened');

    $this->getJson(route('api.v1.activity-logs.show', $activity))
        ->assertApiSuccess('Activity log retrieved successfully.')
        ->assertJsonPath('data.id', $activity->id)
        ->assertJsonPath('data.log_name', 'custom')
        ->assertJsonPath('data.description', 'Custom event happened')
        ->assertJsonPath('data.causer.id', $this->user->id);
});

test('regular users cannot access activity logs', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson(route('api.v1.activity-logs.index'))
        ->assertForbidden();
});
