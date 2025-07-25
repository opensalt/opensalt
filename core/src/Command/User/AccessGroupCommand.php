<?php

declare(strict_types=1);

namespace App\Command\User;

use App\Command\BaseCommand;
use App\Entity\User\AccessGroup;
use Symfony\Component\Validator\Constraints as Assert;

abstract class AccessGroupCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(AccessGroup::class)]
        #[Assert\NotNull]
        private readonly AccessGroup $accessGroup,
    ) {
    }

    public function getAccessGroup(): AccessGroup
    {
        return $this->accessGroup;
    }
}
