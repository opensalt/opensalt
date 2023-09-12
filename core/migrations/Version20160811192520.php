<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20160811192520 extends AbstractMigration
{
    /**
     * @throws \Doctrine\Migrations\Exception\AbortMigration
     */
    public function up(Schema $schema): void
    {
        $this->addSql('
ALTER TABLE ls_doc
    CHANGE uri uri VARCHAR(300) DEFAULT NULL,
    CHANGE global_id global_id VARCHAR(300) NULL
        ');

        $this->addSql('
UPDATE ls_doc
    SET global_id = uri
    WHERE global_id IS NULL
        ');

        $this->addSql('
ALTER TABLE ls_doc
    CHANGE uri uri VARCHAR(300) DEFAULT NULL,
    CHANGE global_id global_id VARCHAR(300) NOT NULL
        ');
    }

    /**
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->addSql('
ALTER TABLE ls_doc
    CHANGE uri uri VARCHAR(300) NOT NULL COLLATE utf8_unicode_ci,
    CHANGE global_id global_id CHAR(36) DEFAULT NULL COLLATE utf8_unicode_ci COMMENT \'(DC2Type:uuid)\'
        ');
    }
}
