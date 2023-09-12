<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20160808195223 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
ALTER TABLE ls_item
    CHANGE list_enum_in_source list_enum_in_source VARCHAR(10) DEFAULT NULL
        ');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('
ALTER TABLE ls_item
    CHANGE list_enum_in_source list_enum_in_source VARCHAR(5) DEFAULT NULL
        ');
    }
}
