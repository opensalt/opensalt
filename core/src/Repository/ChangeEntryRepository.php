<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ChangeEntry;
use App\Entity\Framework\LsDoc;
use App\Event\NotificationEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChangeEntry>
 */
class ChangeEntryRepository extends ServiceEntityRepository
{
    final public const MAX_RESULTS = 50_000;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChangeEntry::class);
    }

    public function updateChanged(ChangeEntry $change, NotificationEvent $notification): void
    {
        if (null !== $change->getId()) {
            // Table name from Doctrine metadata — safe from injection (not user input)
            $this->getEntityManager()->getConnection()->executeStatement(
                sprintf('UPDATE %s SET changed = ? WHERE id = ?', $this->getClassMetadata()->getTableName()),
                [json_encode($notification->getChanged(), JSON_THROW_ON_ERROR), $change->getId()]
            );

            if (null === $change->getDocId() && null !== $notification->getDoc()) {
                // Table name from Doctrine metadata — safe from injection (not user input)
                $this->getEntityManager()->getConnection()->executeStatement(
                    sprintf('UPDATE %s SET doc_id = ? WHERE id = ?', $this->getClassMetadata()->getTableName()),
                    [$notification->getDoc()->getId(), $change->getId()]
                );
            }

            return;
        }

        // ID should always be available after flush — if we reach here, something is wrong upstream
        throw new \LogicException(sprintf('ChangeEntry ID is null in updateChanged(). Description: "%s". Ensure the entity is persisted and flushed before calling updateChanged().', $change->getDescription()));
    }

    /**
     * @return array{'changed_at': ?string}
     */
    public function getLastChangeTimeForDoc(LsDoc $doc): array
    {
        return $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('MAX(a.changed_at) as changed_at')
            ->from($this->getClassMetadata()->getTableName(), 'a')
            ->where('a.doc_id = :doc_id')
            ->setParameter('doc_id', $doc->getId())
            ->executeQuery()
            ->fetchAssociative();
    }

    public function getChangeEntryCountForDoc(LsDoc $doc): int
    {
        return $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('count(*)')
            ->from($this->getClassMetadata()->getTableName(), 'a')
            ->where('a.doc_id = :doc_id')
            ->setParameter('doc_id', $doc->getId())
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @return iterable<array-key, array{'rev': int, 'changed_at': string, 'description': string, 'username': string}>
     */
    public function getChangeEntriesForDoc(LsDoc $doc, int $limit = 20, int $offset = 0): iterable
    {
        $results = $this->createQueryBuilder('a')
            ->select('a.id AS rev, a.changedAt AS changed_at, a.description, a.username')
            ->where('a.doc = :doc_id')
            ->setParameter('doc_id', $doc->getId())
            ->orderBy('a.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults(($limit > 0) ? min($limit, self::MAX_RESULTS) : self::MAX_RESULTS)
            ->getQuery()
            ->setHydrationMode(Query::HYDRATE_ARRAY)
            ->toIterable();

        foreach ($results as $result) {
            yield [
                'rev' => $result['rev'],
                'changed_at' => $result['changed_at']->format('Y-m-d H:i:s.u'),
                'description' => $result['description'],
                'username' => $result['username'],
            ];
        }
    }

    public function getChangeEntryCountForSystem(): int
    {
        return $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('count(*)')
            ->from($this->getClassMetadata()->getTableName(), 'a')
            ->where('a.doc_id IS NULL')
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @return iterable<array-key, array{'rev': int, 'changed_at': string, 'description': string, 'username': string}>
     */
    public function getChangeEntriesForSystem(int $limit = 20, int $offset = 0): iterable
    {
        $results = $this->createQueryBuilder('a')
            ->select('a.id AS rev, a.changedAt AS changed_at, a.description, a.username')
            ->where('a.doc IS NULL')
            ->orderBy('a.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults(($limit > 0) ? min($limit, self::MAX_RESULTS) : self::MAX_RESULTS)
            ->getQuery()
            ->setHydrationMode(Query::HYDRATE_ARRAY)
            ->toIterable();

        foreach ($results as $result) {
            yield [
                'rev' => $result['rev'],
                'changed_at' => $result['changed_at']->format('Y-m-d H:i:s.u'),
                'description' => $result['description'],
                'username' => $result['username'],
            ];
        }
    }
}
