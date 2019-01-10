<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170823192751 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE salt_comment (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, user_id INT NOT NULL, content VARCHAR(255) NOT NULL, item VARCHAR(255) NOT NULL, fullname VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX IDX_5AD1C6CC727ACA70 (parent_id), INDEX IDX_5AD1C6CCA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE salt_comment_upvote (id INT AUTO_INCREMENT NOT NULL, comment_id INT NOT NULL, user_id INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX IDX_4DB1D19CF8697D13 (comment_id), INDEX IDX_4DB1D19CA76ED395 (user_id), UNIQUE INDEX comment_user (comment_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE salt_comment ADD CONSTRAINT FK_5AD1C6CC727ACA70 FOREIGN KEY (parent_id) REFERENCES salt_comment (id)');
        $this->addSql('ALTER TABLE salt_comment ADD CONSTRAINT FK_5AD1C6CCA76ED395 FOREIGN KEY (user_id) REFERENCES salt_user (id)');
        $this->addSql('ALTER TABLE salt_comment_upvote ADD CONSTRAINT FK_4DB1D19CF8697D13 FOREIGN KEY (comment_id) REFERENCES salt_comment (id)');
        $this->addSql('ALTER TABLE salt_comment_upvote ADD CONSTRAINT FK_4DB1D19CA76ED395 FOREIGN KEY (user_id) REFERENCES salt_user (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE salt_comment DROP FOREIGN KEY FK_5AD1C6CC727ACA70');
        $this->addSql('ALTER TABLE salt_comment_upvote DROP FOREIGN KEY FK_4DB1D19CF8697D13');
        $this->addSql('DROP TABLE salt_comment');
        $this->addSql('DROP TABLE salt_comment_upvote');
    }
}
