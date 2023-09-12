<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20191106094004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow alternativeLabel to be large';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item CHANGE alternative_label alternative_label LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE audit_ls_item CHANGE alternative_label alternative_label LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_ls_item CHANGE alternative_label alternative_label VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE ls_item CHANGE alternative_label alternative_label VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
