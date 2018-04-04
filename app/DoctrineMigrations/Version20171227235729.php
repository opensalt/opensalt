<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171227235729 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE IF NOT EXISTS audit_ls_doc (id INT NOT NULL, rev INT NOT NULL, org_id INT DEFAULT NULL, user_id INT DEFAULT NULL, licence_id INT DEFAULT NULL, identifier VARCHAR(300) DEFAULT NULL, uri VARCHAR(300) DEFAULT NULL, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime)\', official_uri VARCHAR(300) DEFAULT NULL, creator VARCHAR(300) DEFAULT NULL, publisher VARCHAR(50) DEFAULT NULL, title VARCHAR(120) DEFAULT NULL, url_name VARCHAR(255) DEFAULT NULL, version VARCHAR(50) DEFAULT NULL, description VARCHAR(300) DEFAULT NULL, subject VARCHAR(50) DEFAULT NULL, subject_uri VARCHAR(300) DEFAULT NULL, language VARCHAR(10) DEFAULT NULL, adoption_status VARCHAR(50) DEFAULT NULL, status_start DATE DEFAULT NULL, status_end DATE DEFAULT NULL, note LONGTEXT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_2017c4975e95098d54218556d75e37b6_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS audit_rubric (id INT NOT NULL, rev INT NOT NULL, identifier VARCHAR(300) DEFAULT NULL, uri VARCHAR(300) DEFAULT NULL, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime)\', title TEXT DEFAULT NULL, description TEXT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_5fd145a56a99316eb4b8a09af1f272dd_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS audit_ls_item (id INT NOT NULL, rev INT NOT NULL, ls_doc_id INT DEFAULT NULL, item_type_id INT DEFAULT NULL, licence_id INT DEFAULT NULL, identifier VARCHAR(300) DEFAULT NULL, uri VARCHAR(300) DEFAULT NULL, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime)\', ls_doc_identifier VARCHAR(300) DEFAULT NULL, ls_doc_uri VARCHAR(300) DEFAULT NULL, human_coding_scheme VARCHAR(50) DEFAULT NULL, list_enum_in_source VARCHAR(20) DEFAULT NULL, rank BIGINT DEFAULT NULL, full_statement LONGTEXT DEFAULT NULL, abbreviated_statement VARCHAR(60) DEFAULT NULL, concept_keywords VARCHAR(300) DEFAULT NULL, concept_keywords_uri VARCHAR(300) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, language VARCHAR(10) DEFAULT NULL, educational_alignment VARCHAR(300) DEFAULT NULL, alternative_label VARCHAR(255) DEFAULT NULL, status_start DATE DEFAULT NULL, status_end DATE DEFAULT NULL, licence_uri VARCHAR(300) DEFAULT NULL, changed_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime)\', revtype VARCHAR(4) NOT NULL, INDEX rev_6d8f50f455093038b4d1fe3c1726bbd2_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS audit_ls_doc_attribute (attribute VARCHAR(255) NOT NULL, ls_doc_id INT NOT NULL, rev INT NOT NULL, value VARCHAR(255) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_85ec0ebcb7db34facfef5f4dae36f48b_idx (rev), PRIMARY KEY(ls_doc_id, attribute, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS audit_ls_association (id INT NOT NULL, rev INT NOT NULL, ls_doc_id INT DEFAULT NULL, assoc_group_id INT DEFAULT NULL, origin_lsdoc_id INT DEFAULT NULL, origin_lsitem_id INT DEFAULT NULL, destination_lsdoc_id INT DEFAULT NULL, destination_lsitem_id INT DEFAULT NULL, identifier VARCHAR(300) DEFAULT NULL, uri VARCHAR(300) DEFAULT NULL, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime)\', ls_doc_identifier VARCHAR(300) DEFAULT NULL, ls_doc_uri VARCHAR(300) DEFAULT NULL, group_name VARCHAR(50) DEFAULT NULL, group_uri VARCHAR(300) DEFAULT NULL, origin_node_identifier VARCHAR(300) DEFAULT NULL, origin_node_uri VARCHAR(300) DEFAULT NULL, destination_node_identifier VARCHAR(300) DEFAULT NULL, destination_node_uri VARCHAR(300) DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, seq BIGINT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_ab9f033153ddaddc13326ef55b668486_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS audit_rubric_criterion_level (id INT NOT NULL, rev INT NOT NULL, criterion_id INT DEFAULT NULL, identifier VARCHAR(300) DEFAULT NULL, uri VARCHAR(300) DEFAULT NULL, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime)\', description TEXT DEFAULT NULL, quality TEXT DEFAULT NULL, score DOUBLE PRECISION DEFAULT NULL, feedback TEXT DEFAULT NULL, position INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_5a28dfe017e53a59266a717428a3d7a8_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS audit_ls_def_licence (id INT NOT NULL, rev INT NOT NULL, identifier VARCHAR(300) DEFAULT NULL, uri VARCHAR(300) DEFAULT NULL, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime)\', title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, licence_text LONGTEXT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_065fb16d0e1a3cb4b15539c2daa33f05_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS audit_ls_def_association_grouping (id INT NOT NULL, rev INT NOT NULL, ls_doc_id INT DEFAULT NULL, identifier VARCHAR(300) DEFAULT NULL, uri VARCHAR(300) DEFAULT NULL, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime)\', title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_bf7194a1c00561d84a1d7e91cb7c75be_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS audit_rubric_criterion (id INT NOT NULL, rev INT NOT NULL, ls_item_id INT DEFAULT NULL, rubric_id INT DEFAULT NULL, identifier VARCHAR(300) DEFAULT NULL, uri VARCHAR(300) DEFAULT NULL, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime)\', category VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, weight DOUBLE PRECISION DEFAULT NULL, position INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_c95d29726857963f63405978aa1e6853_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS audit_ls_def_subject (id INT NOT NULL, rev INT NOT NULL, identifier VARCHAR(300) DEFAULT NULL, uri VARCHAR(300) DEFAULT NULL, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime)\', title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, hierarchy_code VARCHAR(255) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_9aeadfa556e645f082aebe4697c43d9e_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS audit_ls_def_item_type (id INT NOT NULL, rev INT NOT NULL, identifier VARCHAR(300) DEFAULT NULL, uri VARCHAR(300) DEFAULT NULL, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime)\', title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, code VARCHAR(255) DEFAULT NULL, hierarchy_code VARCHAR(255) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_2d6815a36298d9fed8cbaa375f32e90d_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS audit_ls_def_grade (id INT NOT NULL, rev INT NOT NULL, identifier VARCHAR(300) DEFAULT NULL, uri VARCHAR(300) DEFAULT NULL, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime)\', title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, code VARCHAR(255) DEFAULT NULL, rank INT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_45e38822f0d366b3b685da14d9e5debb_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS audit_ls_def_concept (id INT NOT NULL, rev INT NOT NULL, identifier VARCHAR(300) DEFAULT NULL, uri VARCHAR(300) DEFAULT NULL, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime)\', title VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, hierarchy_code VARCHAR(255) DEFAULT NULL, keywords LONGTEXT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_75324ebee3373577889b17bc22abf34e_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS audit_salt_org (id INT NOT NULL, rev INT NOT NULL, name VARCHAR(255) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_59b3da2b9e2bcba5d4a77563efc38235_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS audit_salt_user (id INT NOT NULL, rev INT NOT NULL, org_id INT DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, password VARCHAR(64) DEFAULT NULL, roles JSON DEFAULT NULL COMMENT \'(DC2Type:json_array)\', locked TINYINT(1) DEFAULT \'0\', github_token VARCHAR(40) DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_46f55645cc7f32a05776ae6c103d5adb_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS audit_salt_user_doc_acl (user_id INT NOT NULL, doc_id INT NOT NULL, rev INT NOT NULL, access SMALLINT DEFAULT NULL, revtype VARCHAR(4) NOT NULL, INDEX rev_8112d0e5935d0790cc5f0299a2408621_idx (rev), PRIMARY KEY(user_id, doc_id, rev)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS audit_revision (id INT AUTO_INCREMENT NOT NULL, timestamp DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', username VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ls_doc CHANGE updated_at updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE rubric CHANGE updated_at updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE ls_item CHANGE changed_at changed_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\', CHANGE updated_at updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE ls_association CHANGE updated_at updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE rubric_criterion_level CHANGE updated_at updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE ls_def_licence CHANGE updated_at updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE ls_def_association_grouping CHANGE updated_at updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE rubric_criterion CHANGE updated_at updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE ls_def_subject CHANGE updated_at updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE ls_def_item_type CHANGE updated_at updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE ls_def_grade CHANGE updated_at updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE ls_def_concept CHANGE updated_at updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE salt_comment_upvote CHANGE created_at created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\', CHANGE updated_at updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE salt_comment CHANGE created_at created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\', CHANGE updated_at updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime)\'');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE IF EXISTS audit_ls_doc');
        $this->addSql('DROP TABLE IF EXISTS audit_rubric');
        $this->addSql('DROP TABLE IF EXISTS audit_ls_item');
        $this->addSql('DROP TABLE IF EXISTS audit_ls_doc_attribute');
        $this->addSql('DROP TABLE IF EXISTS audit_ls_association');
        $this->addSql('DROP TABLE IF EXISTS audit_rubric_criterion_level');
        $this->addSql('DROP TABLE IF EXISTS audit_ls_def_licence');
        $this->addSql('DROP TABLE IF EXISTS audit_ls_def_association_grouping');
        $this->addSql('DROP TABLE IF EXISTS audit_rubric_criterion');
        $this->addSql('DROP TABLE IF EXISTS audit_ls_def_subject');
        $this->addSql('DROP TABLE IF EXISTS audit_ls_def_item_type');
        $this->addSql('DROP TABLE IF EXISTS audit_ls_def_grade');
        $this->addSql('DROP TABLE IF EXISTS audit_ls_def_concept');
        $this->addSql('DROP TABLE IF EXISTS audit_salt_org');
        $this->addSql('DROP TABLE IF EXISTS audit_salt_user');
        $this->addSql('DROP TABLE IF EXISTS audit_salt_user_doc_acl');
        $this->addSql('DROP TABLE IF EXISTS audit_revision');
        $this->addSql('ALTER TABLE ls_association CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE ls_def_association_grouping CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE ls_def_concept CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE ls_def_grade CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE ls_def_item_type CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE ls_def_licence CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE ls_def_subject CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE ls_doc CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE ls_item CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime)\', CHANGE changed_at changed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE rubric CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE rubric_criterion CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE rubric_criterion_level CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE salt_comment CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime)\', CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE salt_comment_upvote CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime)\', CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT \'(DC2Type:datetime)\'');
    }
}
