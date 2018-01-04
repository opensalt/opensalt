<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsAssociation;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteAssociationCommand extends BaseCommand
{
    /**
     * @var LsAssociation
     *
     * @Assert\Type(LsAssociation::class)
     * @Assert\NotNull()
     */
    private $association;

    public function __construct(LsAssociation $association)
    {
        $this->association = $association;
    }

    public function getAssociation(): LsAssociation
    {
        return $this->association;
    }
}
