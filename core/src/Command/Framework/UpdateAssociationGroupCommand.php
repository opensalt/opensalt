<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDefAssociationGrouping;

class UpdateAssociationGroupCommand extends BaseCommand
{
    public function __construct(private readonly LsDefAssociationGrouping $associationGrouping)
    {
    }

    public function getAssociationGrouping(): LsDefAssociationGrouping
    {
        return $this->associationGrouping;
    }
}
