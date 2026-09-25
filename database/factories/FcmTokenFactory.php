<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FcmToken>
 */
final class FcmTokenFactory extends Factory
{
    protected $model = FcmToken::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => 'fcm_token_'.Str::random(64),
            'device_type' => fake()->randomElement(['android', 'ios', 'web']),
            'device_name' => fake()->randomElement(['Pixel 8', 'Galaxy S24', 'Xiaomi 14', 'iPhone 15']),
            'last_used_at' => now(),
        ];
    }
}
