<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDefAssociationGrouping;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteAssociationGroupCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(LsDefAssociationGrouping::class)]
        #[Assert\NotNull]
        private readonly LsDefAssociationGrouping $associationGrouping,
    ) {
    }

    public function getAssociationGroup(): LsDefAssociationGrouping
    {
        return $this->associationGrouping;
    }
}
