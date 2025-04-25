<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250425173813 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate to DBAL 4 metadata (remove comments)';
    }

    public function up(Schema $schema): void
    {
        // blob to longblob
        $this->addSql(<<<'SQL'
            ALTER TABLE auth_session CHANGE sess_data sess_data LONGBLOB NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE front_matter CHANGE id id BINARY(16) NOT NULL, CHANGE last_updated last_updated DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_association CHANGE updated_at updated_at DATETIME(6) NOT NULL, CHANGE changed_at changed_at DATETIME(6) NOT NULL, CHANGE extra extra JSON DEFAULT NULL, CHANGE ext ext JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_association_grouping CHANGE extra extra JSON DEFAULT NULL, CHANGE updated_at updated_at DATETIME(6) NOT NULL, CHANGE changed_at changed_at DATETIME(6) NOT NULL, CHANGE ext ext JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_concept CHANGE extra extra JSON DEFAULT NULL, CHANGE updated_at updated_at DATETIME(6) NOT NULL, CHANGE changed_at changed_at DATETIME(6) NOT NULL, CHANGE ext ext JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_grade CHANGE extra extra JSON DEFAULT NULL, CHANGE updated_at updated_at DATETIME(6) NOT NULL, CHANGE changed_at changed_at DATETIME(6) NOT NULL, CHANGE ext ext JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_item_type CHANGE extra extra JSON DEFAULT NULL, CHANGE updated_at updated_at DATETIME(6) NOT NULL, CHANGE changed_at changed_at DATETIME(6) NOT NULL, CHANGE ext ext JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_licence CHANGE extra extra JSON DEFAULT NULL, CHANGE updated_at updated_at DATETIME(6) NOT NULL, CHANGE changed_at changed_at DATETIME(6) NOT NULL, CHANGE ext ext JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_subject CHANGE extra extra JSON DEFAULT NULL, CHANGE updated_at updated_at DATETIME(6) NOT NULL, CHANGE changed_at changed_at DATETIME(6) NOT NULL, CHANGE ext ext JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_doc CHANGE subject subject JSON DEFAULT NULL, CHANGE updated_at updated_at DATETIME(6) NOT NULL, CHANGE changed_at changed_at DATETIME(6) NOT NULL, CHANGE extra extra JSON DEFAULT NULL, CHANGE ext ext JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_item CHANGE concept_keywords concept_keywords JSON DEFAULT NULL, CHANGE subject subject JSON DEFAULT NULL, CHANGE changed_at changed_at DATETIME(6) NOT NULL, CHANGE updated_at updated_at DATETIME(6) NOT NULL, CHANGE extra extra JSON DEFAULT NULL, CHANGE ext ext JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mirror_framework CHANGE last_check last_check DATETIME DEFAULT NULL, CHANGE last_success last_success DATETIME DEFAULT NULL, CHANGE last_failure last_failure DATETIME DEFAULT NULL, CHANGE last_change last_change DATETIME DEFAULT NULL, CHANGE next_check next_check DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME(6) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mirror_log CHANGE occurred_at occurred_at DATETIME(6) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mirror_oauth CHANGE updated_at updated_at DATETIME(6) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mirror_server CHANGE next_check next_check DATETIME DEFAULT NULL, CHANGE last_check last_check DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME(6) NOT NULL, CHANGE status status VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rubric CHANGE extra extra JSON DEFAULT NULL, CHANGE updated_at updated_at DATETIME(6) NOT NULL, CHANGE changed_at changed_at DATETIME(6) NOT NULL, CHANGE ext ext JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rubric_criterion CHANGE extra extra JSON DEFAULT NULL, CHANGE updated_at updated_at DATETIME(6) NOT NULL, CHANGE changed_at changed_at DATETIME(6) NOT NULL, CHANGE ext ext JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rubric_criterion_level CHANGE extra extra JSON DEFAULT NULL, CHANGE updated_at updated_at DATETIME(6) NOT NULL, CHANGE changed_at changed_at DATETIME(6) NOT NULL, CHANGE ext ext JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE salt_additional_field CHANGE type_info type_info JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE salt_change CHANGE changed_at changed_at DATETIME(6) NOT NULL, CHANGE changed changed JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE salt_comment CHANGE created_at created_at DATETIME(6) NOT NULL, CHANGE updated_at updated_at DATETIME(6) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE salt_comment_upvote CHANGE created_at created_at DATETIME(6) NOT NULL, CHANGE updated_at updated_at DATETIME(6) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE salt_object_lock CHANGE expiry expiry DATETIME(6) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE salt_user CHANGE roles roles JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL, CHANGE available_at available_at DATETIME NOT NULL, CHANGE delivered_at delivered_at DATETIME DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE auth_session CHANGE sess_data sess_data BLOB NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE front_matter CHANGE id id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid_binary)', CHANGE last_updated last_updated DATETIME NOT NULL COMMENT '(DC2Type:datetimetz_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_association CHANGE changed_at changed_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE extra extra VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)', CHANGE ext ext VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_association_grouping CHANGE changed_at changed_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE extra extra VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)', CHANGE ext ext VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_concept CHANGE changed_at changed_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE extra extra VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)', CHANGE ext ext VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_grade CHANGE changed_at changed_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE extra extra VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)', CHANGE ext ext VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_item_type CHANGE changed_at changed_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE extra extra VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)', CHANGE ext ext VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_licence CHANGE changed_at changed_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE extra extra VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)', CHANGE ext ext VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_def_subject CHANGE changed_at changed_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE extra extra VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)', CHANGE ext ext VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_doc CHANGE changed_at changed_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE extra extra VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)', CHANGE ext ext VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)', CHANGE subject subject VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ls_item CHANGE changed_at changed_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE extra extra VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)', CHANGE ext ext VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)', CHANGE concept_keywords concept_keywords VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)', CHANGE subject subject VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE messenger_messages CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE available_at available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mirror_framework CHANGE last_check last_check DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', CHANGE last_success last_success DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', CHANGE last_failure last_failure DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', CHANGE last_change last_change DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', CHANGE next_check next_check DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mirror_log CHANGE occurred_at occurred_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mirror_oauth CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE mirror_server CHANGE next_check next_check DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', CHANGE last_check last_check DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE status status VARCHAR(255) DEFAULT 'active' NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rubric CHANGE changed_at changed_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE extra extra VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)', CHANGE ext ext VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rubric_criterion CHANGE changed_at changed_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE extra extra VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)', CHANGE ext ext VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE rubric_criterion_level CHANGE changed_at changed_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE extra extra VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)', CHANGE ext ext VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE salt_additional_field CHANGE type_info type_info VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE salt_change CHANGE changed_at changed_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE changed changed VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE salt_comment CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE salt_comment_upvote CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE salt_object_lock CHANGE expiry expiry DATETIME NOT NULL COMMENT '(DC2Type:datetime)'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE salt_user CHANGE roles roles VARCHAR(0) DEFAULT NULL COMMENT '(DC2Type:json)'
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
