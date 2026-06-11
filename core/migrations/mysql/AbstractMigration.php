<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration as BaseAbstractMigration;

abstract class AbstractMigration extends BaseAbstractMigration
{
    /**
     * Transactions are re-enabled so that a failed migration rolls back cleanly
     * instead of leaving the database in an inconsistent half-applied state.
     *
     * The original disable was a workaround for doctrine/migrations#1104 which
     * has been resolved upstream.
     */
    public function isTransactional(): bool
    {
        return true;
    }
}
