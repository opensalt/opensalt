<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20160825152738 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item DROP source_ls_item_uri, DROP exemplar_resource_uri');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item ADD source_ls_item_uri VARCHAR(300) DEFAULT NULL COLLATE utf8_unicode_ci, ADD exemplar_resource_uri VARCHAR(300) DEFAULT NULL COLLATE utf8_unicode_ci');
    }
}
