<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616212307 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update crosswalk_job table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE crosswalk_job CHANGE id id CHAR(36) NOT NULL, CHANGE threshold threshold DOUBLE PRECISION DEFAULT 0.75 NOT NULL, CHANGE exact_match_threshold exact_match_threshold DOUBLE PRECISION DEFAULT 0.9 NOT NULL, CHANGE error_message error_message LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE crosswalk_job CHANGE id id BINARY(16) NOT NULL, CHANGE threshold threshold DOUBLE PRECISION DEFAULT \'0.75\' NOT NULL, CHANGE exact_match_threshold exact_match_threshold DOUBLE PRECISION DEFAULT \'0.9\' NOT NULL, CHANGE error_message error_message TEXT DEFAULT NULL');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
