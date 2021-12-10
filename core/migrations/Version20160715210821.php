<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20160715210821 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
ALTER TABLE ls_item
  ADD updated_at DATETIME NOT NULL DEFAULT NOW() ON UPDATE NOW()
        ');
        $this->addSql('
ALTER TABLE ls_doc
  ADD updated_at DATETIME NOT NULL DEFAULT NOW() ON UPDATE NOW()
        ');
        $this->addSql('
ALTER TABLE ls_association
  ADD updated_at DATETIME NOT NULL DEFAULT NOW() ON UPDATE NOW()
        ');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_doc DROP updated_at');
        $this->addSql('ALTER TABLE ls_item DROP updated_at');
        $this->addSql('ALTER TABLE ls_association DROP updated_at');
    }
}
