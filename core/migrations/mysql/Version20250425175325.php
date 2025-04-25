<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250425175325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add package and definition extensions to ls_doc';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_doc ADD package_ext JSON DEFAULT NULL, ADD def_ext JSON DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_doc DROP package_ext, DROP def_ext
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
