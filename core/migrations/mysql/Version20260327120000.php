<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20260327120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create vector-search embedding storage without stored functions or separate vector tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE ls_item_embedding (
                id INT AUTO_INCREMENT NOT NULL,
                ls_item_id INT NOT NULL,
                text LONGTEXT DEFAULT NULL,
                vector JSON DEFAULT NULL COMMENT '(DC2Type:json)',
                normalized_vector JSON DEFAULT NULL COMMENT '(DC2Type:json)',
                magnitude DOUBLE PRECISION DEFAULT NULL,
                binary_code BINARY(48) DEFAULT NULL,
                is_leaf_node TINYINT(1) DEFAULT 0 NOT NULL,
                source_hierarchy_updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX idx_ls_item_id (ls_item_id),
                INDEX idx_binary_code (binary_code),
                INDEX idx_is_leaf_node (is_leaf_node),
                UNIQUE INDEX uniq_ls_item_embedding_ls_item_id (ls_item_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql('ALTER TABLE ls_item_embedding ADD CONSTRAINT FK_LS_ITEM_EMBEDDING_LS_ITEM FOREIGN KEY (ls_item_id) REFERENCES ls_item (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ls_item_embedding');
    }
}
