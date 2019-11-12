<?php

namespace App\Repository\User;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Entity\User\Organization;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Organization|null findOneByName(string $orgName)
 */
class OrganizationRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    /**
     * Add a new organization to the system.
     */
    public function addNewOrganization(string $organizationName): Organization
    {
        $org = new Organization();
        $org->setName($organizationName);

        $this->getEntityManager()->persist($org);

        return $org;
    }
}
