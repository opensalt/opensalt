<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20161128163007 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE salt_user ADD github_token varchar(40) DEFAULT NULL');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE salt_user DROP COLUMN github_token');
    }
}
