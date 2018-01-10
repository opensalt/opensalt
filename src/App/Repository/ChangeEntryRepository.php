<?php

namespace App\Repository;

use App\Entity\ChangeEntry;
use App\Event\NotificationEvent;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityRepository;

class ChangeEntryRepository extends EntityRepository
{
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
}
