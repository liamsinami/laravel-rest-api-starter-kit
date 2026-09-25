<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
final class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileName = fake()->uuid().'.pdf';

        return [
            'user_id' => User::factory(),
            'name' => 'document.pdf',
            'description' => fake()->sentence(),
            'path' => 'attachments/2026/09/'.$fileName,
            'disk' => 'public',
            'mime' => 'application/pdf',
            'size' => 102400,
            'is_private' => false,
        ];
    }
}
