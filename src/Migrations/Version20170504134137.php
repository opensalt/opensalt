<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Cache\Adapter\PdoAdapter;

/**
 * Add cache table
 */
class Version20170504134137 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $cacheAdapter = new PdoAdapter($this->connection);

        $cacheAdapter->createTable();

        $this->addSql('/* no additional SQL required */');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE cache_items');
    }
}
