<?php

namespace App\Repository;

use App\Entity\ChangeEntry;
use App\Entity\Framework\LsDoc;
use App\Event\NotificationEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChangeEntry::class>
 */
class ChangeEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChangeEntry::class);
    }

    public function updateChanged(ChangeEntry $change, NotificationEvent $notification): void
    {
        if (null !== $change->getId()) {
            $this->_em->getConnection()->executeStatement(
                sprintf('UPDATE %s SET changed = ? WHERE id = ?', $this->getClassMetadata()->getTableName()),
                [json_encode($notification->getChanged(), JSON_THROW_ON_ERROR), $change->getId()]
            );

            if (null === $change->getDocId() && null !== $notification->getDoc()) {
                $this->_em->getConnection()->executeStatement(
                    sprintf('UPDATE %s SET doc_id = ? WHERE id = ?', $this->getClassMetadata()->getTableName()),
                    [$notification->getDoc()->getId(), $change->getId()]
                );
            }

            return;
        }

        $this->_em->getConnection()->executeStatement(
            sprintf('UPDATE %s SET changed = ? WHERE changed_at = ? and description = ?', $this->getClassMetadata()->getTableName()),
            [json_encode($notification->getChanged(), JSON_THROW_ON_ERROR), $change->getChangedAt()->format('Y-m-d H:i:s.u'), $change->getDescription()]
        );
    }

    /**
     * @return array{'changed_at': string}
     */
    public function getLastChangeTimeForDoc(LsDoc $doc): array
    {
        return $this->_em->getConnection()->createQueryBuilder()
            ->select('MAX(a.changed_at) as changed_at')
            ->from($this->getClassMetadata()->getTableName(), 'a')
            ->where('a.doc_id = :doc_id')
            ->setParameter('doc_id', $doc->getId())
            ->execute()
            ->fetchAssociative();
    }

    public function getChangeEntryCountForDoc(LsDoc $doc): int
    {
        return $this->_em->getConnection()->createQueryBuilder()
            ->select('count(*)')
            ->from($this->getClassMetadata()->getTableName(), 'a')
            ->where('a.doc_id = :doc_id')
            ->setParameter('doc_id', $doc->getId())
            ->execute()
            ->fetchOne();
    }

    /**
     * @return array<array-key, array{'rev': int, 'changed_at': string, 'description': string, 'username': string}>
     */
    public function getChangeEntriesForDoc(LsDoc $doc, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.id AS rev, a.changed_at, a.description, a.username')
            ->where('a.doc_id = :doc_id')
            ->setParameter('doc_id', $doc->getId())
            ->orderBy('a.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults(($limit > 0) ? $limit : 1000000)
            ->getQuery()
            ->getArrayResult();
    }

    public function getChangeEntryCountForSystem(): int
    {
        return $this->_em->getConnection()->createQueryBuilder()
            ->select('count(*)')
            ->from($this->getClassMetadata()->getTableName(), 'a')
            ->where('a.doc_id IS NULL')
            ->execute()
            ->fetchOne();
    }

    /**
     * @return array<array-key, array{'rev': int, 'changed_at': string, 'description': string, 'username': string}>
     */
    public function getChangeEntriesForSystem(int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.id AS rev, a.changed_at, a.description, a.username')
            ->where('a.doc_id IS NULL')
            ->orderBy('a.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults(($limit > 0) ? $limit : 1000000)
            ->getQuery()
            ->getArrayResult();
    }
}
