<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170510163859 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE import_logs (
            id INT AUTO_INCREMENT NOT NULL,
            ls_doc_id INT NOT NULL,
            message_text VARCHAR(250) NOT NULL,
            message_type VARCHAR(30) NOT NULL,
            is_read TINYINT NOT NULL DEFAULT 0,
            PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');

        $this->addSql('ALTER TABLE import_logs ADD CONSTRAINT FK_F9C9DBACA4353F8C FOREIGN KEY (ls_doc_id) REFERENCES ls_doc (id)');

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE import_logs');

    }
}
