<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20170505215412 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_doc ADD url_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9AE8CF1F4077B7BE ON ls_doc (url_name)');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_9AE8CF1F4077B7BE ON ls_doc');
        $this->addSql('ALTER TABLE ls_doc DROP url_name');
    }
}
