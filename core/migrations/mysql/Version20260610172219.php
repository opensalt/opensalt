<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260610172219 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update ls_item to remove type and make ls_doc_id not null';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('ls_item');
        if ($table->hasColumn('type')) {
            $this->addSql('ALTER TABLE ls_item DROP `type`');
        }
        if ($table->getColumn('ls_doc_id')->getNotnull()) {
            return;
        }
        $this->addSql('ALTER TABLE ls_item DROP FOREIGN KEY FK_D8D024989388802C');
        $this->addSql('ALTER TABLE ls_item CHANGE ls_doc_id ls_doc_id INT NOT NULL');
        $this->addSql('ALTER TABLE ls_item ADD CONSTRAINT FK_D8D024989388802C FOREIGN KEY (ls_doc_id) REFERENCES ls_doc (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_item DROP FOREIGN KEY FK_D8D024989388802C');
        $this->addSql('ALTER TABLE ls_item ADD `type` VARCHAR(60) DEFAULT NULL, CHANGE ls_doc_id ls_doc_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ls_item ADD CONSTRAINT FK_D8D024989388802C FOREIGN KEY (ls_doc_id) REFERENCES ls_doc (id)');
    }
}
