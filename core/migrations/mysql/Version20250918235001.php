<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;

final class Version20250918235001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert salt_association_subtype.parentType from title-case to camelCase for CASE compliance';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE salt_association_subtype SET parent_type = CASE parent_type
            WHEN \'Is Child Of\' THEN \'isChildOf\'
            WHEN \'Exact Match Of\' THEN \'exactMatchOf\'
            WHEN \'Is Related To\' THEN \'isRelatedTo\'
            WHEN \'Is Part Of\' THEN \'isPartOf\'
            WHEN \'Replaced By\' THEN \'replacedBy\'
            WHEN \'Precedes\' THEN \'precedes\'
            WHEN \'Has Skill Level\' THEN \'hasSkillLevel\'
            WHEN \'Is Peer Of\' THEN \'isPeerOf\'
            WHEN \'Exemplar\' THEN \'exemplar\'
            WHEN \'Is Translation Of\' THEN \'isTranslationOf\'
            ELSE parent_type
        END');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE salt_association_subtype SET parent_type = CASE parent_type
            WHEN \'isChildOf\' THEN \'Is Child Of\'
            WHEN \'exactMatchOf\' THEN \'Exact Match Of\'
            WHEN \'isRelatedTo\' THEN \'Is Related To\'
            WHEN \'isPartOf\' THEN \'Is Part Of\'
            WHEN \'replacedBy\' THEN \'Replaced By\'
            WHEN \'precedes\' THEN \'Precedes\'
            WHEN \'hasSkillLevel\' THEN \'Has Skill Level\'
            WHEN \'isPeerOf\' THEN \'Is Peer Of\'
            WHEN \'exemplar\' THEN \'Exemplar\'
            WHEN \'isTranslationOf\' THEN \'Is Translation Of\'
            ELSE parent_type
        END');
    }
}
