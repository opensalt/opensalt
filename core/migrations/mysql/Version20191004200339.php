<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20191004200339 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add text field for item_type, remove licence_uri';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item ADD item_type_text VARCHAR(255) DEFAULT NULL AFTER item_type_id, DROP licence_uri');
        $this->addSql('ALTER TABLE audit_ls_item ADD item_type_text VARCHAR(255) DEFAULT NULL AFTER item_type_id, DROP licence_uri');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_ls_item ADD licence_uri VARCHAR(300) DEFAULT NULL COLLATE utf8mb4_unicode_ci, DROP item_type_text');
        $this->addSql('ALTER TABLE ls_item ADD licence_uri VARCHAR(300) DEFAULT NULL COLLATE utf8mb4_unicode_ci, DROP item_type_text');
    }
}
