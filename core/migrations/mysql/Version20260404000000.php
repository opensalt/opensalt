<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20260404000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop ls_item_embedding table; vector data now lives in Qdrant only';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS ls_item_embedding');
    }

    public function down(Schema $schema): void
    {
        throw new \RuntimeException('The ls_item_embedding table has been permanently removed. Restore from a backup if needed.');
    }
}
