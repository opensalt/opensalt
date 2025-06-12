<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsAssociation;

class UpdateAssociationCommand extends BaseCommand
{
    public function __construct(private readonly LsAssociation $association)
    {
    }

    public function getAssociation(): LsAssociation
    {
        return $this->association;
    }
}
