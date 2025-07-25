<?php

declare(strict_types=1);

namespace App\Domain\Credential\DTO;

use App\Entity\User\AccessGroup;
use Symfony\Component\Validator\Constraints as Assert;

class CredentialDefinitionDto
{
    #[Assert\NotBlank(message: 'The collection must be provided.')]
    public ?string $hierarchyParent = null;

    public ?AccessGroup $organization = null;

    public ?string $content = null;
}
