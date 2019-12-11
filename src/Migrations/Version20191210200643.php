<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Framework\LsAssociation;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191210200643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add subtype table with values';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE salt_association_subtype (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, parent_type VARCHAR(255) NOT NULL, direction INT NOT NULL, description VARCHAR(512) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $subTypes = [
            [
                'name' => 'Identical',
                'parentType' => LsAssociation::EXACT_MATCH_OF,
                'direction' => 0,
                'description' => 'Standards are identical.  Slight variations in punctuation or language (such as pluralization that has no impact on meaning) are counted as Identical.',
            ],
            [
                'name' => 'Equivalent',
                'parentType' => LsAssociation::EXACT_MATCH_OF,
                'direction' => 0,
                'description' => 'Any wording change, such as rewording of a list or different use of articles (a, the, etc.) that has no impact on meaning is counted as Equivalent.',
            ],
            [
                'name' => 'Major Alignment',
                'parentType' => LsAssociation::RELATED_TO,
                'direction' => 0,
                'description' => 'Standards cover the same piece of knowledge, skill or concept, but use different wording (usually based on edits made to an original CC/Compendium standard) that could be interpreted as a substantive change.',
            ],
            [
                'name' => 'Examples Differ',
                'parentType' => LsAssociation::EXACT_MATCH_OF,
                'direction' => 0,
                'description' => 'Standards are identical or equivalent although the state and the Compendium vary in their use of examples (include/do not include, add new, etc.)',
            ],
            [
                'name' => 'Identifiers Differ',
                'parentType' => LsAssociation::EXACT_MATCH_OF,
                'direction' => 0,
                'description' => 'State uses a different identifier for an otherwise identical or equivalent Compendium standard.  An example would be states that do not use the (+) identifier used by the Compendium for some math items.',
            ],
            [
                'name' => 'Split',
                'parentType' => LsAssociation::PART_OF,
                'direction' => 1,
                'description' => 'The state has split a Compendium standard into more than one item.',
            ],
            [
                'name' => 'Consolidated',
                'parentType' => LsAssociation::PART_OF,
                'direction' => -1,
                'description' => 'A state has combined Compendium standard into a single item.',
            ],
            [
                'name' => 'Mapped by State',
                'parentType' => LsAssociation::RELATED_TO,
                'direction' => 0,
                'description' => 'Mapping between a state standard and Compendium compendium is provided by the state.',
            ],
            [
                'name' => 'Mapped by Third Party',
                'parentType' => LsAssociation::RELATED_TO,
                'direction' => 0,
                'description' => 'Mapping between a state standard and Compendium compendium is provided by a third party.',
            ],
            [
                'name' => 'Mapped',
                'parentType' => LsAssociation::RELATED_TO,
                'direction' => 0,
                'description' => 'Mapped by Author',
            ],
            [
                'name' => 'Split/Major Alignment',
                'parentType' => LsAssociation::PART_OF,
                'direction' => 1,
                'description' => 'A state has split a Compendium standard into multiple state standards and/or components and the split items  might add up to different wording or emphasis than the original.',
            ],
            [
                'name' => 'Consolidated/Major Alignment',
                'parentType' => LsAssociation::PART_OF,
                'direction' => -1,
                'description' => 'A state has combined Compendium standard into a single item but the combined standard might have different wording or emphasis than the original.',
            ],
        ];

        foreach ($subTypes as $subType) {
            $this->addSql("
INSERT INTO salt_association_subtype
    (name, parent_type, direction, description)
VALUES
    ('{$subType['name']}', '{$subType['parentType']}', {$subType['direction']}, '{$subType['description']}')
;
            ");
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE salt_association_subtype');
    }
}
