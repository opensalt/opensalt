<?php

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161205205017 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ls_doc ADD org_id INT DEFAULT NULL AFTER id, ADD user_id INT DEFAULT NULL AFTER org_id');
        $this->addSql('ALTER TABLE ls_doc ADD CONSTRAINT FK_9AE8CF1FF4837C1B FOREIGN KEY (org_id) REFERENCES salt_org (id)');
        $this->addSql('ALTER TABLE ls_doc ADD CONSTRAINT FK_9AE8CF1FA76ED395 FOREIGN KEY (user_id) REFERENCES salt_user (id)');
        $this->addSql('CREATE INDEX IDX_9AE8CF1FF4837C1B ON ls_doc (org_id)');
        $this->addSql('CREATE INDEX IDX_9AE8CF1FA76ED395 ON ls_doc (user_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ls_doc DROP FOREIGN KEY FK_9AE8CF1FF4837C1B');
        $this->addSql('ALTER TABLE ls_doc DROP FOREIGN KEY FK_9AE8CF1FA76ED395');
        $this->addSql('DROP INDEX IDX_9AE8CF1FF4837C1B ON ls_doc');
        $this->addSql('DROP INDEX IDX_9AE8CF1FA76ED395 ON ls_doc');
        $this->addSql('ALTER TABLE ls_doc DROP org_id, DROP user_id');
    }
}
