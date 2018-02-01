<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180103230827 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE IF NOT EXISTS salt_change (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, doc_id INT DEFAULT NULL, changed_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\', description VARCHAR(2048) NOT NULL, changed JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_8427C157A76ED395 (user_id), UNIQUE INDEX UNIQ_8427C157895648BC (doc_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS audit_salt_change (id INT NOT NULL, rev INT NOT NULL, user_id INT DEFAULT NULL, doc_id INT DEFAULT NULL, changed_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime)\', description VARCHAR(2048) DEFAULT NULL, changed JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', revtype VARCHAR(4) NOT NULL, INDEX rev_f4da141f313c5617c27c4f1fb9f2a4a1_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE salt_change ADD CONSTRAINT FK_8427C157A76ED395 FOREIGN KEY (user_id) REFERENCES salt_user (id)');
        $this->addSql('ALTER TABLE salt_change ADD CONSTRAINT FK_8427C157895648BC FOREIGN KEY (doc_id) REFERENCES ls_doc (id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE IF EXISTS salt_change');
        $this->addSql('DROP TABLE IF EXISTS audit_salt_change');
    }
}
