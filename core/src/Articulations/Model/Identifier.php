<?php

declare(strict_types=1);

namespace App\Articulations\Model;

final readonly class Identifier
{
    public function __construct(
        public string $type,
        public string $value,
    ) {
    }
}
