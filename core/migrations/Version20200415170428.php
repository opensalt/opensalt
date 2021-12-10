<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20200415170428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow concept keywords field to be null';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_def_concept CHANGE keywords keywords LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_def_concept CHANGE keywords keywords LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
