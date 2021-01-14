<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201223202520 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove audit tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('TRUNCATE TABLE salt_change');
        $this->addSql('ALTER TABLE salt_change DROP FOREIGN KEY FK_8427C157895648BC');
        $this->addSql('ALTER TABLE salt_change DROP FOREIGN KEY FK_8427C157A76ED395');
        $this->addSql('DROP INDEX UNIQ_8427C157895648BC ON salt_change');
        $this->addSql('DROP INDEX IDX_8427C157A76ED395 ON salt_change');
        $this->addSql('ALTER TABLE salt_change ADD username VARCHAR(255) DEFAULT NULL AFTER user_id, CHANGE id id BIGINT AUTO_INCREMENT NOT NULL');
        $this->addSql('CREATE INDEX doc_idx ON salt_change (doc_id, changed_at)');
        $this->addSql('
INSERT INTO salt_change
  (id, user_id, username, doc_id, changed_at, description, changed) 
SELECT a.rev, a.user_id, u.username, a.doc_id, a.changed_at, a.description, a.changed
  FROM audit_salt_change a
  LEFT JOIN salt_user u ON u.id = a.user_id
 ORDER BY a.rev;
        ');

        $this->addSql('DROP TABLE audit_ls_association');
        $this->addSql('DROP TABLE audit_ls_def_association_grouping');
        $this->addSql('DROP TABLE audit_ls_def_concept');
        $this->addSql('DROP TABLE audit_ls_def_grade');
        $this->addSql('DROP TABLE audit_ls_def_item_type');
        $this->addSql('DROP TABLE audit_ls_def_licence');
        $this->addSql('DROP TABLE audit_ls_def_subject');
        $this->addSql('DROP TABLE audit_ls_doc');
        $this->addSql('DROP TABLE audit_ls_doc_attribute');
        $this->addSql('DROP TABLE audit_ls_item');
        $this->addSql('DROP TABLE audit_revision');
        $this->addSql('DROP TABLE audit_rubric');
        $this->addSql('DROP TABLE audit_rubric_criterion');
        $this->addSql('DROP TABLE audit_rubric_criterion_level');
        $this->addSql('DROP TABLE audit_salt_change');
        $this->addSql('DROP TABLE audit_salt_org');
        $this->addSql('DROP TABLE audit_salt_user');
        $this->addSql('DROP TABLE audit_salt_user_doc_acl');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE audit_ls_association (id INT NOT NULL, rev INT NOT NULL, ls_doc_id INT DEFAULT NULL, assoc_group_id INT DEFAULT NULL, origin_lsdoc_id INT DEFAULT NULL, origin_lsitem_id INT DEFAULT NULL, destination_lsdoc_id INT DEFAULT NULL, destination_lsitem_id INT DEFAULT NULL, identifier VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, uri VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', changed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', ls_doc_identifier VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ls_doc_uri VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, origin_node_identifier VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, origin_node_uri VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, destination_node_identifier VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, destination_node_uri VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, type VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, seq BIGINT DEFAULT NULL, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, subtype VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, annotation TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_ab9f033153ddaddc13326ef55b668486_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE audit_ls_def_association_grouping (id INT NOT NULL, rev INT NOT NULL, ls_doc_id INT DEFAULT NULL, identifier VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, uri VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', changed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', title VARCHAR(1024) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_bf7194a1c00561d84a1d7e91cb7c75be_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE audit_ls_def_concept (id INT NOT NULL, rev INT NOT NULL, identifier VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, uri VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', changed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', title VARCHAR(1024) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, hierarchy_code VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, keywords LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_75324ebee3373577889b17bc22abf34e_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE audit_ls_def_grade (id INT NOT NULL, rev INT NOT NULL, identifier VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, uri VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', changed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', title VARCHAR(1024) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, code VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, rank INT DEFAULT NULL, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_45e38822f0d366b3b685da14d9e5debb_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE audit_ls_def_item_type (id INT NOT NULL, rev INT NOT NULL, identifier VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, uri VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', changed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', title VARCHAR(1024) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, code VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, hierarchy_code VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_2d6815a36298d9fed8cbaa375f32e90d_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE audit_ls_def_licence (id INT NOT NULL, rev INT NOT NULL, identifier VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, uri VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', changed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', title VARCHAR(1024) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, licence_text LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_065fb16d0e1a3cb4b15539c2daa33f05_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE audit_ls_def_subject (id INT NOT NULL, rev INT NOT NULL, identifier VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, uri VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', changed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', title VARCHAR(1024) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, hierarchy_code VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_9aeadfa556e645f082aebe4697c43d9e_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE audit_ls_doc (id INT NOT NULL, rev INT NOT NULL, org_id INT DEFAULT NULL, user_id INT DEFAULT NULL, licence_id INT DEFAULT NULL, identifier VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, uri VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', changed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', official_uri VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, creator VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, publisher VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, title VARCHAR(120) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, url_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, version VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, description VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, subject JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', language VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, adoption_status VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, status_start DATE DEFAULT NULL, status_end DATE DEFAULT NULL, note LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, frameworktype_id INT DEFAULT NULL, mirrored_framework_id INT DEFAULT NULL, INDEX rev_2017c4975e95098d54218556d75e37b6_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE audit_ls_doc_attribute (attribute VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, ls_doc_id INT NOT NULL, rev INT NOT NULL, value VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_85ec0ebcb7db34facfef5f4dae36f48b_idx (rev), PRIMARY KEY(ls_doc_id, attribute, rev)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE audit_ls_item (id INT NOT NULL, rev INT NOT NULL, ls_doc_id INT DEFAULT NULL, item_type_id INT DEFAULT NULL, item_type_text VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, licence_id INT DEFAULT NULL, identifier VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, uri VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', ls_doc_identifier VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ls_doc_uri VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, human_coding_scheme VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, list_enum_in_source VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, full_statement LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, abbreviated_statement LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, concept_keywords JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, language VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, educational_alignment VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, alternative_label LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, status_start DATE DEFAULT NULL, status_end DATE DEFAULT NULL, changed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_6d8f50f455093038b4d1fe3c1726bbd2_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE audit_revision (id INT AUTO_INCREMENT NOT NULL, timestamp DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE audit_rubric (id INT NOT NULL, rev INT NOT NULL, identifier VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, uri VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', changed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', title TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_5fd145a56a99316eb4b8a09af1f272dd_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE audit_rubric_criterion (id INT NOT NULL, rev INT NOT NULL, ls_item_id INT DEFAULT NULL, rubric_id INT DEFAULT NULL, identifier VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, uri VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', changed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', category VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, weight DOUBLE PRECISION DEFAULT NULL, position INT DEFAULT NULL, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_c95d29726857963f63405978aa1e6853_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE audit_rubric_criterion_level (id INT NOT NULL, rev INT NOT NULL, criterion_id INT DEFAULT NULL, identifier VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, uri VARCHAR(300) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, extra JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', changed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, quality TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, score DOUBLE PRECISION DEFAULT NULL, feedback TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, position INT DEFAULT NULL, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_5a28dfe017e53a59266a717428a3d7a8_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE audit_salt_change (id INT NOT NULL, rev INT NOT NULL, user_id INT DEFAULT NULL, doc_id INT DEFAULT NULL, changed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', description VARCHAR(2048) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, changed JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX changed_doc (doc_id, changed_at), INDEX rev_f4da141f313c5617c27c4f1fb9f2a4a1_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE audit_salt_org (id INT NOT NULL, rev INT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_59b3da2b9e2bcba5d4a77563efc38235_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE audit_salt_user (id INT NOT NULL, rev INT NOT NULL, org_id INT DEFAULT NULL, username VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, password VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, roles JSON DEFAULT NULL COMMENT \'(DC2Type:json_array)\', github_token VARCHAR(40) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, status INT DEFAULT 2 NOT NULL, INDEX rev_46f55645cc7f32a05776ae6c103d5adb_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE audit_salt_user_doc_acl (id INT NOT NULL, rev INT NOT NULL, user_id INT DEFAULT NULL, doc_id INT DEFAULT NULL, access SMALLINT DEFAULT NULL, revtype VARCHAR(4) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX rev_8112d0e5935d0790cc5f0299a2408621_idx (rev), PRIMARY KEY(id, rev)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('TRUNCATE TABLE salt_change');
        $this->addSql('DROP INDEX doc_idx ON salt_change');
        $this->addSql('ALTER TABLE salt_change DROP username, CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE salt_change ADD CONSTRAINT FK_8427C157895648BC FOREIGN KEY (doc_id) REFERENCES ls_doc (id)');
        $this->addSql('ALTER TABLE salt_change ADD CONSTRAINT FK_8427C157A76ED395 FOREIGN KEY (user_id) REFERENCES salt_user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8427C157895648BC ON salt_change (doc_id)');
        $this->addSql('CREATE INDEX IDX_8427C157A76ED395 ON salt_change (user_id)');
    }
}