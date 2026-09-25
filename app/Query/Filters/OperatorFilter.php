<?php

declare(strict_types=1);

namespace App\Query\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Filters\Filter;

final class OperatorFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        [$column, $operator] = $this->parseProperty($property);

        if ($operator === 'between') {
            $this->applyBetween($query, $column, $value);

            return;
        }

        if ($operator === 'in') {
            $this->applyIn($query, $column, $value);

            return;
        }

        $this->applyPartial($query, $column, $value);
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function parseProperty(string $property): array
    {
        foreach (['between', 'in'] as $operator) {
            $suffix = "_{$operator}";

            if (Str::endsWith($property, $suffix)) {
                return [Str::beforeLast($property, $suffix), $operator];
            }
        }

        return [$property, null];
    }

    private function applyBetween(Builder $query, string $column, mixed $value): void
    {
        $values = array_values($this->normalizeValues($value));

        if (count($values) < 2) {
            return;
        }

        $query->whereBetween($column, [$values[0], $values[1]]);
    }

    private function applyIn(Builder $query, string $column, mixed $value): void
    {
        $values = $this->normalizeValues($value);

        if ($values === []) {
            return;
        }

        $query->whereIn($column, $values);
    }

    private function applyPartial(Builder $query, string $column, mixed $value): void
    {
        $search = mb_trim((string) $value);

        if ($search === '') {
            return;
        }

        $query->where($column, $this->operator($query), "%{$search}%");
    }

    /**
     * @return array<int, mixed>
     */
    private function normalizeValues(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return [$value];
        }

        return array_values(array_filter($value, static fn (mixed $item): bool => $item !== null && $item !== ''));
    }

    private function operator(Builder $query): string
    {
        return $query->getConnection()->getDriverName() === 'pgsql'
            ? 'ILIKE'
            : 'LIKE';
    }
}
