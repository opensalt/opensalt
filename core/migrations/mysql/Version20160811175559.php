<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20160811175559 extends AbstractMigration
{
    /**
     * @throws \Doctrine\Migrations\Exception\AbortMigration
     */
    public function up(Schema $schema): void
    {
        $this->addSql('
ALTER TABLE ls_association
        ADD group_name VARCHAR(50) DEFAULT NULL AFTER id
        ');
    }

    /**
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->addSql('
ALTER TABLE ls_association
    DROP group_name
        ');
    }
}
