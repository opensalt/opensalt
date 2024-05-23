<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20160914191941 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item ADD language VARCHAR(10) DEFAULT NULL AFTER language_uri');
        $this->addSql('ALTER TABLE ls_doc ADD language VARCHAR(10) DEFAULT NULL AFTER language_uri');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_doc DROP language');
        $this->addSql('ALTER TABLE ls_item DROP language');
    }
}
