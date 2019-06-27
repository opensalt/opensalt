<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160811203854 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
UPDATE ls_association a, ls_item i
    SET a.ls_doc_id = i.ls_doc_id,
        a.ls_doc_uri = i.ls_doc_uri
    WHERE a.origin_lsitem_id = i.id
      AND a.origin_lsitem_id IS NOT NULL
      AND (a.ls_doc_id IS NULL OR a.ls_doc_uri IS NULL);
        ');

        $this->addSql('
ALTER TABLE ls_association
    ADD identifier VARCHAR(300) NULL AFTER id,
    ADD ls_doc_identifier VARCHAR(300) NULL AFTER uri,
    ADD origin_node_identifier VARCHAR(300) NULL AFTER ls_doc_id,
    ADD destination_node_identifier VARCHAR(300) NULL AFTER origin_lsitem_id;
        ');

        // Association identifier
        $this->addSql("
UPDATE ls_association a
    SET a.identifier = REPLACE(a.uri, 'local:', '')
    WHERE a.identifier IS NULL;
        ");

        // Document Identifier
        $this->addSql('
UPDATE ls_association a, ls_doc d
    SET a.ls_doc_identifier = d.global_id
    WHERE a.ls_doc_id = d.id
      AND a.ls_doc_id IS NOT NULL;
        ');

        // Origin node identifier
        $this->addSql('
UPDATE ls_association a, ls_item i
    SET a.origin_node_identifier = i.global_id
  WHERE a.origin_lsitem_id = i.id
    AND a.origin_lsitem_id IS NOT NULL
    AND a.origin_node_identifier IS NULL;
        ');

        // Destination node identifier
        $this->addSql('
UPDATE ls_association a, ls_item i
    SET a.destination_node_identifier = i.global_id
  WHERE a.destination_lsitem_id = i.id
    AND a.destination_lsitem_id IS NOT NULL
    AND a.destination_node_identifier IS NULL;
        ');
        $this->addSql('
UPDATE ls_association a, ls_doc d
    SET a.destination_node_identifier = d.global_id
  WHERE a.destination_lsdoc_id = d.id
    AND a.destination_lsdoc_id IS NOT NULL
    AND a.destination_node_identifier IS NULL;
        ');
        $this->addSql("
UPDATE ls_association a
    SET a.destination_node_identifier = REPLACE(a.destination_node_uri, 'urn:guid:', '')
  WHERE a.destination_node_identifier IS NULL;
        ");

        $this->addSql('
ALTER TABLE ls_association
    CHANGE identifier identifier VARCHAR(300) NOT NULL,
    CHANGE ls_doc_identifier ls_doc_identifier VARCHAR(300) NOT NULL,
    CHANGE origin_node_identifier origin_node_identifier VARCHAR(300) NOT NULL,
    CHANGE destination_node_identifier destination_node_identifier VARCHAR(300) NOT NULL,
    CHANGE uri uri VARCHAR(300) DEFAULT NULL,
    CHANGE origin_node_uri origin_node_uri VARCHAR(300) DEFAULT NULL,
    CHANGE destination_node_uri destination_node_uri VARCHAR(300) DEFAULT NULL;
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
ALTER TABLE ls_association
    DROP ls_doc_identifier,
    DROP identifier,
    DROP origin_node_identifier,
    DROP destination_node_identifier,
    CHANGE uri uri VARCHAR(300) NOT NULL COLLATE utf8_unicode_ci,
    CHANGE origin_node_uri origin_node_uri VARCHAR(300) NOT NULL COLLATE utf8_unicode_ci,
    CHANGE destination_node_uri destination_node_uri VARCHAR(300) NOT NULL COLLATE utf8_unicode_ci
        ');
    }
}
