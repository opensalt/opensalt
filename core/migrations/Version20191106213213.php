<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20191106213213 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow code to be null for itemtype';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_def_item_type CHANGE code code VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_def_item_type CHANGE code code VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
