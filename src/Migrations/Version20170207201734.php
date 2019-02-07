<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170207201734 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ls_association ADD assoc_group_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ls_association ADD CONSTRAINT FK_A84022D45BE201D2 FOREIGN KEY (assoc_group_id) REFERENCES ls_def_association_grouping (id)');
        $this->addSql('CREATE INDEX IDX_A84022D45BE201D2 ON ls_association (assoc_group_id)');
        $this->addSql('ALTER TABLE ls_def_association_grouping DROP name');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ls_association DROP FOREIGN KEY FK_A84022D45BE201D2');
        $this->addSql('DROP INDEX IDX_A84022D45BE201D2 ON ls_association');
        $this->addSql('ALTER TABLE ls_association DROP assoc_group_id');
        $this->addSql('ALTER TABLE ls_def_association_grouping ADD name VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci');
    }
}
