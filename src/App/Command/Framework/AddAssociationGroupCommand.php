<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsDefAssociationGrouping;
use Symfony\Component\Validator\Constraints as Assert;

class AddAssociationGroupCommand extends BaseCommand
{
    /**
     * @var LsDefAssociationGrouping
     *
     * @Assert\Type(LsDefAssociationGrouping::class)
     * @Assert\NotNull()
     */
    private $associationGrouping;

    /**
     * Constructor.
     *
     * @param LsDefAssociationGrouping $associationGrouping
     */
    public function __construct(LsDefAssociationGrouping $associationGrouping)
    {
        $this->associationGrouping = $associationGrouping;
    }

    public function getAssociationGroup(): LsDefAssociationGrouping
    {
        return $this->associationGrouping;
    }
}
