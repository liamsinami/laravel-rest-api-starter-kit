<?php

declare(strict_types=1);

namespace App\Query\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class Sort
{
    public function __construct(public string $name) {}
}
