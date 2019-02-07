<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160705235303 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ls_item (id INT AUTO_INCREMENT NOT NULL, ls_doc_id INT DEFAULT NULL, uri VARCHAR(300) NOT NULL, ls_doc_uri VARCHAR(300) NOT NULL, human_coding_scheme VARCHAR(50) DEFAULT NULL, global_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', list_enum_in_source VARCHAR(5) DEFAULT NULL, full_statement LONGTEXT NOT NULL, abbreviated_statement VARCHAR(50) DEFAULT NULL, concept_keywords VARCHAR(300) DEFAULT NULL, concept_keywords_uri VARCHAR(300) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, language_uri VARCHAR(300) DEFAULT NULL, educational_alignment VARCHAR(300) DEFAULT NULL, type VARCHAR(60) DEFAULT NULL, licence_uri VARCHAR(300) DEFAULT NULL, source_ls_item_uri VARCHAR(300) DEFAULT NULL, exemplar_resource_uri VARCHAR(300) DEFAULT NULL, changed_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_D8D02498841CB121 (uri), INDEX IDX_D8D024989388802C (ls_doc_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ls_item_ls_item_child (parent_id INT NOT NULL, child_id INT NOT NULL, INDEX IDX_2B1EE8A9727ACA70 (parent_id), INDEX IDX_2B1EE8A9DD62C21B (child_id), PRIMARY KEY(parent_id, child_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ls_doc (id INT AUTO_INCREMENT NOT NULL, uri VARCHAR(300) NOT NULL, global_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', official_uri VARCHAR(300) DEFAULT NULL, creator VARCHAR(300) NOT NULL, publisher VARCHAR(50) DEFAULT NULL, title VARCHAR(120) NOT NULL, version VARCHAR(50) DEFAULT NULL, description VARCHAR(300) DEFAULT NULL, subject VARCHAR(50) DEFAULT NULL, subject_uri VARCHAR(300) DEFAULT NULL, language_uri VARCHAR(300) DEFAULT NULL, adoption_status VARCHAR(50) DEFAULT NULL, status_start DATE DEFAULT NULL, status_end DATE DEFAULT NULL, note LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_9AE8CF1F841CB121 (uri), UNIQUE INDEX UNIQ_9AE8CF1F2D2FD50E (global_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ls_doc_ls_item_top_item (ls_doc_id INT NOT NULL, ls_item_id INT NOT NULL, INDEX IDX_B85A54D99388802C (ls_doc_id), UNIQUE INDEX UNIQ_B85A54D9E27A1FD2 (ls_item_id), PRIMARY KEY(ls_doc_id, ls_item_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ls_association (id INT AUTO_INCREMENT NOT NULL, uri VARCHAR(300) NOT NULL, weight NUMERIC(5, 2) DEFAULT NULL, origin_node_uri VARCHAR(300) NOT NULL, destination_node_uri VARCHAR(300) NOT NULL, type VARCHAR(300) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ls_item ADD CONSTRAINT FK_D8D024989388802C FOREIGN KEY (ls_doc_id) REFERENCES ls_doc (id)');
        $this->addSql('ALTER TABLE ls_item_ls_item_child ADD CONSTRAINT FK_2B1EE8A9727ACA70 FOREIGN KEY (parent_id) REFERENCES ls_item (id)');
        $this->addSql('ALTER TABLE ls_item_ls_item_child ADD CONSTRAINT FK_2B1EE8A9DD62C21B FOREIGN KEY (child_id) REFERENCES ls_item (id)');
        $this->addSql('ALTER TABLE ls_doc_ls_item_top_item ADD CONSTRAINT FK_B85A54D99388802C FOREIGN KEY (ls_doc_id) REFERENCES ls_doc (id)');
        $this->addSql('ALTER TABLE ls_doc_ls_item_top_item ADD CONSTRAINT FK_B85A54D9E27A1FD2 FOREIGN KEY (ls_item_id) REFERENCES ls_item (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ls_item_ls_item_child DROP FOREIGN KEY FK_2B1EE8A9727ACA70');
        $this->addSql('ALTER TABLE ls_item_ls_item_child DROP FOREIGN KEY FK_2B1EE8A9DD62C21B');
        $this->addSql('ALTER TABLE ls_doc_ls_item_top_item DROP FOREIGN KEY FK_B85A54D9E27A1FD2');
        $this->addSql('ALTER TABLE ls_item DROP FOREIGN KEY FK_D8D024989388802C');
        $this->addSql('ALTER TABLE ls_doc_ls_item_top_item DROP FOREIGN KEY FK_B85A54D99388802C');
        $this->addSql('DROP TABLE ls_item');
        $this->addSql('DROP TABLE ls_item_ls_item_child');
        $this->addSql('DROP TABLE ls_doc');
        $this->addSql('DROP TABLE ls_doc_ls_item_top_item');
        $this->addSql('DROP TABLE ls_association');
    }
}
