<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20170201212306 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item CHANGE list_enum_in_source list_enum_in_source VARCHAR(20) DEFAULT NULL COLLATE utf8_unicode_ci');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item CHANGE list_enum_in_source list_enum_in_source VARCHAR(10) DEFAULT NULL COLLATE utf8_unicode_ci');
    }
}
