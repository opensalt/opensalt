<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170512001154 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE rubric (id INT AUTO_INCREMENT NOT NULL, identifier VARCHAR(300) NOT NULL, uri VARCHAR(300) DEFAULT NULL, extra json DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, title TEXT DEFAULT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rubric_criterion_level (id INT AUTO_INCREMENT NOT NULL, criterion_id INT DEFAULT NULL, identifier VARCHAR(300) NOT NULL, uri VARCHAR(300) DEFAULT NULL, extra json DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, description TEXT DEFAULT NULL, quality TEXT DEFAULT NULL, score DOUBLE PRECISION DEFAULT NULL, feedback TEXT DEFAULT NULL, position INT DEFAULT NULL, INDEX IDX_FEC0421697766307 (criterion_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rubric_criterion (id INT AUTO_INCREMENT NOT NULL, ls_item_id INT DEFAULT NULL, rubric_id INT DEFAULT NULL, identifier VARCHAR(300) NOT NULL, uri VARCHAR(300) DEFAULT NULL, extra json DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, category VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, weight DOUBLE PRECISION DEFAULT NULL, position INT DEFAULT NULL, INDEX IDX_98E476F9E27A1FD2 (ls_item_id), INDEX IDX_98E476F9A29EC0FC (rubric_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE rubric_criterion_level ADD CONSTRAINT FK_FEC0421697766307 FOREIGN KEY (criterion_id) REFERENCES rubric_criterion (id)');
        $this->addSql('ALTER TABLE rubric_criterion ADD CONSTRAINT FK_98E476F9E27A1FD2 FOREIGN KEY (ls_item_id) REFERENCES ls_item (id)');
        $this->addSql('ALTER TABLE rubric_criterion ADD CONSTRAINT FK_98E476F9A29EC0FC FOREIGN KEY (rubric_id) REFERENCES rubric (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE rubric_criterion DROP FOREIGN KEY FK_98E476F9A29EC0FC');
        $this->addSql('ALTER TABLE rubric_criterion_level DROP FOREIGN KEY FK_FEC0421697766307');
        $this->addSql('DROP TABLE rubric');
        $this->addSql('DROP TABLE rubric_criterion_level');
        $this->addSql('DROP TABLE rubric_criterion');
    }
}
