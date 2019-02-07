<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170925222726 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE salt_comment_upvote DROP FOREIGN KEY FK_4DB1D19CF8697D13');
        $this->addSql('ALTER TABLE salt_comment_upvote ADD CONSTRAINT FK_4DB1D19CF8697D13 FOREIGN KEY (comment_id) REFERENCES salt_comment (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE salt_comment_upvote DROP FOREIGN KEY FK_4DB1D19CF8697D13');
        $this->addSql('ALTER TABLE salt_comment_upvote ADD CONSTRAINT FK_4DB1D19CF8697D13 FOREIGN KEY (comment_id) REFERENCES salt_comment (id)');
    }
}
