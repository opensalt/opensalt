<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20260720124500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes on ls_item.changed_at and ls_doc.changed_at for MCP recent-item/document ordering';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;

        $hasItemIdx = $conn->fetchOne(
            "SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ls_item' AND COLUMN_NAME = 'changed_at' LIMIT 1"
        );
        if (false === $hasItemIdx) {
            $this->addSql('CREATE INDEX ls_item_changed_at_idx ON ls_item (changed_at)');
        }

        $hasDocIdx = $conn->fetchOne(
            "SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ls_doc' AND COLUMN_NAME = 'changed_at' LIMIT 1"
        );
        if (false === $hasDocIdx) {
            $this->addSql('CREATE INDEX ls_doc_changed_at_idx ON ls_doc (changed_at)');
        }
    }

    public function down(Schema $schema): void
    {
        $conn = $this->connection;

        $hasItemIdx = $conn->fetchOne(
            "SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ls_item' AND INDEX_NAME = 'ls_item_changed_at_idx' LIMIT 1"
        );
        if (false !== $hasItemIdx) {
            $this->addSql('DROP INDEX ls_item_changed_at_idx ON ls_item');
        }

        $hasDocIdx = $conn->fetchOne(
            "SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ls_doc' AND INDEX_NAME = 'ls_doc_changed_at_idx' LIMIT 1"
        );
        if (false !== $hasDocIdx) {
            $this->addSql('DROP INDEX ls_doc_changed_at_idx ON ls_doc');
        }
    }
}
