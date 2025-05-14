<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250506231237 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index for JSON "salt:atId" value';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE ls_item
              ADD INDEX item_atid ((
                 CAST(ext->>'$."salt:atId"' AS CHAR(255)) COLLATE utf8mb4_bin
              ))
        SQL);
    }

    public function down(Schema $schema): void
    {

    }

    public function isTransactional(): bool
    {
        return false;
    }
}
