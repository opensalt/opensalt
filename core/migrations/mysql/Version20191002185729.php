<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20191002185729 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop unused concept_keywords_uri column.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item DROP concept_keywords_uri');
        $this->addSql('ALTER TABLE audit_ls_item DROP concept_keywords_uri');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_ls_item ADD concept_keywords_uri VARCHAR(300) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE ls_item ADD concept_keywords_uri VARCHAR(300) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
    }
}
