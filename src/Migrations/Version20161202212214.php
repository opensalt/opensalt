<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161202212214 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE salt_org (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_762FB035E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE salt_user ADD org_id INT NULL AFTER id');

        $this->addSql("INSERT IGNORE INTO salt_org (name) VALUES ('Unknown');");
        $this->addSql("UPDATE salt_user SET org_id = (SELECT id FROM salt_org WHERE name = 'Unknown');");

        $this->addSql('ALTER TABLE salt_user CHANGE org_id org_id INT NOT NULL');
        $this->addSql('ALTER TABLE salt_user ADD CONSTRAINT FK_F9577392F4837C1B FOREIGN KEY (org_id) REFERENCES salt_org (id)');
        $this->addSql('CREATE INDEX IDX_F9577392F4837C1B ON salt_user (org_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE salt_user DROP FOREIGN KEY FK_F9577392F4837C1B');
        $this->addSql('DROP TABLE salt_org');
        $this->addSql('DROP INDEX IDX_F9577392F4837C1B ON salt_user');
        $this->addSql('ALTER TABLE salt_user DROP org_id');
    }
}
