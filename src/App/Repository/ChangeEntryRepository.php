<?php

namespace App\Repository;

use App\Entity\ChangeEntry;
use App\Event\NotificationEvent;
use CftfBundle\Entity\LsDoc;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ChangeEntryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ChangeEntry::class);
    }

    /**
     * @throws DBALException
     */
    public function updateChanged(ChangeEntry $change, NotificationEvent $notification): void
    {
        // Update both the normal and audit tables
        $this->_em->getConnection()->executeUpdate(
            sprintf('UPDATE audit_%s SET changed = ? WHERE changed_at = ? and description = ?', $this->getClassMetadata()->getTableName()),
            [json_encode($notification->getChanged()), $change->getChangedAt()->format('Y-m-d H:i:s.u'), $change->getDescription()]
        );
        $this->_em->getConnection()->executeUpdate(
            sprintf('UPDATE %s SET changed = ? WHERE changed_at = ? and description = ?', $this->getClassMetadata()->getTableName()),
            [json_encode($notification->getChanged()), $change->getChangedAt()->format('Y-m-d H:i:s.u'), $change->getDescription()]
        );
    }

    public function getChangeEntryCountForDoc(LsDoc $doc): int
    {
        $data = $this->_em->getConnection()->createQueryBuilder()
            ->select('count(*)')
            ->from('audit_'.$this->getClassMetadata()->getTableName(), 'a')
            ->where('a.doc_id = :doc_id')
            ->setParameter('doc_id', $doc->getId())
            ->execute()
            ->fetchColumn();

        return $data;
    }

    public function getChangeEntriesForDoc(LsDoc $doc, int $limit = 20, int $offset = 0): Statement
    {
        $data = $this->_em->getConnection()->createQueryBuilder()
            ->select('a.rev, a.changed_at, a.description, u.username')
            ->from('audit_'.$this->getClassMetadata()->getTableName(), 'a')
            ->leftJoin('a', 'salt_user', 'u', 'u.id = a.user_id')
            ->where('a.doc_id = :doc_id')
            ->setParameter('doc_id', $doc->getId())
            ->orderBy('a.rev', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults(($limit > 0) ? $limit : 1000000)
            ->execute();

        return $data;
    }

    public function getChangeEntryCountForSystem(): int
    {
        $data = $this->_em->getConnection()->createQueryBuilder()
            ->select('count(*)')
            ->from('audit_'.$this->getClassMetadata()->getTableName(), 'a')
            ->where('a.doc_id IS NULL')
            ->execute()
            ->fetchColumn();

        return $data;
    }

    public function getChangeEntriesForSystem(int $limit = 20, int $offset = 0): Statement
    {
        $data = $this->_em->getConnection()->createQueryBuilder()
            ->select('a.rev, a.changed_at, a.description, u.username')
            ->from('audit_'.$this->getClassMetadata()->getTableName(), 'a')
            ->leftJoin('a', 'salt_user', 'u', 'u.id = a.user_id')
            ->where('a.doc_id IS NULL')
            ->orderBy('a.rev', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults(($limit > 0) ? $limit : 1000000)
            ->execute();

        return $data;
    }
}
