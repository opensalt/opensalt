<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20170206220209 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_association ADD seq BIGINT DEFAULT NULL AFTER type');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_association DROP seq');
    }
}
