<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20191007212215 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removed unused group name and uri fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_association DROP group_name, DROP group_uri');
        $this->addSql('ALTER TABLE audit_ls_association DROP group_name, DROP group_uri');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_ls_association ADD group_name VARCHAR(50) DEFAULT NULL COLLATE utf8mb4_unicode_ci, ADD group_uri VARCHAR(300) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE ls_association ADD group_name VARCHAR(50) DEFAULT NULL COLLATE utf8mb4_unicode_ci, ADD group_uri VARCHAR(300) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
    }
}
