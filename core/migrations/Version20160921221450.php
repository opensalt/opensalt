<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20160921221450 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_def_grade ADD `rank` INT DEFAULT NULL');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_def_grade DROP `rank`');
    }
}
