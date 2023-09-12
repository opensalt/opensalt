<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20180910183057 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item DROP `rank`');
        $this->addSql('ALTER TABLE audit_ls_item DROP `rank`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_ls_item ADD `rank` BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE ls_item ADD `rank` BIGINT DEFAULT NULL');
    }
}
