<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class BatchDeleteUsers
{
    /**
     * Delete multiple users.
     *
     * @param  array<int, string>  $ids
     */
    public function handle(array $ids): int
    {
        return DB::transaction(fn (): int => User::query()->whereIn('id', $ids)->delete());
    }
}
