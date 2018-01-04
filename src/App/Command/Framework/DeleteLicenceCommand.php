<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsDefLicence;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteLicenceCommand extends BaseCommand
{
    /**
     * @var LsDefLicence
     *
     * @Assert\Type(LsDefLicence::class)
     * @Assert\NotNull()
     */
    private $licence;

    public function __construct(LsDefLicence $licence)
    {
        $this->licence = $licence;
    }

    public function getLicence(): LsDefLicence
    {
        return $this->licence;
    }
}
