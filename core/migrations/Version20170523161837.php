<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20170523161837 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D8D02498772E836A ON ls_item (identifier)');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_D8D02498772E836A ON ls_item');
    }
}
