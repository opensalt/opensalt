<?php

namespace App\Repository\Framework;

use App\Entity\Framework\ObjectLock;
use App\Entity\LockableInterface;
use App\Exception\AlreadyLockedException;
use CftfBundle\Entity\LsDoc;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Salt\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class ObjectLockRepository
 *
 * @method null|ObjectLock findOneBy(array $criteria, array $orderBy = null)
 */
class ObjectLockRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ObjectLock::class);
    }

    public function findLockFor(LockableInterface $obj): ?ObjectLock
    {
        $lock = $this->findOneBy(['objectType' => \get_class($obj), 'objectId' => $obj->getId()]);

        return $lock;
    }

    /**
     * @return ObjectLock[]
     */
    public function findDocLocks(LsDoc $doc): array
    {
        $qb = $this->createQueryBuilder('o');
        $query = $qb->select('o')
            ->where('o.doc = :doc')
            ->andWhere('o.timeout > :now')
            ->setParameter('doc', $doc)
            ->setParameter('now', new \DateTime())
            ->getQuery();
        return $query->getResult();
    }

    /**
     * @throws AlreadyLockedException
     */
    public function acquireLock(LockableInterface $obj, User $user, int $timeout = 5): ObjectLock
    {
        $lock = $this->findLockFor($obj);

        if (null !== $lock && $lock->isExpired()) {
            //$this->release($lock);
            $this->removeExpiredLocks();
            $lock = null;
        }

        if (null !== $lock && $lock->getUser() !== $user) {
            throw new AlreadyLockedException('Cannot acquire lock');
        }

        if (null !== $lock && $lock->getUser() === $user) {
            $lock->addTime(5);
            return $lock;
        }

        $lock = new ObjectLock($obj, $user, $timeout);
        $this->getEntityManager()->persist($lock);

        return $lock;
    }

    public function releaseLock(LockableInterface $obj, ?User $user = null): void
    {
        $lock = $this->findLockFor($obj);

        if (null !== $lock && $lock->isExpired()) {
            $this->release($lock);
            $lock = null;
        }

        if (null === $lock) {
            return;
        }

        if (null !== $user && $lock->getUser() !== $user) {
            throw new \RuntimeException('Cannot release lock for a different user');
        }

        $this->_em->remove($lock);
    }

    public function release(ObjectLock $lock): void
    {
        if (null === $lock->getId()) {
            return;
        }

        //$this->_em->remove($lock);
        $this->_em->getConnection()->delete($this->getClassMetadata()->getTableName(), ['id' => $lock->getId()]);
        $this->_em->detach($lock);
    }

    public function removeExpiredLocks(): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $query = $qb->delete($this->_entityName, 'o')
            ->where('o.timeout < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery();
        $query->execute();
    }
}
