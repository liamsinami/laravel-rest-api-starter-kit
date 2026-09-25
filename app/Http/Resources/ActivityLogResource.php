<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Activitylog\Models\Activity;

/**
 * @mixin Activity
 */
final class ActivityLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{id: int, log_name: ?string, description: string, event: ?string, subject_type: ?string, subject_id: ?string, subject_name: ?string, causer: ?array{id: string, name: string, email: string}, properties: mixed, changes: mixed, created_at: ?string}
     */
    public function toArray(Request $request): array
    {
        /** @var User|null $causer */
        $causer = $this->relationLoaded('causer') ? $this->causer : null;

        /** @var object|null $subject */
        $subject = $this->relationLoaded('subject') ? $this->subject : null;

        $subjectName = null;
        if (is_object($subject)) {
            $subjectName = $subject->name ?? $subject->title ?? (string) $this->subject_id;
        }

        return [
            'id' => $this->id,
            'log_name' => $this->log_name,
            'description' => $this->description,
            'event' => $this->event,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id !== null ? (string) $this->subject_id : null,
            'subject_name' => $subjectName,
            'causer' => $causer !== null ? [
                'id' => $causer->id,
                'name' => $causer->name,
                'email' => $causer->email,
            ] : null,
            'properties' => $this->properties,
            'changes' => $this->attribute_changes,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
