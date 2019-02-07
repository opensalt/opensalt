<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160720143502 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
ALTER TABLE ls_association
    ADD ls_doc_uri VARCHAR(300) NULL AFTER uri,
    ADD ls_doc_id INT DEFAULT NULL AFTER ls_doc_uri
        ');
        $this->addSql('ALTER TABLE ls_association ADD CONSTRAINT FK_A84022D49388802C FOREIGN KEY (ls_doc_id) REFERENCES ls_doc (id)');
        $this->addSql('CREATE INDEX IDX_A84022D49388802C ON ls_association (ls_doc_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ls_association DROP FOREIGN KEY FK_A84022D49388802C');
        $this->addSql('DROP INDEX IDX_A84022D49388802C ON ls_association');
        $this->addSql('
ALTER TABLE ls_association
    DROP ls_doc_uri,
    DROP ls_doc_id
        ');
    }
}
