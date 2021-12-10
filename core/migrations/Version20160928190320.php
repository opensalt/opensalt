<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20160928190320 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ls_doc_subject (ls_doc_id INT NOT NULL, subject_id INT NOT NULL, INDEX IDX_D9A8D9199388802C (ls_doc_id), INDEX IDX_D9A8D91923EDC87 (subject_id), PRIMARY KEY(ls_doc_id, subject_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ls_doc_subject ADD CONSTRAINT FK_D9A8D9199388802C FOREIGN KEY (ls_doc_id) REFERENCES ls_doc (id)');
        $this->addSql('ALTER TABLE ls_doc_subject ADD CONSTRAINT FK_D9A8D91923EDC87 FOREIGN KEY (subject_id) REFERENCES ls_def_subject (id)');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ls_doc_subject');
    }
}
