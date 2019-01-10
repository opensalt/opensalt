<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170511154711 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ls_item_concept (ls_item_id INT NOT NULL, concept_id INT NOT NULL, INDEX IDX_FF2E1B51E27A1FD2 (ls_item_id), INDEX IDX_FF2E1B51F909284E (concept_id), PRIMARY KEY(ls_item_id, concept_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ls_item_concept ADD CONSTRAINT FK_FF2E1B51E27A1FD2 FOREIGN KEY (ls_item_id) REFERENCES ls_item (id)');
        $this->addSql('ALTER TABLE ls_item_concept ADD CONSTRAINT FK_FF2E1B51F909284E FOREIGN KEY (concept_id) REFERENCES ls_def_concept (id)');

        $this->addSql('ALTER TABLE ls_item ADD licence_id INT DEFAULT NULL, ADD alternative_label VARCHAR(255) DEFAULT NULL, ADD status_start DATE DEFAULT NULL, ADD status_end DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE ls_item ADD CONSTRAINT FK_D8D0249826EF07C9 FOREIGN KEY (licence_id) REFERENCES ls_def_licence (id)');
        $this->addSql('CREATE INDEX IDX_D8D0249826EF07C9 ON ls_item (licence_id)');

        $this->addSql('ALTER TABLE ls_doc ADD licence_id INT DEFAULT NULL, ADD extra LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\'');
        $this->addSql('ALTER TABLE ls_doc ADD CONSTRAINT FK_9AE8CF1F26EF07C9 FOREIGN KEY (licence_id) REFERENCES ls_def_licence (id)');
        $this->addSql('CREATE INDEX IDX_9AE8CF1F26EF07C9 ON ls_doc (licence_id)');

        $this->addSql('ALTER TABLE ls_doc_subject RENAME INDEX idx_d9a8d9199388802c TO IDX_71E9B5BC9388802C');
        $this->addSql('ALTER TABLE ls_doc_subject RENAME INDEX idx_d9a8d91923edc87 TO IDX_71E9B5BC23EDC87');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE ls_item_concept');

        $this->addSql('ALTER TABLE ls_doc DROP FOREIGN KEY FK_9AE8CF1F26EF07C9');
        $this->addSql('DROP INDEX IDX_9AE8CF1F26EF07C9 ON ls_doc');
        $this->addSql('ALTER TABLE ls_doc DROP licence_id, DROP extra');

        $this->addSql('ALTER TABLE ls_doc_subject RENAME INDEX idx_71e9b5bc9388802c TO IDX_D9A8D9199388802C');
        $this->addSql('ALTER TABLE ls_doc_subject RENAME INDEX idx_71e9b5bc23edc87 TO IDX_D9A8D91923EDC87');

        $this->addSql('ALTER TABLE ls_item DROP FOREIGN KEY FK_D8D0249826EF07C9');
        $this->addSql('DROP INDEX IDX_D8D0249826EF07C9 ON ls_item');
        $this->addSql('ALTER TABLE ls_item DROP licence_id, DROP alternative_label, DROP status_start, DROP status_end');
    }
}
