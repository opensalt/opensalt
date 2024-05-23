<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20160811225500 extends AbstractMigration
{
    /**
     * @throws \Doctrine\Migrations\Exception\AbortMigration
     */
    public function up(Schema $schema): void
    {
        $this->addSql('
ALTER TABLE ls_item
    ADD ls_doc_identifier VARCHAR(300) NULL AFTER id
        ');

        $this->addSql('
UPDATE ls_item i, ls_doc d
   SET i.ls_doc_identifier = d.global_id
 WHERE i.ls_doc_identifier IS NULL
   AND i.ls_doc_id = d.id
        ');

        $this->addSql('
ALTER TABLE ls_item
    CHANGE ls_doc_identifier ls_doc_identifier VARCHAR(300) NOT NULL
        ');
    }

    /**
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->addSql('
    ALTER TABLE ls_item
        DROP ls_doc_identifier
        ');
    }
}
