<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20160824143305 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_association ADD group_uri VARCHAR(300) DEFAULT NULL AFTER group_name');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_association DROP group_uri');
    }
}
