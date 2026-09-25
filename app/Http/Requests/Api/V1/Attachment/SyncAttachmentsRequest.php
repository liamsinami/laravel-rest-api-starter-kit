<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Attachment;

use Illuminate\Foundation\Http\FormRequest;

final class SyncAttachmentsRequest extends FormRequest
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
            'attachment_ids' => ['required', 'array'],
            'attachment_ids.*' => ['required', 'integer', 'exists:attachments,id'],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function attachmentIds(): array
    {
        /** @var array<int, int> $ids */
        $ids = $this->validated('attachment_ids');

        return $ids;
    }
}
