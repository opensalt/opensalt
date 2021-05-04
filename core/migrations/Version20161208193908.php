<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

/**
 * Add field to indicate that the user account is locked
 */
class Version20161208193908 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE salt_user ADD locked TINYINT(1) NOT NULL DEFAULT 0 AFTER password');
    }


    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE salt_user DROP locked');
    }
}
