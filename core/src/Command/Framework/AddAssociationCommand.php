<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsAssociation;
use Symfony\Component\Validator\Constraints as Assert;

class AddAssociationCommand extends BaseCommand
{
    /**
     * @var LsAssociation
     *
     * @Assert\Type(LsAssociation::class)
     * @Assert\NotNull()
     */
    private $association;

    /**
     * Constructor.
     */
    public function __construct(LsAssociation $association)
    {
        $this->association = $association;
    }

    public function getAssociation(): LsAssociation
    {
        return $this->association;
    }
}
