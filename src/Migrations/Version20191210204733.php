<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191210204733 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move subtype and annotations to their new fields';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
UPDATE ls_association
   SET annotation = JSON_UNQUOTE(JSON_EXTRACT(extra, "$.customFields.annotation"))
 WHERE extra IS NOT NULL
   AND JSON_EXTRACT(extra, "$.customFields.annotation") IS NOT NULL
;
        ');

        $this->addSql('
UPDATE ls_association
   SET subtype = JSON_UNQUOTE(JSON_EXTRACT(extra, "$.customFields.subtype"))
 WHERE extra IS NOT NULL
   AND JSON_EXTRACT(extra, "$.customFields.subtype") IS NOT NULL
;
        ');

        $this->addSql('
UPDATE ls_association
   SET subtype = "Identifiers Differ"
 WHERE subtype = "Identifier Differ"
;
        ');

        $this->addSql('
UPDATE ls_association
   SET extra = JSON_REMOVE(extra, "$.customFields")
 WHERE extra IS NOT NULL
;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
UPDATE ls_association
   SET extra = JSON_OBJECT()
 WHERE extra IS NULL
    AND (annotation IS NOT NULL OR subtype IS NOT NULL)
;
        ');

        $this->addSql('
UPDATE ls_association
   SET extra = JSON_SET(extra, "$.customFields", JSON_OBJECT())
 WHERE (annotation IS NOT NULL OR subtype IS NOT NULL)
;
        ');

        $this->addSql('
UPDATE ls_association
   SET extra = JSON_SET(extra, "$.customFields.annotation", annotation)
 WHERE annotation IS NOT NULL
;
        ');

        $this->addSql('
UPDATE ls_association
   SET extra = JSON_EXTRACT(extra, "$.customFields.subtype", subtype)
 WHERE subtype IS NOT NULL
;
        ');

        $this->addSql('
UPDATE ls_association
   SET subtype = null, annotation = null
 WHERE (annotation IS NOT NULL OR subtype IS NOT NULL)
;
        ');
    }
}
