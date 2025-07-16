<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250716202048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow document to be longer (Georgia has a few long titles)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_doc CHANGE title title VARCHAR(300) NOT NULL, CHANGE updated_at updated_at DATETIME(6) NOT NULL, CHANGE changed_at changed_at DATETIME(6) NOT NULL');
        $this->addSql('ALTER TABLE mirror_framework CHANGE title title VARCHAR(300) DEFAULT NULL, CHANGE updated_at updated_at DATETIME(6) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_doc CHANGE title title VARCHAR(120) NOT NULL, CHANGE changed_at changed_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE mirror_framework CHANGE title title VARCHAR(255) DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
