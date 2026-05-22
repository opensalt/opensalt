<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

/**
 * Adds 12 binary code segment columns (32 bits each) for multi-index hashing.
 * This enables fast Hamming distance search using the pigeonhole principle.
 *
 * Pigeonhole principle: if total Hamming distance ≤ R, then at least one
 * of the 12 segments must have Hamming distance ≤ floor(R/12).
 */
final class Version20260330120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add binary code segment columns for multi-index hashing search optimization';
    }

    public function up(Schema $schema): void
    {
        // Add 12 STORED generated columns in a single ALTER TABLE to avoid 12 full table rebuilds.
        // Each column extracts 4 bytes (32 bits) from the 48-byte binary_code.
        // Uses CONV(HEX(SUBSTRING(binary_code, pos, 4)), 16, 10) to convert binary segment to unsigned int.
        $columns = [];
        for ($i = 0; $i < 12; $i++) {
            $pos = $i * 4 + 1; // SUBSTRING is 1-indexed
            $columns[] = sprintf(
                'ADD COLUMN bc_seg_%d INT UNSIGNED GENERATED ALWAYS AS (IF(binary_code IS NULL, NULL, CAST(CONV(HEX(SUBSTRING(binary_code,%d,4)),16,10) AS UNSIGNED))) STORED',
                $i, $pos
            );
        }
        $this->addSql('ALTER TABLE ls_item_embedding ' . implode(', ', $columns));

        // Add B-tree indexes for each segment column in a single ALTER TABLE
        $indexes = [];
        for ($i = 0; $i < 12; $i++) {
            $indexes[] = sprintf('ADD INDEX idx_bc_seg_%d (bc_seg_%d)', $i, $i);
        }
        $this->addSql('ALTER TABLE ls_item_embedding ' . implode(', ', $indexes));
    }

    public function down(Schema $schema): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->addSql(sprintf('DROP INDEX idx_bc_seg_%d ON ls_item_embedding', $i));
            $this->addSql(sprintf('ALTER TABLE ls_item_embedding DROP COLUMN bc_seg_%d', $i));
        }
    }
}
