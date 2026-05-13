<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20260330130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add backend-independent indexed flag for vector search metadata';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item_embedding ADD is_indexed TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql(
            <<<'SQL'
                UPDATE ls_item_embedding
                SET is_indexed = 1
                WHERE vector IS NOT NULL
                  AND normalized_vector IS NOT NULL
                  AND magnitude IS NOT NULL
                  AND binary_code IS NOT NULL
            SQL
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item_embedding DROP COLUMN is_indexed');
    }
}
