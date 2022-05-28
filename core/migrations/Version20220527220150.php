<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220527220150 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add visible column to mirrored framework';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mirror_framework ADD visible TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mirror_framework DROP visible');
    }
}
