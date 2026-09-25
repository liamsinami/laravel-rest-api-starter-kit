<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Fcm;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterFcmTokenRequest extends FormRequest
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
            'token' => ['required', 'string', 'max:500'],
            'device_type' => ['sometimes', 'string', 'in:android,ios,web'],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
