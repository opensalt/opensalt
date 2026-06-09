<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20250425190543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate item data from extra to extensions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE ls_item
            SET extra = JSON_INSERT(
              JSON_REMOVE(extra, '$.extendedItem.type'),
              '$.extendedItem."salt:type"',
              JSON_EXTRACT(extra, '$.extendedItem.type')
            )
            WHERE JSON_EXTRACT(extra, "$.extendedItem.type") IS NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE ls_item
            SET ext = JSON_EXTRACT(extra, '$.extendedItem'), extra = JSON_REMOVE(extra, '$.extendedItem')
            WHERE JSON_EXTRACT(extra, '$.extendedItem') IS NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE ls_item li
               SET extra = NULL
             WHERE (extra = JSON_ARRAY() OR extra = JSON_OBJECT())
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE ls_item
            SET extra = JSON_INSERT(extra, '$.extendedItem', ext), ext = NULL
            WHERE ext IS NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE ls_item
            SET extra = JSON_INSERT(
              JSON_REMOVE(extra, '$.extendedItem."salt:type"'),
              '$.extendedItem.type',
              JSON_EXTRACT(extra, '$.extendedItem."salt:type"')
            )
            WHERE JSON_EXTRACT(extra, '$.extendedItem."salt:type"') IS NOT NULL
        SQL);
    }
}
