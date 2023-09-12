<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20160811194318 extends AbstractMigration
{
    /**
     * @throws \Doctrine\Migrations\Exception\AbortMigration
     */
    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_D8D02498841CB121 ON ls_item');

        $this->addSql('
ALTER TABLE ls_item
    CHANGE uri uri VARCHAR(300) DEFAULT NULL,
    CHANGE global_id global_id VARCHAR(300) NULL
        ');

        $this->addSql("
UPDATE ls_item
    SET global_id = REPLACE(uri, 'local:', '')
    WHERE global_id IS NULL
        ");

        $this->addSql('
ALTER TABLE ls_item
    CHANGE global_id global_id VARCHAR(300) NOT NULL
        ');

        $this->addSql('DROP INDEX UNIQ_9AE8CF1F2D2FD50E ON ls_doc');
    }

    /**
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9AE8CF1F2D2FD50E ON ls_doc (global_id)');
        $this->addSql('
ALTER TABLE ls_item
    CHANGE uri uri VARCHAR(300) NOT NULL COLLATE utf8_unicode_ci,
    CHANGE global_id global_id CHAR(36) DEFAULT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:uuid)\'
        ');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D8D02498841CB121 ON ls_item (uri)');
    }
}
