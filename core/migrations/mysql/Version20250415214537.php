<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20250415214537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add extension columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_association ADD ext JSON DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_association_grouping ADD ext JSON DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_concept ADD ext JSON DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_grade ADD ext JSON DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_item_type ADD ext JSON DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_licence ADD ext JSON DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_subject ADD ext JSON DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_doc ADD ext JSON DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_item ADD ext JSON DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rubric ADD ext JSON DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rubric_criterion ADD ext JSON DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rubric_criterion_level ADD ext JSON DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_association DROP ext
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_association_grouping DROP ext
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_concept DROP ext
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_grade DROP ext
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_item_type DROP ext
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_licence DROP ext
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_subject DROP ext
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_doc DROP ext
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_item DROP ext
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rubric DROP ext
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rubric_criterion DROP ext
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rubric_criterion_level DROP ext
        SQL);
    }

}
