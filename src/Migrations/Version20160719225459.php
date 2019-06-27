<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160719225459 extends AbstractMigration
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
    ADD origin_lsdoc_id INT DEFAULT NULL AFTER origin_node_uri,
    ADD origin_lsitem_id INT DEFAULT NULL AFTER origin_lsdoc_id,
    ADD destination_lsdoc_id INT DEFAULT NULL AFTER destination_node_uri,
    ADD destination_lsitem_id INT DEFAULT NULL AFTER destination_lsdoc_id,
    CHANGE type type VARCHAR(50) NOT NULL COLLATE utf8_unicode_ci
        ');
        $this->addSql('ALTER TABLE ls_association ADD CONSTRAINT FK_A84022D434C423C4 FOREIGN KEY (origin_lsdoc_id) REFERENCES ls_doc (id)');
        $this->addSql('ALTER TABLE ls_association ADD CONSTRAINT FK_A84022D44C0C393B FOREIGN KEY (origin_lsitem_id) REFERENCES ls_item (id)');
        $this->addSql('ALTER TABLE ls_association ADD CONSTRAINT FK_A84022D459C28905 FOREIGN KEY (destination_lsdoc_id) REFERENCES ls_doc (id)');
        $this->addSql('ALTER TABLE ls_association ADD CONSTRAINT FK_A84022D4A002CDB7 FOREIGN KEY (destination_lsitem_id) REFERENCES ls_item (id)');
        $this->addSql('CREATE INDEX IDX_A84022D434C423C4 ON ls_association (origin_lsdoc_id)');
        $this->addSql('CREATE INDEX IDX_A84022D44C0C393B ON ls_association (origin_lsitem_id)');
        $this->addSql('CREATE INDEX IDX_A84022D459C28905 ON ls_association (destination_lsdoc_id)');
        $this->addSql('CREATE INDEX IDX_A84022D4A002CDB7 ON ls_association (destination_lsitem_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ls_association DROP FOREIGN KEY FK_A84022D434C423C4');
        $this->addSql('ALTER TABLE ls_association DROP FOREIGN KEY FK_A84022D44C0C393B');
        $this->addSql('ALTER TABLE ls_association DROP FOREIGN KEY FK_A84022D459C28905');
        $this->addSql('ALTER TABLE ls_association DROP FOREIGN KEY FK_A84022D4A002CDB7');
        $this->addSql('DROP INDEX IDX_A84022D434C423C4 ON ls_association');
        $this->addSql('DROP INDEX IDX_A84022D44C0C393B ON ls_association');
        $this->addSql('DROP INDEX IDX_A84022D459C28905 ON ls_association');
        $this->addSql('DROP INDEX IDX_A84022D4A002CDB7 ON ls_association');
        $this->addSql('
ALTER TABLE ls_association
    DROP origin_lsdoc_id,
    DROP origin_lsitem_id,
    DROP destination_lsdoc_id,
    DROP destination_lsitem_id,
    CHANGE type type VARCHAR(300) NOT NULL COLLATE utf8_unicode_ci
        ');
    }
}
