<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20160826134721 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item ADD extra LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\'');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item DROP extra');
    }
}
