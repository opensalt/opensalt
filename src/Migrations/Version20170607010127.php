<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170607010127 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_60C4016C772E836A ON rubric (identifier)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_60C4016C841CB121 ON rubric (uri)');
        $this->addSql('CREATE INDEX IDX_1DA328DC9388802C ON import_logs (ls_doc_id)');
        $this->addSql('ALTER TABLE ls_item CHANGE extra extra json DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D8D02498841CB121 ON ls_item (uri)');
        $this->addSql('ALTER TABLE ls_doc CHANGE extra extra json DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE ls_association ADD extra json DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A84022D4772E836A ON ls_association (identifier)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A84022D4841CB121 ON ls_association (uri)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FEC04216772E836A ON rubric_criterion_level (identifier)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FEC04216841CB121 ON rubric_criterion_level (uri)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CA38B808772E836A ON ls_def_licence (identifier)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CA38B808841CB121 ON ls_def_licence (uri)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6A465B62772E836A ON ls_def_association_grouping (identifier)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6A465B62841CB121 ON ls_def_association_grouping (uri)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_98E476F9772E836A ON rubric_criterion (identifier)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_98E476F9841CB121 ON rubric_criterion (uri)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2C5C603A772E836A ON ls_def_subject (identifier)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2C5C603A841CB121 ON ls_def_subject (uri)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3844F7B6772E836A ON ls_def_item_type (identifier)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3844F7B6841CB121 ON ls_def_item_type (uri)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6A10EA72772E836A ON ls_def_grade (identifier)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6A10EA72841CB121 ON ls_def_grade (uri)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_30D83E10772E836A ON ls_def_concept (identifier)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_30D83E10841CB121 ON ls_def_concept (uri)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX IDX_1DA328DC9388802C ON ls_association');
        $this->addSql('DROP INDEX UNIQ_A84022D4772E836A ON ls_association');
        $this->addSql('DROP INDEX UNIQ_A84022D4841CB121 ON ls_association');
        $this->addSql('ALTER TABLE ls_association DROP extra');
        $this->addSql('DROP INDEX UNIQ_6A465B62772E836A ON ls_def_association_grouping');
        $this->addSql('DROP INDEX UNIQ_6A465B62841CB121 ON ls_def_association_grouping');
        $this->addSql('DROP INDEX UNIQ_30D83E10772E836A ON ls_def_concept');
        $this->addSql('DROP INDEX UNIQ_30D83E10841CB121 ON ls_def_concept');
        $this->addSql('DROP INDEX UNIQ_6A10EA72772E836A ON ls_def_grade');
        $this->addSql('DROP INDEX UNIQ_6A10EA72841CB121 ON ls_def_grade');
        $this->addSql('DROP INDEX UNIQ_3844F7B6772E836A ON ls_def_item_type');
        $this->addSql('DROP INDEX UNIQ_3844F7B6841CB121 ON ls_def_item_type');
        $this->addSql('DROP INDEX UNIQ_CA38B808772E836A ON ls_def_licence');
        $this->addSql('DROP INDEX UNIQ_CA38B808841CB121 ON ls_def_licence');
        $this->addSql('DROP INDEX UNIQ_2C5C603A772E836A ON ls_def_subject');
        $this->addSql('DROP INDEX UNIQ_2C5C603A841CB121 ON ls_def_subject');
        $this->addSql('ALTER TABLE ls_doc CHANGE extra extra LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json_array)\'');
        $this->addSql('DROP INDEX UNIQ_D8D02498841CB121 ON ls_item');
        $this->addSql('ALTER TABLE ls_item CHANGE extra extra LONGTEXT DEFAULT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:json_array)\'');
        $this->addSql('DROP INDEX UNIQ_60C4016C772E836A ON rubric');
        $this->addSql('DROP INDEX UNIQ_60C4016C841CB121 ON rubric');
        $this->addSql('DROP INDEX UNIQ_98E476F9772E836A ON rubric_criterion');
        $this->addSql('DROP INDEX UNIQ_98E476F9841CB121 ON rubric_criterion');
        $this->addSql('DROP INDEX UNIQ_FEC04216772E836A ON rubric_criterion_level');
        $this->addSql('DROP INDEX UNIQ_FEC04216841CB121 ON rubric_criterion_level');
    }
}
