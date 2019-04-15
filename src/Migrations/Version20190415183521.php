<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190415183521 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ls_def_framework_type (id INT AUTO_INCREMENT NOT NULL, framework_type VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('DROP INDEX changed_doc ON audit_salt_change');
        $this->addSql('ALTER TABLE salt_user CHANGE status status INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE audit_salt_user CHANGE status status INT DEFAULT 0');
        $this->addSql('ALTER TABLE ls_item DROP type');
        $this->addSql('ALTER TABLE ls_doc ADD frameworktype_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ls_doc ADD CONSTRAINT FK_9AE8CF1F3C4C17B2 FOREIGN KEY (frameworktype_id) REFERENCES ls_def_framework_type (id)');
        $this->addSql('CREATE INDEX IDX_9AE8CF1F3C4C17B2 ON ls_doc (frameworktype_id)');
        $this->addSql('ALTER TABLE audit_ls_doc ADD frameworktype_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_561885835E237E06 ON salt_additional_field (name)');
        $this->addSql('CREATE INDEX applies_idx ON salt_additional_field (applies_to)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ls_doc DROP FOREIGN KEY FK_9AE8CF1F3C4C17B2');
        $this->addSql('DROP TABLE ls_def_framework_type');
        $this->addSql('ALTER TABLE audit_ls_doc DROP frameworktype_id');
        $this->addSql('CREATE INDEX changed_doc ON audit_salt_change (doc_id, changed_at)');
        $this->addSql('ALTER TABLE audit_salt_user CHANGE status status INT DEFAULT 2 NOT NULL');
        $this->addSql('DROP INDEX IDX_9AE8CF1F3C4C17B2 ON ls_doc');
        $this->addSql('ALTER TABLE ls_doc DROP frameworktype_id');
        $this->addSql('ALTER TABLE ls_item ADD type VARCHAR(60) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('DROP INDEX UNIQ_561885835E237E06 ON salt_additional_field');
        $this->addSql('DROP INDEX applies_idx ON salt_additional_field');
        $this->addSql('ALTER TABLE salt_user CHANGE status status INT DEFAULT 2 NOT NULL');
    }
}
