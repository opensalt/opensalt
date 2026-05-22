<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20260329120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create vector search state table for incremental embedding generation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE vector_search_state (
                state_key VARCHAR(100) NOT NULL,
                last_ls_item_id INT DEFAULT NULL,
                updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY(state_key)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE vector_search_state');
    }
}
