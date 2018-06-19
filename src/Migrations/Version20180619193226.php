<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180619193226 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE task_model (id INT AUTO_INCREMENT NOT NULL, task_narrative LONGTEXT NOT NULL, depth_of_knowledge VARCHAR(255) NOT NULL, item_type VARCHAR(255) NOT NULL, available_tools VARCHAR(255) NOT NULL, accessibility_concerns VARCHAR(255) NOT NULL, task_model_variables VARCHAR(255) NOT NULL, passage_stimulus_spec_code VARCHAR(255) NOT NULL, common_errors_misconceptions VARCHAR(255) NOT NULL, stem_requirments VARCHAR(255) NOT NULL, key_requirments VARCHAR(255) NOT NULL, distractor_requirments VARCHAR(255) NOT NULL, tei_guidelines VARCHAR(255) NOT NULL, task_model_notes LONGTEXT NOT NULL, example_items LONGTEXT NOT NULL, rubric_scoring_rules VARCHAR(255) NOT NULL, item_authoring_tips VARCHAR(255) NOT NULL, common_authoring_problems VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ls_item DROP type');
        $this->addSql('ALTER TABLE salt_user CHANGE status status INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE audit_salt_user CHANGE status status INT DEFAULT 0');
        $this->addSql('DROP INDEX changed_doc ON audit_salt_change');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE task_model');
        $this->addSql('CREATE INDEX changed_doc ON audit_salt_change (doc_id, changed_at)');
        $this->addSql('ALTER TABLE audit_salt_user CHANGE status status INT DEFAULT 2 NOT NULL');
        $this->addSql('ALTER TABLE ls_item ADD type VARCHAR(60) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE salt_user CHANGE status status INT DEFAULT 2 NOT NULL');
    }
}
