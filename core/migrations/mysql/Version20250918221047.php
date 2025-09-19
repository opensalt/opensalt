<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250918221047 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update association types to be camelCase';
    }

    public function up(Schema $schema): void
    {
        // Update association types to camelCase
        $this->addSql('UPDATE ls_association SET type = CASE type
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
            ELSE type
        END');
    }

    public function down(Schema $schema): void
    {
        // Revert association types to human-readable
        $this->addSql('UPDATE ls_association SET type = CASE type
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
            ELSE type
        END');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
