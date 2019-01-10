<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160902204929 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE ls_doc_ls_item_top_item');
        $this->addSql('DROP TABLE ls_item_ls_item_child');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ls_doc_ls_item_top_item (ls_doc_id INT NOT NULL, ls_item_id INT NOT NULL, UNIQUE INDEX UNIQ_B85A54D9E27A1FD2 (ls_item_id), INDEX IDX_B85A54D99388802C (ls_doc_id), PRIMARY KEY(ls_doc_id, ls_item_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ls_item_ls_item_child (parent_id INT NOT NULL, child_id INT NOT NULL, INDEX IDX_2B1EE8A9727ACA70 (parent_id), INDEX IDX_2B1EE8A9DD62C21B (child_id), PRIMARY KEY(parent_id, child_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ls_doc_ls_item_top_item ADD CONSTRAINT FK_B85A54D99388802C FOREIGN KEY (ls_doc_id) REFERENCES ls_doc (id)');
        $this->addSql('ALTER TABLE ls_doc_ls_item_top_item ADD CONSTRAINT FK_B85A54D9E27A1FD2 FOREIGN KEY (ls_item_id) REFERENCES ls_item (id)');
        $this->addSql('ALTER TABLE ls_item_ls_item_child ADD CONSTRAINT FK_2B1EE8A9727ACA70 FOREIGN KEY (parent_id) REFERENCES ls_item (id)');
        $this->addSql('ALTER TABLE ls_item_ls_item_child ADD CONSTRAINT FK_2B1EE8A9DD62C21B FOREIGN KEY (child_id) REFERENCES ls_item (id)');
    }
}
