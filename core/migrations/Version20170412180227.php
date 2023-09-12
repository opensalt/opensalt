<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20170412180227 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
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


    public function down(Schema $schema): void
    {
        // No need to revert
    }
}
