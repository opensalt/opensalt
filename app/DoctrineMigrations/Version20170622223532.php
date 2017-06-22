<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170622223532 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            CREATE TABLE comments (
                id                INT             NOT NULL AUTO_INCREMENT PRIMARY KEY,
                ancestors         VARCHAR(255)    NOT NULL,
                body              TEXT            NOT NULL,
                depth             INT             NOT NULL,
                state             INT             NOT NULL,
                previous_state    INT             NOT NULL DEFAULT 0,
                thread_id         VARCHAR(255)    NOT NULL,
                created_at        TIMESTAMP       DEFAULT "0000-00-00 00:00:00"
            ) COLLATE utf8_bin, ENGINE = InnoDB;
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE comments;');
    }
}
