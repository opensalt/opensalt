<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161205231342 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE salt_user_doc_acl (user_id INT NOT NULL, doc_id INT NOT NULL, access SMALLINT NOT NULL, INDEX IDX_85C83E4AA76ED395 (user_id), INDEX IDX_85C83E4A895648BC (doc_id), PRIMARY KEY(user_id, doc_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE salt_user_doc_acl ADD CONSTRAINT FK_85C83E4AA76ED395 FOREIGN KEY (user_id) REFERENCES salt_user (id)');
        $this->addSql('ALTER TABLE salt_user_doc_acl ADD CONSTRAINT FK_85C83E4A895648BC FOREIGN KEY (doc_id) REFERENCES ls_doc (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE salt_user_doc_acl');
    }
}
