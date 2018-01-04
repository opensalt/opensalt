<?php

namespace App\Command\User;

use App\Command\BaseCommand;
use Salt\UserBundle\Entity\Organization;
use Symfony\Component\Validator\Constraints as Assert;

abstract class OrganizationCommand extends BaseCommand
{
    /**
     * @var Organization
     *
     * @Assert\Type(Organization::class)
     * @Assert\NotNull()
     */
    private $organization;

    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
    }

    public function getOrg(): Organization
    {
        return $this->organization;
    }
}
