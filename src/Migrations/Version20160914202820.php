<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160914202820 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql("
UPDATE ls_doc
  SET language = 'en'
 WHERE language_uri = 'http://www-01.sil.org/iso639-3/documentation.asp?id=en'
;
        ");
        $this->addSql("
UPDATE ls_item
  SET language = 'en'
 WHERE language_uri IN (
   'http://www-01.sil.org/iso639-3/documentation.asp?id=en',
   'http://id.loc.gov/vocabulary/iso639-2/eng'
 )
;
        ");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // No backwards compatibility
        $this->abortIf(true, 'Cannot revert this chagne');
    }
}
