<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsAssociation;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteAssociationCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(LsAssociation::class)]
        #[Assert\NotNull]
        private readonly LsAssociation $association,
    ) {
    }

    public function getAssociation(): LsAssociation
    {
        return $this->association;
    }
}
