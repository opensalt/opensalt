<?php

namespace DoctrineMigrations;

use App\Entity\Framework\LsDocAttribute;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160914181326 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $isGradeLevels = LsDocAttribute::IS_GRADE_LEVELS;

        $this->addSql("
INSERT IGNORE INTO ls_doc_attribute
  (ls_doc_id, attribute, value)
VALUES
  ((SELECT id FROM ls_doc WHERE title = 'US Grade Levels'), '{$isGradeLevels}', 'yes')
;
        ");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // Ignore on downgrade
    }
}
