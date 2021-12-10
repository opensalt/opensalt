<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

class Version20171004005547 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $dbParams = $this->connection->getParams();
        $dbName = $dbParams['dbname'];

        $this->addSql("
ALTER DATABASE {$dbName} CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
        ");

        $tables = [
            ' ls_def_association_grouping',
            ' ls_doc',
            ' rubric',
            ' ls_item',
            ' salt_org',
            ' salt_user',
            ' cache_items',
            ' import_logs',
            ' auth_session',
            ' ls_def_grade',
            ' salt_comment',
            ' ls_association',
            ' ls_def_concept',
            ' ls_def_licence',
            ' ls_def_subject',
            ' ls_doc_subject',
            ' ls_item_concept',
            ' ls_def_item_type',
            ' ls_doc_attribute',
            ' rubric_criterion',
            ' salt_user_doc_acl',
            ' migration_versions',
            ' salt_comment_upvote',
            ' rubric_criterion_level',
        ];

        foreach ($tables as $table) {
            $this->addSql("
                ALTER TABLE {$table} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
                REPAIR TABLE {$table};
                OPTIMIZE TABLE {$table};
            ");
        }
    }


    public function down(Schema $schema): void
    {
        $dbParams = $this->connection->getParams();
        $dbName = $dbParams['dbname'];

        $this->addSql("
ALTER DATABASE {$dbName} CHARACTER SET = utf8 COLLATE = utf8_unicode_ci;
        ");

        $tables = [
            ' ls_def_association_grouping',
            ' ls_doc',
            ' rubric',
            ' ls_item',
            ' salt_org',
            ' salt_user',
            ' cache_items',
            ' import_logs',
            ' auth_session',
            ' ls_def_grade',
            ' salt_comment',
            ' ls_association',
            ' ls_def_concept',
            ' ls_def_licence',
            ' ls_def_subject',
            ' ls_doc_subject',
            ' ls_item_concept',
            ' ls_def_item_type',
            ' ls_doc_attribute',
            ' rubric_criterion',
            ' salt_user_doc_acl',
            ' migration_versions',
            ' salt_comment_upvote',
            ' rubric_criterion_level',
        ];

        foreach ($tables as $table) {
            $this->addSql("
                ALTER TABLE {$table} CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;
                REPAIR TABLE {$table};
                OPTIMIZE TABLE {$table};
            ");
        }
    }
}
