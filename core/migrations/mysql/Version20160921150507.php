<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20160921150507 extends AbstractMigration
{
    /**
     * @throws \Doctrine\Migrations\Exception\AbortMigration
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ls_def_licence (id INT AUTO_INCREMENT NOT NULL, identifier VARCHAR(300) DEFAULT NULL, uri VARCHAR(300) DEFAULT NULL, extra json DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, licence_text LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ls_def_association_grouping (id INT AUTO_INCREMENT NOT NULL, identifier VARCHAR(300) DEFAULT NULL, uri VARCHAR(300) DEFAULT NULL, extra json DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ls_def_subject (id INT AUTO_INCREMENT NOT NULL, identifier VARCHAR(300) DEFAULT NULL, uri VARCHAR(300) DEFAULT NULL, extra json DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, hierarchy_code VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ls_def_item_type (id INT AUTO_INCREMENT NOT NULL, identifier VARCHAR(300) DEFAULT NULL, uri VARCHAR(300) DEFAULT NULL, extra json DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, code VARCHAR(255) NOT NULL, hierarchy_code VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ls_def_grade (id INT AUTO_INCREMENT NOT NULL, identifier VARCHAR(300) DEFAULT NULL, uri VARCHAR(300) DEFAULT NULL, extra json DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, code VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ls_def_concept (id INT AUTO_INCREMENT NOT NULL, identifier VARCHAR(300) DEFAULT NULL, uri VARCHAR(300) DEFAULT NULL, extra json DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, hierarchy_code VARCHAR(255) NOT NULL, keywords LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
    }

    /**
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ls_def_licence');
        $this->addSql('DROP TABLE ls_def_association_grouping');
        $this->addSql('DROP TABLE ls_def_subject');
        $this->addSql('DROP TABLE ls_def_item_type');
        $this->addSql('DROP TABLE ls_def_grade');
        $this->addSql('DROP TABLE ls_def_concept');
    }
}
