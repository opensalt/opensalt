<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160715210821 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
ALTER TABLE ls_item
  ADD updated_at DATETIME NOT NULL DEFAULT NOW() ON UPDATE NOW()
        ');
        $this->addSql('
ALTER TABLE ls_doc
  ADD updated_at DATETIME NOT NULL DEFAULT NOW() ON UPDATE NOW()
        ');
        $this->addSql('
ALTER TABLE ls_association
  ADD updated_at DATETIME NOT NULL DEFAULT NOW() ON UPDATE NOW()
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ls_doc DROP updated_at');
        $this->addSql('ALTER TABLE ls_item DROP updated_at');
        $this->addSql('ALTER TABLE ls_association DROP updated_at');
    }
}
