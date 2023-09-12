<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20170324210751 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_def_association_grouping ADD ls_doc_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ls_def_association_grouping ADD CONSTRAINT FK_6A465B629388802C FOREIGN KEY (ls_doc_id) REFERENCES ls_doc (id)');
        $this->addSql('CREATE INDEX IDX_6A465B629388802C ON ls_def_association_grouping (ls_doc_id)');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_def_association_grouping DROP FOREIGN KEY FK_6A465B629388802C');
        $this->addSql('DROP INDEX IDX_6A465B629388802C ON ls_def_association_grouping');
        $this->addSql('ALTER TABLE ls_def_association_grouping DROP ls_doc_id');
    }
}
