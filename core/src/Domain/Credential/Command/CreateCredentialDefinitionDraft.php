<?php

declare(strict_types=1);

namespace App\Domain\Credential\Command;

final readonly class CreateCredentialDefinitionDraft
{
    public function __construct(
        public string $hierarchyParent,
        public int $organization,
        public array $content,
    ) {
    }
}
