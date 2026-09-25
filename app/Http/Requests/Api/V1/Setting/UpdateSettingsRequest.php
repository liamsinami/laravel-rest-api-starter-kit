<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Setting;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'settings' => ['sometimes', 'array'],
        ];
    }

    /**
     * Get settings data to update.
     *
     * @return array<string, mixed>
     */
    public function settingsData(): array
    {
        $validated = $this->validated();

        if (isset($validated['settings']) && is_array($validated['settings'])) {
            return $validated['settings'];
        }

        // If sent as direct key-value pairs in JSON body (excluding framework keys)
        $all = $this->except(['_token', '_method']);

        return isset($all['settings']) && is_array($all['settings']) ? $all['settings'] : $all;
    }
}
