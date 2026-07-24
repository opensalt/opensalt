<?php

declare(strict_types=1);

namespace App\Articulations\Model;

use App\Entity\Framework\LsItem;

final readonly class InstitutionMatch
{
    public function __construct(
        public LsItem $item,
        public Identifier $matchedIdentifier,
    ) {
    }
}
