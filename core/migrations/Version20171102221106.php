<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20171102221106 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE salt_comment DROP fullname');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE salt_comment ADD fullname VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci');
    }
}
