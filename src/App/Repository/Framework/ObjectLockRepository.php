<?php

namespace App\Repository\Framework;

use App\Entity\Framework\ObjectLock;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\ORM\EntityRepository;
use Salt\UserBundle\Entity\User;

/**
 * Class ObjectLockRepository
 *
 * @method null|ObjectLock findOneBy(array $criteria, array $orderBy = null)
 */
class ObjectLockRepository extends EntityRepository
{
    public function getDocLock(LsDoc $doc): ?ObjectLock
    {
        return $this->findOneBy(['lock' => 'doc:'.$doc->getId()]);
    }

    public function getItemLock(LsItem $item): ?ObjectLock
    {
        return $this->findOneBy(['lock' => 'item:'.$item->getId()]);
    }

    public function createDocLock(LsDoc $doc, User $user, int $timeout = 5): ObjectLock
    {
        $lock = new ObjectLock('doc', $doc->getId(), $user, $timeout);
        $this->getEntityManager()->persist($lock);

        return $lock;
    }

    public function createItemLock(LsItem $item, User $user, int $timeout = 5): ObjectLock
    {
        $lock = new ObjectLock('item', $item->getId(), $user, $timeout);
        $this->getEntityManager()->persist($lock);

        return $lock;
    }

    public function removeExpiredLocks(): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $query = $qb->delete($this->_entityName, 'o')
            ->where('o.expiry < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery();
        $query->execute();
    }
}
