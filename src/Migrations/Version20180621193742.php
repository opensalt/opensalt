<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180621193742 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE task_model (id INT AUTO_INCREMENT NOT NULL, ls_item_id INT NOT NULL, task_narrative LONGTEXT NOT NULL, depth_of_knowledge VARCHAR(255) NOT NULL, item_type VARCHAR(255) NOT NULL, available_tools VARCHAR(255) NOT NULL, accessibility_concerns VARCHAR(255) NOT NULL, task_model_variables VARCHAR(255) NOT NULL, passage_stimulus_spec_code VARCHAR(255) NOT NULL, common_errors_misconceptions VARCHAR(255) NOT NULL, stem_requirements VARCHAR(255) NOT NULL, key_requirements VARCHAR(255) NOT NULL, distractor_requirements VARCHAR(255) NOT NULL, tei_guidelines VARCHAR(255) NOT NULL, task_model_notes LONGTEXT NOT NULL, example_items LONGTEXT NOT NULL, rubric_scoring_rules VARCHAR(255) NOT NULL, item_authoring_tips VARCHAR(255) NOT NULL, common_authoring_problems_requirements VARCHAR(255) NOT NULL, INDEX IDX_103169F9E27A1FD2 (ls_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE task_model ADD CONSTRAINT FK_103169F9E27A1FD2 FOREIGN KEY (ls_item_id) REFERENCES ls_item (id)');
        $this->addSql('ALTER TABLE ls_item DROP type');
        $this->addSql('ALTER TABLE salt_user CHANGE status status INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE audit_salt_user CHANGE status status INT DEFAULT 0');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE task_model');
        $this->addSql('ALTER TABLE audit_salt_user CHANGE status status INT DEFAULT 2 NOT NULL');
        $this->addSql('ALTER TABLE ls_item ADD type VARCHAR(60) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE salt_user CHANGE status status INT DEFAULT 2 NOT NULL');
    }
}
