<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210414163628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add set of default subtypes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
INSERT IGNORE INTO salt_association_subtype (name,parent_type,direction,description) VALUES
("Identical","Exact Match Of",0,"Standards are identical.  Slight variations in punctuation or language (such as pluralization that has no impact on meaning) are counted as Identical.")
,("Equivalent","Exact Match Of",0,"Any wording change, such as rewording of a list or different use of articles (a, the, etc.) that has no impact on meaning is counted as Equivalent.")
,("Major Alignment","Is Related To",0,"Standards cover the same piece of knowledge, skill or concept, but use different wording (usually based on edits made to an original CC/Compendium standard) that could be interpreted as a substantive change.")
,("Examples Differ","Exact Match Of",0,"Standards are identical or equivalent although the state and the Compendium vary in their use of examples (include/do not include, add new, etc.)")
,("Identifiers Differ","Exact Match Of",0,"State uses a different identifier for an otherwise identical or equivalent Compendium standard. An example would be states that do not use the (+) identifier used by the Compendium for some math items.")
,("Split","Is Part Of",1,"The state has split a Compendium standard into more than one item.")
,("Consolidated","Is Part Of",-1,"A state has combined Compendium standard into a single item.")
,("Mapped by State","Is Related To",0,"Mapping between a state standard and Compendium compendium is provided by the state.")
,("Mapped by Third Party","Is Related To",0,"Mapping between a state standard and Compendium compendium is provided by a third party.")
,("Mapped","Is Related To",0,"Mapped by Author")
,("Split/Major Alignment","Is Part Of",1,"A state has split a Compendium standard into multiple state standards and/or components and the split items might add up to different wording or emphasis than the original.")
,("Consolidated/Major Alignment","Is Part Of",-1,"A state has combined Compendium standard into a single item but the combined standard might have different wording or emphasis than the original.")
,("Conceptual Alignment","Is Related To",0,"Curricular competency is similar to all or parts of one or more Compendium practice standard.")
,("Similar To/Draws From","Is Related To",0,"Standards can be seen as similar to or drawing from parts of multiple Compendium standards.")
;
        ');

        $this->addSql('
UPDATE ls_association
   SET annotation = JSON_UNQUOTE(JSON_EXTRACT(extra, "$.customFields.annotation"))
 WHERE extra IS NOT NULL
   AND JSON_EXTRACT(extra, "$.customFields.annotation") IS NOT NULL
;

UPDATE ls_association
   SET subtype = JSON_UNQUOTE(JSON_EXTRACT(extra, "$.customFields.subtype"))
 WHERE extra IS NOT NULL
   AND JSON_EXTRACT(extra, "$.customFields.subtype") IS NOT NULL
;

UPDATE ls_association
   SET subtype = "Identifiers Differ"
 WHERE subtype = "Identifier Differ"
;

UPDATE ls_association
   SET extra = JSON_REMOVE(extra, "$.customFields.annotation")
 WHERE extra IS NOT NULL
   AND JSON_CONTAINS_PATH(extra, "one", "$.customFields.annotation") = 1
;

UPDATE ls_association
   SET extra = JSON_REMOVE(extra, "$.customFields.subtype")
 WHERE extra IS NOT NULL
   AND JSON_CONTAINS_PATH(extra, "one", "$.customFields.subtype") = 1
;

UPDATE ls_association
   SET extra = JSON_REMOVE(extra, "$.customFields")
 WHERE extra IS NOT NULL
   AND JSON_LENGTH(extra, "$.customFields") = 0
;

UPDATE ls_association
   SET extra = NULL
 WHERE extra IS NOT NULL
   AND JSON_LENGTH(extra, "$") = 0
;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
UPDATE ls_association
   SET extra = JSON_OBJECT()
 WHERE extra IS NULL
    AND (annotation IS NOT NULL OR subtype IS NOT NULL)
;

UPDATE ls_association
   SET extra = JSON_SET(extra, "$.customFields", JSON_OBJECT())
 WHERE (annotation IS NOT NULL OR subtype IS NOT NULL)
;

UPDATE ls_association
   SET extra = JSON_SET(extra, "$.customFields.annotation", annotation)
 WHERE annotation IS NOT NULL
;

UPDATE ls_association
   SET extra = JSON_EXTRACT(extra, "$.customFields.subtype", subtype)
 WHERE subtype IS NOT NULL
;

UPDATE ls_association
   SET subtype = null, annotation = null
 WHERE (annotation IS NOT NULL OR subtype IS NOT NULL)
;
        ');

        $this->addSql('
DELETE FROM salt_association_subtype
 WHERE name IN
 (
  "Identical" ,"Equivalent" ,"Major Alignment" ,"Examples Differ",
  "Identifiers Differ" ,"Split" ,"Consolidated" ,"Mapped by State",
  "Mapped by Third Party" ,"Mapped" ,"Split/Major Alignment",
  "Consolidated/Major Alignment" ,"Conceptual Alignment" ,"Similar To/Draws From"
 )
        ');
    }
}
