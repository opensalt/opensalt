<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20160811180746 extends AbstractMigration
{
    /**
     * @throws \Doctrine\Migrations\Exception\AbortMigration
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_association DROP weight');
    }

    /**
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_association ADD weight NUMERIC(5, 2) DEFAULT NULL');
    }
}
