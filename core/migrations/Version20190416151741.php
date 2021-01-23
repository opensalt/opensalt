<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190416151741 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Framework Type';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE framework_type (id INT AUTO_INCREMENT NOT NULL, framework_type VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ls_doc ADD frameworktype_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ls_doc ADD CONSTRAINT FK_9AE8CF1F3C4C17B2 FOREIGN KEY (frameworktype_id) REFERENCES framework_type (id)');
        $this->addSql('CREATE INDEX IDX_9AE8CF1F3C4C17B2 ON ls_doc (frameworktype_id)');
        $this->addSql('ALTER TABLE audit_ls_doc ADD frameworktype_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ls_doc DROP FOREIGN KEY FK_9AE8CF1F3C4C17B2');
        $this->addSql('DROP TABLE framework_type');
        $this->addSql('ALTER TABLE audit_ls_doc DROP frameworktype_id');
        $this->addSql('DROP INDEX IDX_9AE8CF1F3C4C17B2 ON ls_doc');
        $this->addSql('ALTER TABLE ls_doc DROP frameworktype_id');
    }
}
