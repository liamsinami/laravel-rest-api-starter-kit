<?php

declare(strict_types=1);

namespace App\Query\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

final readonly class QueryContext
{
    /**
     * @param  array<string, mixed>  $params
     */
    public function __construct(
        public array $params = [],
        public ?string $userId = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $authenticatedUser = $request->user();
        $params = $request->query();

        // Normalize direct query params for seamless backward compatibility
        $directFilters = ['search', 'role', 'log_name', 'event', 'subject_type', 'guard_name', 'trashed'];
        foreach ($directFilters as $filterKey) {
            if ($request->has($filterKey) && ! isset($params['filter'][$filterKey])) {
                $params['filter'][$filterKey] = $request->query($filterKey);
            }
        }

        return new self(
            params: $params,
            userId: $authenticatedUser instanceof User ? (string) $authenticatedUser->id : null,
        );
    }

    public function toRequest(): Request
    {
        return Request::create('/', 'GET', $this->params);
    }

    public function perPage(int $default = 15, int $max = 100): int
    {
        $perPage = (int) ($this->params['per_page'] ?? $this->params['perpage'] ?? $default);

        return max(1, min($perPage, $max));
    }

    public function wantsPagination(bool $defaultPagination = true): bool
    {
        if (isset($this->params['paginate'])) {
            return filter_var($this->params['paginate'], FILTER_VALIDATE_BOOL);
        }

        if (Arr::hasAny($this->params, ['page', 'per_page', 'perpage'])) {
            return true;
        }

        return $defaultPagination;
    }

    public function wantsExport(): bool
    {
        return filter_var($this->params['export'] ?? false, FILTER_VALIDATE_BOOL);
    }
}
