<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190530232540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Extend password field size';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE salt_user CHANGE password password VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE audit_salt_user CHANGE password password VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE audit_salt_user CHANGE password password VARCHAR(64) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE salt_user CHANGE password password VARCHAR(64) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
    }
}
