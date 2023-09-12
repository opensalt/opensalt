<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20191210200643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add subtype table with values';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE salt_association_subtype (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, parent_type VARCHAR(255) NOT NULL, direction INT NOT NULL, description VARCHAR(512) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE salt_association_subtype');
    }
}
