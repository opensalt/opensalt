<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20191016232759 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add changed_at columns.';
    }

    public function up(Schema $schema): void
    {
        $tables = [
            'ls_doc',
            'ls_def_association_grouping',
            'ls_association',
            'ls_def_concept',
            'ls_def_grade',
            'ls_def_item_type',
            'ls_def_licence',
            'ls_def_subject',
            'rubric',
            'rubric_criterion_level',
            'rubric_criterion',
        ];

        foreach ($tables as $table) {
            $this->addSql('ALTER TABLE '.$table.' ADD changed_at DATETIME(6) NULL COMMENT \'(DC2Type:datetime)\' AFTER updated_at');
            $this->addSql('ALTER TABLE audit_'.$table.' ADD changed_at DATETIME(6) NULL COMMENT \'(DC2Type:datetime)\' AFTER updated_at');

            $this->addSql("UPDATE {$table} SET changed_at = updated_at");
            $this->addSql("UPDATE audit_{$table} SET changed_at = updated_at");

            $this->addSql('ALTER TABLE '.$table.' CHANGE changed_at changed_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\'');
        }
    }

    public function down(Schema $schema): void
    {
        $tables = [
            'ls_doc',
            'ls_def_association_grouping',
            'ls_association',
            'ls_def_concept',
            'ls_def_grade',
            'ls_def_item_type',
            'ls_def_licence',
            'ls_def_subject',
            'rubric',
            'rubric_criterion_level',
            'rubric_criterion',
        ];

        foreach ($tables as $table) {
            $this->addSql('ALTER TABLE '.$table.' DROP changed_at');
            $this->addSql('ALTER TABLE audit_'.$table.' DROP changed_at');
        }
    }
}
