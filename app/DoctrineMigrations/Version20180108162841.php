<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180108162841 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('
CREATE TEMPORARY TABLE audit_doc
(rev INT AUTO_INCREMENT NOT NULL, doc_id INT DEFAULT NULL, PRIMARY KEY (rev), UNIQUE INDEX (doc_id));

INSERT INTO audit_revision
(timestamp, username)
VALUES
(NOW(), NULL);

INSERT INTO audit_revision
(timestamp, username)
SELECT NOW(), id FROM ls_doc;

INSERT INTO audit_doc (rev, doc_id)
SELECT MAX(id), NULL
FROM audit_revision
WHERE username IS NULL
GROUP BY username;

INSERT INTO audit_doc (rev, doc_id)
SELECT MAX(id), username
FROM audit_revision
WHERE username IN (SELECT id from ls_doc)
GROUP BY username;

UPDATE audit_revision
SET username = NULL
WHERE id IN (SELECT rev FROM audit_doc);
        ');
        $this->addSql('
INSERT IGNORE INTO audit_ls_doc
(id, rev, org_id, user_id, licence_id, identifier, uri, extra, updated_at, official_uri, creator, publisher, title, url_name, version, description, subject, subject_uri, language, adoption_status, status_start, status_end, note, revtype)
SELECT
id, (SELECT ad.rev FROM audit_doc ad WHERE ad.doc_id = id), org_id, user_id, licence_id, identifier, uri, extra, updated_at, official_uri, creator, publisher, title, url_name, version, description, subject, subject_uri, language, adoption_status, status_start, status_end, note, "INS"
FROM ls_doc;
        ');
        $this->addSql('
INSERT IGNORE INTO audit_rubric
(id, rev, identifier, uri, extra, updated_at, title, description, revtype)
SELECT
id, (SELECT ad.rev FROM audit_doc ad WHERE ad.doc_id IS NULL), identifier, uri, extra, updated_at, title, description, "INS"
FROM rubric;
        ');
        $this->addSql('
INSERT IGNORE INTO audit_ls_item
(id, rev, ls_doc_id, item_type_id, licence_id, identifier, uri, extra, updated_at, ls_doc_identifier, ls_doc_uri, human_coding_scheme, list_enum_in_source, rank, full_statement, abbreviated_statement, concept_keywords, concept_keywords_uri, notes, language, educational_alignment, alternative_label, status_start, status_end, licence_uri, changed_at, revtype)
SELECT
id, (SELECT ad.rev FROM audit_doc ad WHERE ad.doc_id = ls_doc_id), ls_doc_id, item_type_id, licence_id, identifier, uri, extra, updated_at, ls_doc_identifier, ls_doc_uri, human_coding_scheme, list_enum_in_source, rank, full_statement, abbreviated_statement, concept_keywords, concept_keywords_uri, notes, language, educational_alignment, alternative_label, status_start, status_end, licence_uri, changed_at, "INS"
FROM ls_item;
        ');
        $this->addSql('
INSERT IGNORE INTO audit_ls_doc_attribute
(attribute, ls_doc_id, rev, value, revtype)
SELECT
attribute, ls_doc_id, (SELECT ad.rev FROM audit_doc ad WHERE ad.doc_id = ls_doc_id), value, "INS"
FROM ls_doc_attribute;
        ');
        $this->addSql('
INSERT IGNORE INTO audit_ls_association
(id, rev, ls_doc_id, assoc_group_id, origin_lsdoc_id, origin_lsitem_id, destination_lsdoc_id, destination_lsitem_id, identifier, uri, extra, updated_at, ls_doc_identifier, ls_doc_uri, group_name, group_uri, origin_node_identifier, origin_node_uri, destination_node_identifier, destination_node_uri, type, seq, revtype)
SELECT
id, (SELECT ad.rev FROM audit_doc ad WHERE ad.doc_id = ls_doc_id), ls_doc_id, assoc_group_id, origin_lsdoc_id, origin_lsitem_id, destination_lsdoc_id, destination_lsitem_id, identifier, uri, extra, updated_at, ls_doc_identifier, ls_doc_uri, group_name, group_uri, origin_node_identifier, origin_node_uri, destination_node_identifier, destination_node_uri, type, seq, "INS"
FROM ls_association;
        ');
        $this->addSql('
INSERT IGNORE INTO audit_rubric_criterion_level
(id, rev, criterion_id, identifier, uri, extra, updated_at, description, quality, score, feedback, position, revtype)
SELECT
id, (SELECT ad.rev FROM audit_doc ad WHERE ad.doc_id IS NULL), criterion_id, identifier, uri, extra, updated_at, description, quality, score, feedback, position, "INS"
FROM rubric_criterion_level;
        ');
        $this->addSql('
INSERT IGNORE INTO audit_ls_def_licence
(id, rev, identifier, uri, extra, updated_at, title, description, licence_text, revtype)
SELECT
id, 1, identifier, uri, extra, updated_at, title, description, licence_text, "INS"
FROM ls_def_licence;
        ');
        $this->addSql('
INSERT IGNORE INTO audit_ls_def_association_grouping
(id, rev, ls_doc_id, identifier, uri, extra, updated_at, title, description, revtype)
SELECT
id, (SELECT ad.rev FROM audit_doc ad WHERE ad.doc_id = ls_doc_id), ls_doc_id, identifier, uri, extra, updated_at, title, description, "INS"
FROM ls_def_association_grouping;
        ');
        $this->addSql('
INSERT IGNORE INTO audit_rubric_criterion
(id, rev, ls_item_id, rubric_id, identifier, uri, extra, updated_at, category, description, weight, position, revtype)
SELECT
id, (SELECT ad.rev FROM audit_doc ad WHERE ad.doc_id IS NULL), ls_item_id, rubric_id, identifier, uri, extra, updated_at, category, description, weight, position, "INS"
FROM rubric_criterion;
        ');
        $this->addSql('
INSERT IGNORE INTO audit_ls_def_subject
(id, rev, identifier, uri, extra, updated_at, title, description, hierarchy_code, revtype)
SELECT
id, (SELECT ad.rev FROM audit_doc ad WHERE ad.doc_id IS NULL), identifier, uri, extra, updated_at, title, description, hierarchy_code, "INS"
FROM ls_def_subject;
        ');
        $this->addSql('
INSERT IGNORE INTO audit_ls_def_item_type
(id, rev, identifier, uri, extra, updated_at, title, description, code, hierarchy_code, revtype)
SELECT
id, (SELECT ad.rev FROM audit_doc ad WHERE ad.doc_id IS NULL), identifier, uri, extra, updated_at, title, description, code, hierarchy_code, "INS"
FROM ls_def_item_type;
        ');
        $this->addSql('
INSERT IGNORE INTO audit_ls_def_grade
(id, rev, identifier, uri, extra, updated_at, title, description, code, rank, revtype)
SELECT
id, (SELECT ad.rev FROM audit_doc ad WHERE ad.doc_id IS NULL), identifier, uri, extra, updated_at, title, description, code, rank, "INS"
FROM ls_def_grade;
        ');
        $this->addSql('
INSERT IGNORE INTO audit_ls_def_concept
(id, rev, identifier, uri, extra, updated_at, title, description, hierarchy_code, keywords, revtype)
SELECT
id, (SELECT ad.rev FROM audit_doc ad WHERE ad.doc_id IS NULL), identifier, uri, extra, updated_at, title, description, hierarchy_code, keywords, "INS"
FROM ls_def_concept;
        ');
        $this->addSql('
INSERT IGNORE INTO audit_salt_org
(id, rev, name, revtype)
SELECT
id, (SELECT ad.rev FROM audit_doc ad WHERE ad.doc_id IS NULL), name, "INS"
FROM salt_org;
        ');
        $this->addSql('
INSERT IGNORE INTO audit_salt_user
(id, rev, org_id, username, password, roles, locked, github_token, revtype)
SELECT
id, (SELECT ad.rev FROM audit_doc ad WHERE ad.doc_id IS NULL), org_id, username, password, roles, locked, github_token, "INS"
FROM salt_user;
        ');
        $this->addSql('
INSERT IGNORE INTO audit_salt_user_doc_acl
(user_id, doc_id, rev, access, revtype)
SELECT
user_id, doc_id, (SELECT ad.rev FROM audit_doc ad WHERE ad.doc_id = doc_id), access, "INS"
FROM salt_user_doc_acl;
        ');
    }

    public function down(Schema $schema)
    {
        $this->addSql('
TRUNCATE audit_ls_doc;
TRUNCATE audit_rubric;
TRUNCATE audit_ls_item;
TRUNCATE audit_ls_doc_attribute;
TRUNCATE audit_ls_association;
TRUNCATE audit_rubric_criterion_level;
TRUNCATE audit_ls_def_licence;
TRUNCATE audit_ls_def_association_grouping;
TRUNCATE audit_rubric_criterion;
TRUNCATE audit_ls_def_subject;
TRUNCATE audit_ls_def_item_type;
TRUNCATE audit_ls_def_grade;
TRUNCATE audit_ls_def_concept;
TRUNCATE audit_salt_org;
TRUNCATE audit_salt_user;
TRUNCATE audit_salt_user_doc_acl;
TRUNCATE audit_salt_change;
TRUNCATE audit_revision;
        ');
    }
}
