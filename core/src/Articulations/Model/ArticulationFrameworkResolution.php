<?php

declare(strict_types=1);

namespace App\Articulations\Model;

use App\Entity\Framework\LsDoc;

final readonly class ArticulationFrameworkResolution
{
    public function __construct(
        public LsDoc $doc,
        public InstitutionMatch $sending,
        public InstitutionMatch $receiving,
    ) {
    }
}
