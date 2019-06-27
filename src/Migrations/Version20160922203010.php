<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160922203010 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // Migrate the educational_alignment column to use a standard set
        // HS -> '09,10,11,12'
        // http://purl.org/ASN/.../K-12,Pre-K -> 'KG', '01'..'12', 'PK'
        // 1-9 -> '01'..'09'
        // 0 -> null
        // Any , separated values to above sets , separated
        $this->addSql("
UPDATE ls_item
   SET educational_alignment = REPLACE(educational_alignment, 'http://purl.org/ASN/scheme/ASNEducationLevel/', '')
 WHERE educational_alignment LIKE 'http://purl.org/%'
        ");

        $this->addSql("
UPDATE ls_item
   SET educational_alignment = 'PK'
 WHERE educational_alignment = 'Pre-K'
        ");

        $this->addSql("
UPDATE ls_item
   SET educational_alignment = 'KG'
 WHERE educational_alignment = 'K'
        ");

        $this->addSql("
UPDATE ls_item
   SET educational_alignment = 'KG'
 WHERE educational_alignment = '0'
        ");

        $this->addSql("
UPDATE ls_item
   SET educational_alignment = '09,10,11,12'
 WHERE educational_alignment = 'HS'
        ");

        $this->addSql("
UPDATE ls_item
   SET educational_alignment = CONCAT('0', educational_alignment)
 WHERE educational_alignment IN ('1', '2', '3', '4', '5', '6', '7', '8', '9')
   AND educational_alignment IS NOT NULL
        ");

        $this->addSql("
UPDATE ls_item
   SET educational_alignment = '09,10,11'
 WHERE educational_alignment = '10,11,9'
        ");

        $this->addSql("
UPDATE ls_item
   SET educational_alignment = '09,10,11,12'
 WHERE educational_alignment = '9,10,11,12'
        ");

        $this->addSql("
UPDATE ls_item
   SET educational_alignment = 'KG,01,02,03,04,05,06,07,08,09,10'
 WHERE educational_alignment = '1,10,2,3,4,5,6,7,8,9,0'
        ");

        $this->addSql("
UPDATE ls_item
   SET educational_alignment = '03,04,05,06,07,08,09,10,11,12'
 WHERE educational_alignment = '3,4,5,6,7,8,9,10,11,12'
        ");

        $this->addSql("
UPDATE ls_item
   SET educational_alignment = '05,08,09,10,11,12'
 WHERE educational_alignment = '5,8,9,10,11,12'
        ");

        $this->addSql("
UPDATE ls_item
   SET educational_alignment = null
 WHERE educational_alignment = ''
        ");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf(true, 'Cannot reverse educational alignment change');
    }
}
