<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20170506002933 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9AE8CF1F772E836A ON ls_doc (identifier)');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_9AE8CF1F772E836A ON ls_doc');
    }
}
