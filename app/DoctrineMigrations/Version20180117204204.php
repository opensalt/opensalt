<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Fix issue where in some cases time() - sess_time gives the error
 * SQLSTATE[22003]: Numeric value out of range: 1690 BIGINT UNSIGNED value is out of range in '(1516221203 - `cftf`.`auth_session`.`sess_time`)'
 * which can be caused with multiple AJAX calls where a later one sets sess_time to be greater than time() (getting -1)
 */
class Version20180117204204 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
ALTER TABLE auth_session        
  CHANGE sess_time sess_time INTEGER NOT NULL,
  CHANGE sess_lifetime sess_lifetime INTEGER NOT NULL
;
        ');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
ALTER TABLE auth_session        
  CHANGE sess_time sess_time INTEGER UNSIGNED NOT NULL,
  CHANGE sess_lifetime sess_lifetime MEDIUMINT NOT NULL
;
        ');
    }
}
