<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20160823233434 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item ADD `rank` BIGINT DEFAULT NULL AFTER list_enum_in_source');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item DROP `rank`');
    }
}
