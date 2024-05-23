<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20160914205741 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item DROP language_uri');
        $this->addSql('ALTER TABLE ls_doc DROP language_uri');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_doc ADD language_uri VARCHAR(300) DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE ls_item ADD language_uri VARCHAR(300) DEFAULT NULL COLLATE utf8_unicode_ci');
    }
}
