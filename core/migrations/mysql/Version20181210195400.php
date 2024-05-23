<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20181210195400 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
CREATE TABLE salt_additional_field (
  id INT AUTO_INCREMENT NOT NULL,
  name VARCHAR(255) NOT NULL,
  applies_to VARCHAR(255) NOT NULL,
  display_name VARCHAR(255) NOT NULL,
  type VARCHAR(255) NOT NULL,
  type_info JSON DEFAULT NULL COMMENT \'(DC2Type:json)\',
  PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE salt_additional_field');
    }
}
