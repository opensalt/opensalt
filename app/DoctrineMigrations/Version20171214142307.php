<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171214142307 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE salt_comment ADD document_id INT DEFAULT NULL, ADD item_id INT DEFAULT NULL');
        $this->addSql('UPDATE salt_comment SET document_id = SUBSTRING_INDEX(item, ":", -1) WHERE SUBSTRING_INDEX(item, ":", 1) = "document"');
        $this->addSql('UPDATE salt_comment SET item_id = SUBSTRING_INDEX(item, ":", -1) WHERE SUBSTRING_INDEX(item, ":", 1) = "item"');
        $this->addSql('DELETE FROM salt_comment WHERE document_id NOT IN (SELECT ls_doc.id FROM ls_doc)');
        $this->addSql('DELETE FROM salt_comment WHERE item_id NOT IN (SELECT ls_item.id FROM ls_item)');
        $this->addSql('ALTER TABLE salt_comment ADD CONSTRAINT FK_5AD1C6CCC33F7837 FOREIGN KEY (document_id) REFERENCES ls_doc (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE salt_comment ADD CONSTRAINT FK_5AD1C6CC126F525E FOREIGN KEY (item_id) REFERENCES ls_item (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_5AD1C6CCC33F7837 ON salt_comment (document_id)');
        $this->addSql('CREATE INDEX IDX_5AD1C6CC126F525E ON salt_comment (item_id)');
        $this->addSql('ALTER TABLE salt_comment DROP item');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE salt_comment DROP FOREIGN KEY FK_5AD1C6CCC33F7837');
        $this->addSql('ALTER TABLE salt_comment DROP FOREIGN KEY FK_5AD1C6CC126F525E');
        $this->addSql('DROP INDEX IDX_5AD1C6CCC33F7837 ON salt_comment');
        $this->addSql('DROP INDEX IDX_5AD1C6CC126F525E ON salt_comment');
        $this->addSql('ALTER TABLE salt_comment ADD item VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('UPDATE salt_comment SET item = CONCAT("document:", document_id) WHERE document_id IS NOT NULL');
        $this->addSql('UPDATE salt_comment SET item = CONCAT("item:", item_id) WHERE item_id IS NOT NULL');
        $this->addSql('ALTER TABLE salt_comment MODIFY item VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE salt_comment DROP document_id, DROP item_id');
    }
}
