<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20160927144534 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item ADD item_type_id INT DEFAULT NULL AFTER `type`');
        $this->addSql('ALTER TABLE ls_item ADD CONSTRAINT FK_D8D02498CE11AAC7 FOREIGN KEY (item_type_id) REFERENCES ls_def_item_type (id)');
        $this->addSql('CREATE INDEX IDX_D8D02498CE11AAC7 ON ls_item (item_type_id)');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item DROP FOREIGN KEY FK_D8D02498CE11AAC7');
        $this->addSql('DROP INDEX IDX_D8D02498CE11AAC7 ON ls_item');
        $this->addSql('ALTER TABLE ls_item DROP item_type_id');
    }
}
