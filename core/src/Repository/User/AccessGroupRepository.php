<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\User\AccessGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AccessGroup|null findOneByName(string $orgName)
 * @method AccessGroup[] findAll()
 *
 * @extends ServiceEntityRepository<AccessGroup>
 */
class AccessGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessGroup::class);
    }

    public function addNewAccessGroup(string $organizationName): AccessGroup
    {
        $group = new AccessGroup();
        $group->setName($organizationName);

        $this->getEntityManager()->persist($group);

        return $group;
    }
}
