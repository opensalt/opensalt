<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20160720143502 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
ALTER TABLE ls_association
    ADD ls_doc_uri VARCHAR(300) NULL AFTER uri,
    ADD ls_doc_id INT DEFAULT NULL AFTER ls_doc_uri
        ');
        $this->addSql('ALTER TABLE ls_association ADD CONSTRAINT FK_A84022D49388802C FOREIGN KEY (ls_doc_id) REFERENCES ls_doc (id)');
        $this->addSql('CREATE INDEX IDX_A84022D49388802C ON ls_association (ls_doc_id)');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_association DROP FOREIGN KEY FK_A84022D49388802C');
        $this->addSql('DROP INDEX IDX_A84022D49388802C ON ls_association');
        $this->addSql('
ALTER TABLE ls_association
    DROP ls_doc_uri,
    DROP ls_doc_id
        ');
    }
}
