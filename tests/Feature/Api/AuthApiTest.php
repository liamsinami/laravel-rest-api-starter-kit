<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

test('guest can register a new account via api with optional username', function (): void {
    $response = $this->postJson(route('api.v1.auth.register'), [
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'username' => 'johndoe',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
    ]);

    $response->assertApiSuccess('User registered successfully.', 201)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'token_type',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'username',
                ],
            ],
        ])
        ->assertJsonPath('data.user.username', 'johndoe');

    $this->assertDatabaseHas('users', [
        'email' => 'john.doe@example.com',
        'username' => 'johndoe',
    ]);
});

test('user can authenticate via login endpoint using email', function (): void {
    $user = User::factory()->create([
        'email' => 'login.test@example.com',
        'username' => 'logintest',
        'password' => Hash::make('secret1234'),
    ]);

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => 'login.test@example.com',
        'password' => 'secret1234',
        'device_name' => 'iphone',
    ]);

    $response->assertApiSuccess('Access token issued successfully.', 201)
        ->assertJsonPath('data.user.email', 'login.test@example.com');
});

test('user can authenticate via login endpoint using username', function (): void {
    $user = User::factory()->create([
        'email' => 'login2.test@example.com',
        'username' => 'johndoe99',
        'password' => Hash::make('secret1234'),
    ]);

    $response = $this->postJson(route('api.v1.auth.login'), [
        'username' => 'johndoe99',
        'password' => 'secret1234',
    ]);

    $response->assertApiSuccess('Access token issued successfully.', 201)
        ->assertJsonPath('data.user.username', 'johndoe99')
        ->assertJsonPath('data.user.email', 'login2.test@example.com');
});

test('user can authenticate via login endpoint using login field', function (): void {
    $user = User::factory()->create([
        'email' => 'login3.test@example.com',
        'username' => 'superadmin',
        'password' => Hash::make('secret1234'),
    ]);

    $this->postJson(route('api.v1.auth.login'), [
        'login' => 'superadmin',
        'password' => 'secret1234',
    ])->assertApiSuccess('Access token issued successfully.', 201);

    $this->postJson(route('api.v1.auth.login'), [
        'login' => 'login3.test@example.com',
        'password' => 'secret1234',
    ])->assertApiSuccess('Access token issued successfully.', 201);
});

test('authenticated user can view their profile via me endpoint', function (): void {
    $user = User::factory()->create([
        'name' => 'Profile User',
        'email' => 'profile@example.com',
        'username' => 'profileuser',
    ]);

    Sanctum::actingAs($user);

    $this->getJson(route('api.v1.auth.me'))
        ->assertApiSuccess('User profile retrieved successfully.')
        ->assertJsonPath('data.name', 'Profile User')
        ->assertJsonPath('data.email', 'profile@example.com')
        ->assertJsonPath('data.username', 'profileuser');
});

test('authenticated user can update their profile including username', function (): void {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
        'username' => 'oldusername',
    ]);

    Sanctum::actingAs($user);

    $this->putJson(route('api.v1.auth.me.update'), [
        'name' => 'New Name',
        'email' => 'new@example.com',
        'username' => 'newusername',
    ])->assertApiSuccess('User profile updated successfully.')
        ->assertJsonPath('data.name', 'New Name')
        ->assertJsonPath('data.email', 'new@example.com')
        ->assertJsonPath('data.username', 'newusername');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'New Name',
        'email' => 'new@example.com',
        'username' => 'newusername',
    ]);
});

test('authenticated user can update their password', function (): void {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);

    Sanctum::actingAs($user);

    $this->putJson(route('api.v1.auth.me.password.update'), [
        'current_password' => 'OldPassword123!',
        'password' => 'NewPassword456!',
        'password_confirmation' => 'NewPassword456!',
    ])->assertApiSuccess('Password updated successfully.');

    expect(Hash::check('NewPassword456!', (string) $user->fresh()->password))->toBeTrue();
});

test('authenticated user can upload an avatar image', function (): void {
    Storage::fake('public');

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->image('profile.jpg', 200, 200);

    $response = $this->postJson(route('api.v1.auth.me.avatar.upload'), [
        'avatar' => $file,
    ]);

    $response->assertApiSuccess('Avatar uploaded successfully.')
        ->assertJsonStructure([
            'data' => [
                'id',
                'avatar',
                'avatar_url',
            ],
        ]);

    $user->refresh();
    expect($user->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar);
});

test('avatar upload validates file type and size', function (): void {
    Storage::fake('public');

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $invalidFile = UploadedFile::fake()->create('document.pdf', 500);

    $this->postJson(route('api.v1.auth.me.avatar.upload'), [
        'avatar' => $invalidFile,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['avatar']);
});

test('authenticated user can delete their avatar', function (): void {
    Storage::fake('public');

    $user = User::factory()->create([
        'avatar' => 'avatars/existing.jpg',
    ]);
    Storage::disk('public')->put('avatars/existing.jpg', 'fake-image-content');

    Sanctum::actingAs($user);

    $response = $this->deleteJson(route('api.v1.auth.me.avatar.delete'));

    $response->assertApiSuccess('Avatar deleted successfully.')
        ->assertJsonPath('data.avatar', null)
        ->assertJsonPath('data.avatar_url', null);

    $user->refresh();
    expect($user->avatar)->toBeNull();
    Storage::disk('public')->assertMissing('avatars/existing.jpg');
});

test('authenticated user can logout', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('logout-test')->plainTextToken;

    $this->withToken($token)
        ->postJson(route('api.v1.auth.logout'))
        ->assertApiSuccess('Successfully logged out.');

    expect($user->fresh()->tokens)->toHaveCount(0);
});
