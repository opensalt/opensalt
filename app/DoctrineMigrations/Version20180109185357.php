<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180109185357 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE salt_object_lock (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, doc_id INT DEFAULT NULL, expiry DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\', obj_type VARCHAR(255) NOT NULL, obj_id VARCHAR(255) NOT NULL, INDEX IDX_247092FA76ED395 (user_id), INDEX IDX_247092F895648BC (doc_id), INDEX expiry_idx (expiry), UNIQUE INDEX lock_obj_idx (obj_type, obj_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE salt_object_lock ADD CONSTRAINT FK_247092FA76ED395 FOREIGN KEY (user_id) REFERENCES salt_user (id)');
        $this->addSql('ALTER TABLE salt_object_lock ADD CONSTRAINT FK_247092F895648BC FOREIGN KEY (doc_id) REFERENCES ls_doc (id)');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE salt_object_lock');
    }
}
