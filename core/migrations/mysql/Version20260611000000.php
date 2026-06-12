<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create crosswalk_job table for tracking async crosswalk operations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE crosswalk_job (
                id BINARY(16) NOT NULL PRIMARY KEY,
                status VARCHAR(20) NOT NULL DEFAULT \'queued\',
                origin_framework_id INT NOT NULL,
                destination_framework_id INT NOT NULL,
                crosswalk_framework_id INT NOT NULL,
                threshold DOUBLE PRECISION NOT NULL DEFAULT 0.75,
                exact_match_threshold DOUBLE PRECISION NOT NULL DEFAULT 0.90,
                total_items INT NOT NULL DEFAULT 0,
                processed_items INT NOT NULL DEFAULT 0,
                matched_items INT NOT NULL DEFAULT 0,
                exact_match_items INT NOT NULL DEFAULT 0,
                related_items INT NOT NULL DEFAULT 0,
                skipped_no_embedding INT NOT NULL DEFAULT 0,
                skipped_below_threshold INT NOT NULL DEFAULT 0,
                failed_items INT NOT NULL DEFAULT 0,
                queued_at DATETIME NOT NULL,
                started_at DATETIME DEFAULT NULL,
                completed_at DATETIME DEFAULT NULL,
                error_message TEXT DEFAULT NULL,
                INDEX idx_crosswalk_job_status (status),
                INDEX idx_crosswalk_job_crosswalk_framework (crosswalk_framework_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE crosswalk_job');
    }
}
