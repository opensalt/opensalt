<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20250514164854 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE flysystem_files (
                id BIGINT AUTO_INCREMENT NOT NULL,
                path VARCHAR(255) NOT NULL,
                type ENUM('dir', 'file'),
                contents LONGBLOB DEFAULT NULL,
                size INT DEFAULT 0 NOT NULL,
                level INT NOT NULL,
                mimetype VARCHAR(127) DEFAULT NULL,
                visibility ENUM('public', 'private'),
                timestamp INT DEFAULT 0 NOT NULL,
                UNIQUE INDEX path_unique (path),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP TABLE flysystem_files
        SQL);
    }

}
