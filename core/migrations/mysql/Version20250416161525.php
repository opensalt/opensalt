<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250416161525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add targetType to association links';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_association ADD origin_node_target_type VARCHAR(300) DEFAULT NULL AFTER origin_node_uri, ADD destination_node_target_type VARCHAR(300) DEFAULT NULL AFTER destination_node_uri
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_association DROP origin_node_target_type, DROP destination_node_target_type
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
