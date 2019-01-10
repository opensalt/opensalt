<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170412180227 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
UPDATE ls_def_association_grouping ag
   SET ag.ls_doc_id = (SELECT DISTINCT a.ls_doc_id
                         FROM ls_association a
                        WHERE a.assoc_group_id = ag.id)
 WHERE ag.ls_doc_id IS NULL
;
        ');

        $this->addSql('
DELETE FROM ls_def_association_grouping
 WHERE ls_doc_id IS NULL
;
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // No need to revert
    }
}
