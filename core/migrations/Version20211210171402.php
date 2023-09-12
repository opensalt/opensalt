<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211210171402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change DC2Type to json instead of json_array';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE salt_user MODIFY COLUMN roles longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '(DC2Type:json)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE salt_user MODIFY COLUMN roles longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '(DC2Type:json_array)'");
    }
}
