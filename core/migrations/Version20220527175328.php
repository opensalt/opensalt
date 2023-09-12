<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220527175328 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update server_id to be required';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mirror_framework CHANGE server_id server_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mirror_framework CHANGE server_id server_id INT DEFAULT NULL');
    }
}
