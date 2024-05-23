<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20160921185958 extends AbstractMigration
{
    /**
     * @throws \Doctrine\Migrations\Exception\AbortMigration
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_def_licence CHANGE identifier identifier VARCHAR(300) NOT NULL');
        $this->addSql('ALTER TABLE ls_def_association_grouping CHANGE identifier identifier VARCHAR(300) NOT NULL');
        $this->addSql('ALTER TABLE ls_def_subject CHANGE identifier identifier VARCHAR(300) NOT NULL');
        $this->addSql('ALTER TABLE ls_def_item_type CHANGE identifier identifier VARCHAR(300) NOT NULL');
        $this->addSql('ALTER TABLE ls_def_grade CHANGE identifier identifier VARCHAR(300) NOT NULL');
        $this->addSql('ALTER TABLE ls_def_concept CHANGE identifier identifier VARCHAR(300) NOT NULL');
    }

    /**
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_def_association_grouping CHANGE identifier identifier VARCHAR(300) DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE ls_def_concept CHANGE identifier identifier VARCHAR(300) DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE ls_def_grade CHANGE identifier identifier VARCHAR(300) DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE ls_def_item_type CHANGE identifier identifier VARCHAR(300) DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE ls_def_licence CHANGE identifier identifier VARCHAR(300) DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE ls_def_subject CHANGE identifier identifier VARCHAR(300) DEFAULT NULL COLLATE utf8_unicode_ci');
    }
}
