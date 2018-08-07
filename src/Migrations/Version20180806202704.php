<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180806202704 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE id_entries (entity_id VARCHAR(255) NOT NULL, id VARCHAR(255) NOT NULL, expiry_timestamp INT NOT NULL, PRIMARY KEY(entity_id, id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('DROP INDEX changed_doc ON audit_salt_change');
        $this->addSql('ALTER TABLE salt_user CHANGE status status INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE audit_salt_user CHANGE status status INT DEFAULT 0');
        $this->addSql('ALTER TABLE ls_item DROP type');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE id_entries');
        $this->addSql('CREATE INDEX changed_doc ON audit_salt_change (doc_id, changed_at)');
        $this->addSql('ALTER TABLE audit_salt_user CHANGE status status INT DEFAULT 2 NOT NULL');
        $this->addSql('ALTER TABLE ls_item ADD type VARCHAR(60) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE salt_user CHANGE status status INT DEFAULT 2 NOT NULL');
    }
}
