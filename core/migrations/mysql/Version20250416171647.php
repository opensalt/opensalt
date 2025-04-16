<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250416171647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add caseVersion to document';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_doc ADD case_version VARCHAR(255) DEFAULT NULL AFTER identifier
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_doc DROP case_version
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
