<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20260327213000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add source hierarchy update timestamp to vector-search embeddings';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('ls_item_embedding')) {
            return;
        }

        $table = $schema->getTable('ls_item_embedding');
        if ($table->hasColumn('source_hierarchy_updated_at')) {
            return;
        }

        $this->addSql(
            "ALTER TABLE ls_item_embedding ADD source_hierarchy_updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'"
        );
        $this->addSql('UPDATE ls_item_embedding SET source_hierarchy_updated_at = updated_at WHERE source_hierarchy_updated_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('ls_item_embedding')) {
            return;
        }

        $table = $schema->getTable('ls_item_embedding');
        if (!$table->hasColumn('source_hierarchy_updated_at')) {
            return;
        }

        $this->addSql('ALTER TABLE ls_item_embedding DROP source_hierarchy_updated_at');
    }
}
