<?php

declare(strict_types=1);

namespace App\Query\Definitions;

abstract class BaseQueryDefinition
{
    /**
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    abstract public static function model(): string;

    /**
     * @return array<int, string>
     */
    final public static function searchable(): array
    {
        return [];
    }
}
