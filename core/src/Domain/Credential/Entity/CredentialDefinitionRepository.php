<?php

declare(strict_types=1);

namespace App\Domain\Credential\Entity;

use Ecotone\Modelling\Attribute\Repository;
use Symfony\Component\Uid\Uuid;

interface CredentialDefinitionRepository
{
    #[Repository]
    public function findBy(Uuid $id): CredentialDefinition;
}
