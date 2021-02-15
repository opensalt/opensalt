<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20191008182209 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make rubric parents not-null';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE rubric_criterion_level CHANGE criterion_id criterion_id INT NOT NULL');
        $this->addSql('ALTER TABLE rubric_criterion CHANGE rubric_id rubric_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE rubric_criterion CHANGE rubric_id rubric_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE rubric_criterion_level CHANGE criterion_id criterion_id INT DEFAULT NULL');
    }
}
