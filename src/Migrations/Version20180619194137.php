<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180619194137 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE task_model ADD stem_requirements VARCHAR(255) NOT NULL, ADD key_requirements VARCHAR(255) NOT NULL, ADD distractor_requirements VARCHAR(255) NOT NULL, ADD common_authoring_problems_requirements VARCHAR(255) NOT NULL, DROP stem_requirments, DROP key_requirments, DROP distractor_requirments, DROP common_authoring_problems');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE task_model ADD stem_requirments VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, ADD key_requirments VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, ADD distractor_requirments VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, ADD common_authoring_problems VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, DROP stem_requirements, DROP key_requirements, DROP distractor_requirements, DROP common_authoring_problems_requirements');
    }
}
