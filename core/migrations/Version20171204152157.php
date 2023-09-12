<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20171204152157 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item CHANGE abbreviated_statement abbreviated_statement VARCHAR(60) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item CHANGE abbreviated_statement abbreviated_statement VARCHAR(50) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
    }
}
