<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration as BaseAbstractMigration;

abstract class AbstractMigration extends BaseAbstractMigration
{
    /**
     * Disable transactional migrations because MySQL DDL statements
     * (ALTER TABLE, DROP TABLE, etc.) cause implicit commits, making
     * transactions ineffective and triggering deprecation warnings
     * when Doctrine tries to commit an already-committed transaction.
     *
     * @see https://www.doctrine-project.org/projects/doctrine-migrations/en/stable/explanation/implicit-commits.html
     * @see https://github.com/doctrine/migrations/issues/1169
     */
    public function isTransactional(): bool
    {
        return false;
    }
}
