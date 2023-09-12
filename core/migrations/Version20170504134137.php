<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

/**
 * Add cache table.
 */
class Version20170504134137 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
CREATE TABLE cache_items (
  item_id varbinary(255) NOT NULL,
  item_data mediumblob NOT NULL,
  item_lifetime int(10) unsigned DEFAULT NULL,
  item_time int(10) unsigned NOT NULL,
  PRIMARY KEY (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE cache_items');
    }
}
