<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160811225500 extends AbstractMigration
{
    /**
     * @param \Doctrine\DBAL\Schema\Schema $schema
     *
     * @throws \Doctrine\Migrations\Exception\AbortMigration
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

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
     * @param \Doctrine\DBAL\Schema\Schema $schema
     *
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
    ALTER TABLE ls_item
        DROP ls_doc_identifier
        ');
    }
}
