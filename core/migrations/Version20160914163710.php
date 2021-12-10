<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20160914163710 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ls_doc_attribute (ls_doc_id INT NOT NULL, attribute VARCHAR(255) NOT NULL, value VARCHAR(255) DEFAULT NULL, INDEX IDX_1DB04FBC9388802C (ls_doc_id), PRIMARY KEY(ls_doc_id, attribute)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ls_doc_attribute ADD CONSTRAINT FK_1DB04FBC9388802C FOREIGN KEY (ls_doc_id) REFERENCES ls_doc (id)');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ls_doc_attribute');
    }
}
