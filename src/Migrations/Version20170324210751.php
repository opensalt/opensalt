<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170324210751 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ls_def_association_grouping ADD ls_doc_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ls_def_association_grouping ADD CONSTRAINT FK_6A465B629388802C FOREIGN KEY (ls_doc_id) REFERENCES ls_doc (id)');
        $this->addSql('CREATE INDEX IDX_6A465B629388802C ON ls_def_association_grouping (ls_doc_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ls_def_association_grouping DROP FOREIGN KEY FK_6A465B629388802C');
        $this->addSql('DROP INDEX IDX_6A465B629388802C ON ls_def_association_grouping');
        $this->addSql('ALTER TABLE ls_def_association_grouping DROP ls_doc_id');
    }
}
