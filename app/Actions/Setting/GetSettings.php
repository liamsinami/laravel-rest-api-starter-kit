<?php

declare(strict_types=1);

namespace App\Actions\Setting;

use App\Models\Setting;
use Illuminate\Support\Collection;

final readonly class GetSettings
{
    /**
     * @return Collection<int, Setting>
     */
    public function handle(?string $group = null, ?bool $isPublic = null): Collection
    {
        $all = Setting::getAllModels();

        if ($group !== null && $group !== '') {
            $all = $all->where('group', $group);
        }

        if ($isPublic !== null) {
            $all = $all->where('is_public', $isPublic);
        }

        return $all->values();
    }
}
