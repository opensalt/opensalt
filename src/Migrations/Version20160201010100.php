<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20160201010100 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
CREATE TABLE auth_session (
    id            VARBINARY(128)   NOT NULL PRIMARY KEY,
    sess_data     BLOB             NOT NULL,
    sess_time     INTEGER UNSIGNED NOT NULL,
    sess_lifetime MEDIUMINT        NOT NULL
) COLLATE utf8_bin, ENGINE = InnoDB;
        ');

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
DROP TABLE auth_session;
        ');
    }
}
