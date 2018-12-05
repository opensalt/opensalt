<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181205175053 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE additional_field CHANGE name name VARCHAR(255) NOT NULL, CHANGE applies_to applies_to VARCHAR(255) NOT NULL, CHANGE display_name display_name VARCHAR(255) NOT NULL, CHANGE type type VARCHAR(255) NOT NULL, CHANGE type_info type_info VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE additional_field CHANGE name name TINYTEXT NOT NULL COLLATE utf8mb4_unicode_ci, CHANGE applies_to applies_to TINYTEXT NOT NULL COLLATE utf8mb4_unicode_ci, CHANGE display_name display_name TINYTEXT NOT NULL COLLATE utf8mb4_unicode_ci, CHANGE type type TINYTEXT NOT NULL COLLATE utf8mb4_unicode_ci, CHANGE type_info type_info LONGTEXT NOT NULL COLLATE utf8mb4_unicode_ci');
    }
}
