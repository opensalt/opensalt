<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160927144534 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ls_item ADD item_type_id INT DEFAULT NULL AFTER `type`');
        $this->addSql('ALTER TABLE ls_item ADD CONSTRAINT FK_D8D02498CE11AAC7 FOREIGN KEY (item_type_id) REFERENCES ls_def_item_type (id)');
        $this->addSql('CREATE INDEX IDX_D8D02498CE11AAC7 ON ls_item (item_type_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ls_item DROP FOREIGN KEY FK_D8D02498CE11AAC7');
        $this->addSql('DROP INDEX IDX_D8D02498CE11AAC7 ON ls_item');
        $this->addSql('ALTER TABLE ls_item DROP item_type_id');
    }
}
