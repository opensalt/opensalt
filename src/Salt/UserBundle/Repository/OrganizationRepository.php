<?php

namespace Salt\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Salt\UserBundle\Entity\Organization;

/**
 * OrganizationRepository
 *
 * @method Organization findOneByName(string $orgName)
 */
class OrganizationRepository extends EntityRepository
{
    /**
     * Add a new organization to the system
     *
     * @param string $organizationName
     *
     * @return Organization
     */
    public function addNewOrganization($organizationName) {
        $org = new Organization();
        $org->setName($organizationName);

        $this->getEntityManager()->persist($org);

        return $org;
    }

    /**
     * Search all organizations which contains passed string.
     *
     * string|null $search
     *
     * @return array
     */
    public function findOrg($search): array
    {
        $qb = $this->createQueryBuilder('orgs');
        $qb->select('orgs.id')
            ->where('orgs.name LIKE :search')
            ->setParameter('search', '%'.$search.'%');
        return $qb->getQuery()->getResult();
    }
}
