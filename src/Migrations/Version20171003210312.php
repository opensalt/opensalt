<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171003210312 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE salt_comment CHANGE content content LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE salt_comment DROP FOREIGN KEY FK_5AD1C6CC727ACA70');
        $this->addSql('ALTER TABLE salt_comment ADD CONSTRAINT FK_5AD1C6CC727ACA70 FOREIGN KEY (parent_id) REFERENCES salt_comment (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE salt_comment DROP FOREIGN KEY FK_5AD1C6CC727ACA70');
        $this->addSql('ALTER TABLE salt_comment ADD CONSTRAINT FK_5AD1C6CC727ACA70 FOREIGN KEY (parent_id) REFERENCES salt_comment (id)');
        $this->addSql('ALTER TABLE salt_comment CHANGE content content VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci');
    }
}
