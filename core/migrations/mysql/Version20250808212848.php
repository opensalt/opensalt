<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20250808212848 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add api_usage_log table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE api_usage_log (
            id INT AUTO_INCREMENT NOT NULL,
            created_at DATETIME(6) NOT NULL,
            user_identifier VARCHAR(255) NOT NULL,
            api_token_id INT DEFAULT NULL,
            ip VARCHAR(45) DEFAULT NULL,
            method VARCHAR(10) NOT NULL,
            request LONGTEXT NOT NULL,
            endpoint_url VARCHAR(2048) NOT NULL,
            status_code SMALLINT NOT NULL,
            request_full JSON DEFAULT NULL,
            INDEX api_usage_log_created_at_idx (created_at),
            INDEX api_usage_log_user_idx (user_identifier),
            INDEX api_usage_log_endpoint_idx (endpoint_url(255)),
            PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE api_usage_log');
    }

}
