<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230912000014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow publisher and human_coding_scheme to be longer';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_doc CHANGE publisher publisher VARCHAR(300) DEFAULT NULL');
        $this->addSql('ALTER TABLE ls_item CHANGE human_coding_scheme human_coding_scheme VARCHAR(80) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ls_doc CHANGE publisher publisher VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE ls_item CHANGE human_coding_scheme human_coding_scheme VARCHAR(50) DEFAULT NULL');
    }
}
