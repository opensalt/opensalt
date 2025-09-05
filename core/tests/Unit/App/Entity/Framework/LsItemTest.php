<?php

namespace Tests\Unit\App\Entity\Framework;

use App\Entity\Framework\CfRubric;
use App\Entity\Framework\CfRubricCriterion;
use App\Entity\Framework\LsDefConcept;
use App\Entity\Framework\LsDefItemType;
use App\Entity\Framework\LsDefLicence;
use App\Entity\Framework\LsDefSubject;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Form\Type\LsItemType;
use Ramsey\Uuid\Uuid;

class LsItemTest extends \Codeception\Test\Unit
{
    /**
     * @var \Tests\Support\UnitTester
     */
    protected $tester;

    // tests
    public function testConstructor()
    {
        $lsItem = new LsItem();

        $this->assertInstanceOf(LsItem::class, $lsItem);
        $this->assertNotNull($lsItem->getIdentifier());
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsItem->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsItem->getChangedAt());
    }

    public function testConstructorWithIdentifier()
    {
        $identifier = Uuid::uuid4()->toString();
        $lsItem = new LsItem($identifier);

        $this->assertEquals($identifier, $lsItem->getIdentifier());
    }

    public function testSetAndGetLsDocIdentifier()
    {
        $lsItem = new LsItem();
        $lsDocIdentifier = Uuid::uuid4()->toString();

        $lsItem->setLsDocIdentifier($lsDocIdentifier);
        $this->assertEquals($lsDocIdentifier, $lsItem->getLsDocIdentifier());
    }

    public function testSetAndGetLsDocUri()
    {
        $lsItem = new LsItem();
        $lsDocUri = 'https://example.com/doc';

        $lsItem->setLsDocUri($lsDocUri);
        $this->assertEquals($lsDocUri, $lsItem->getLsDocUri());
    }

    public function testSetAndGetDiscriminator()
    {
        $lsItem = new LsItem();

        $lsItem->setDiscriminator(1);
        $this->assertEquals(1, $lsItem->getDiscriminator());
    }

    public function testObjectTypeForDiscriminator()
    {
        $this->assertEquals('item', LsItem::objectTypeForDiscriminator(0));
        $this->assertEquals('job', LsItem::objectTypeForDiscriminator(1));
        $this->assertEquals('course', LsItem::objectTypeForDiscriminator(2));
        $this->assertEquals('assessment', LsItem::objectTypeForDiscriminator(3));
        $this->assertEquals('credential', LsItem::objectTypeForDiscriminator(4));
        $this->assertEquals('organization', LsItem::objectTypeForDiscriminator(5));
        $this->assertEquals('identifier', LsItem::objectTypeForDiscriminator(6));
        $this->assertEquals('public_key', LsItem::objectTypeForDiscriminator(7));
    }

    public function testGetObjectType()
    {
        $lsItem = new LsItem();

        $this->assertEquals('item', $lsItem->getObjectType());

        $lsItem->setDiscriminator(1);
        $this->assertEquals('job', $lsItem->getObjectType());
    }

    public function testSetAndGetHumanCodingScheme()
    {
        $lsItem = new LsItem();
        $humanCodingScheme = 'MATH.1';

        $lsItem->setHumanCodingScheme($humanCodingScheme);
        $this->assertEquals($humanCodingScheme, $lsItem->getHumanCodingScheme());
    }

    public function testSetAndGetListEnumInSource()
    {
        $lsItem = new LsItem();
        $listEnumInSource = '1.2.3';

        $lsItem->setListEnumInSource($listEnumInSource);
        $this->assertEquals($listEnumInSource, $lsItem->getListEnumInSource());
    }

    public function testSetAndGetFullStatement()
    {
        $lsItem = new LsItem();
        $fullStatement = 'This is a learning objective';

        $lsItem->setFullStatement($fullStatement);
        $this->assertEquals($fullStatement, $lsItem->getFullStatement());
    }

    public function testGetShortStatement()
    {
        $lsItem = new LsItem();

        // Test with abbreviated statement
        $lsItem->setAbbreviatedStatement('Short statement');
        $this->assertEquals('Short statement', $lsItem->getShortStatement());

        // Test without abbreviated statement
        $lsItem->setAbbreviatedStatement(null);
        $lsItem->setFullStatement('This is a very long full statement that should be truncated');
        $this->assertEquals(59, strlen($lsItem->getShortStatement()));
    }

    public function testSetAndGetAbbreviatedStatement()
    {
        $lsItem = new LsItem();
        $abbreviatedStatement = 'Short version';

        $lsItem->setAbbreviatedStatement($abbreviatedStatement);
        $this->assertEquals($abbreviatedStatement, $lsItem->getAbbreviatedStatement());
    }

    public function testSetAndGetConceptKeywordsArray()
    {
        $lsItem = new LsItem();
        $keywords = ['math', 'algebra', 'equations'];

        $lsItem->setConceptKeywordsArray($keywords);
        $this->assertEquals($keywords, $lsItem->getConceptKeywordsArray());
    }

    public function testSetAndGetConceptKeywordsString()
    {
        $lsItem = new LsItem();
        $keywordsString = 'math,algebra,equations';

        $lsItem->setConceptKeywordsString($keywordsString);
        $this->assertEquals($keywordsString, $lsItem->getConceptKeywordsString());
        $this->assertEquals(['math', 'algebra', 'equations'], $lsItem->getConceptKeywordsArray());
    }

    public function testSetAndGetNotes()
    {
        $lsItem = new LsItem();
        $notes = 'These are some notes';

        $lsItem->setNotes($notes);
        $this->assertEquals($notes, $lsItem->getNotes());
    }

    public function testSetAndGetLanguage()
    {
        $lsItem = new LsItem();
        $language = 'en';

        $lsItem->setLanguage($language);
        $this->assertEquals($language, $lsItem->getLanguage());
    }

    public function testSetAndGetEducationalAlignment()
    {
        $lsItem = new LsItem();
        $alignment = 'CCSS.Math.Content.1.OA.A.1';

        $lsItem->setEducationalAlignment($alignment);
        $this->assertEquals($alignment, $lsItem->getEducationalAlignment());
    }

    public function testSetAndGetEducationalAlignmentArray()
    {
        $lsItem = new LsItem();
        $alignments = ['CCSS.Math.Content.1.OA.A.1', 'CCSS.Math.Content.1.OA.A.2'];

        $lsItem->setEducationalAlignment($alignments);
        $this->assertEquals('CCSS.Math.Content.1.OA.A.1,CCSS.Math.Content.1.OA.A.2', $lsItem->getEducationalAlignment());
    }

    public function testSetAndGetItemType()
    {
        $lsItem = new LsItem();
        $itemType = new LsDefItemType();

        $lsItem->setItemType($itemType);
        $this->assertEquals($itemType, $lsItem->getItemType());
    }

    public function testSetAndGetItemTypeText()
    {
        $lsItem = new LsItem();
        $itemTypeText = 'Learning Objective';

        $lsItem->setItemTypeText($itemTypeText);
        $this->assertEquals($itemTypeText, $lsItem->getItemTypeText());
    }

    public function testSetAndGetAlternativeLabel()
    {
        $lsItem = new LsItem();
        $alternativeLabel = 'Alternative description';

        $lsItem->setAlternativeLabel($alternativeLabel);
        $this->assertEquals($alternativeLabel, $lsItem->getAlternativeLabel());
    }

    public function testSetAndGetStatusStart()
    {
        $lsItem = new LsItem();
        $statusStart = new \DateTime();

        $lsItem->setStatusStart($statusStart);
        $this->assertEquals($statusStart, $lsItem->getStatusStart());
    }

    public function testSetAndGetStatusEnd()
    {
        $lsItem = new LsItem();
        $statusEnd = new \DateTime();

        $lsItem->setStatusEnd($statusEnd);
        $this->assertEquals($statusEnd, $lsItem->getStatusEnd());
    }

    public function testSetAndGetLicence()
    {
        $lsItem = new LsItem();
        $licence = new LsDefLicence();

        $lsItem->setLicence($licence);
        $this->assertEquals($licence, $lsItem->getLicence());
    }

    public function testSetAndGetSubject()
    {
        $lsItem = new LsItem();
        $subject = ['Mathematics', 'Science'];

        $lsItem->setSubject($subject);
        $this->assertEquals($subject, $lsItem->getSubject());

        // Test with string subject
        $lsItem->setSubject('Mathematics');
        $this->assertEquals(['Mathematics'], $lsItem->getSubject());

        // Test with null subject
        $lsItem->setSubject(null);
        $this->assertNull($lsItem->getSubject());
    }

    public function testAddAndGetConcepts()
    {
        $lsItem = new LsItem();
        $concept = new LsDefConcept();

        $lsItem->addConcept($concept);
        $concepts = $lsItem->getConcepts();

        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $concepts);
        $this->assertTrue($concepts->contains($concept));
    }

    public function testSetConcepts()
    {
        $lsItem = new LsItem();
        $concepts = [new LsDefConcept(), new LsDefConcept()];

        $lsItem->setConcepts($concepts);
        $this->assertCount(2, $lsItem->getConcepts());
    }

    public function testAddAndGetSubjects()
    {
        $lsItem = new LsItem();
        $subject = new LsDefSubject();

        $lsItem->addSubject($subject);
        $subjects = $lsItem->getSubjects();

        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $subjects);
        $this->assertTrue($subjects->contains($subject));
    }

    public function testSetSubjects()
    {
        $lsItem = new LsItem();
        $subjects = [new LsDefSubject(), new LsDefSubject()];

        $lsItem->setSubjects($subjects);
        $this->assertCount(2, $lsItem->getSubjects());
    }

    public function testAddAndGetCriteria()
    {
        $lsItem = new LsItem();
        $rubric = new CfRubric();
        $criterion = new CfRubricCriterion($rubric);

        $lsItem->addCriterion($criterion);
        $criteria = $lsItem->getCriteria();

        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $criteria);
        $this->assertTrue($criteria->contains($criterion));
    }

    public function testSetCriteria()
    {
        $lsItem = new LsItem();
        $rubric = new CfRubric();
        $criteria = [new CfRubricCriterion($rubric), new CfRubricCriterion($rubric)];

        $lsItem->setCriteria($criteria);
        $this->assertCount(2, $lsItem->getCriteria());
    }

    public function testGetDisplayIdentifier()
    {
        $lsItem = new LsItem();

        // Test with human coding scheme
        $lsItem->setHumanCodingScheme('MATH.1');
        $this->assertEquals('MATH.1', $lsItem->getDisplayIdentifier());

        // Test with abbreviated statement
        $lsItem->setHumanCodingScheme(null);
        $lsItem->setAbbreviatedStatement('Short statement');
        $this->assertEquals('Short statement', $lsItem->getDisplayIdentifier());

        // Test with full statement
        $lsItem->setAbbreviatedStatement(null);
        $lsItem->setFullStatement('This is a full statement');
        $this->assertEquals('This is a full statement', $lsItem->getDisplayIdentifier());
    }

    public function testToString()
    {
        $lsItem = new LsItem();

        $this->assertEquals($lsItem->getUri(), (string) $lsItem);
    }

    public function testCanEdit()
    {
        $lsDoc = new LsDoc();
        $lsItem = new LsItem();
        $lsItem->setLsDoc($lsDoc);

        // Test when LsDoc can be edited
        $this->assertTrue($lsItem->canEdit());
    }

    public function testCreateItem()
    {
        $lsDoc = new LsDoc();
        $lsItem = new LsItem();
        $lsItem->setLsDoc($lsDoc);

        $newItem = $lsItem->createItem();

        $this->assertInstanceOf(LsItem::class, $newItem);
        $this->assertEquals($lsDoc, $newItem->getLsDoc());
    }

    public function testCreateAssociation()
    {
        $lsDoc = new LsDoc();
        $lsItem = new LsItem();
        $lsItem->setLsDoc($lsDoc);

        $association = $lsItem->createAssociation();

        $this->assertInstanceOf(\App\Entity\Framework\LsAssociation::class, $association);
        $this->assertEquals($lsDoc, $association->getLsDoc());
    }

    public function testClone()
    {
        $lsItem = new LsItem();
        $lsItem->setFullStatement('Original statement');
        $lsItem->setAbbreviatedStatement('Original short');

        $clonedItem = clone $lsItem;

        $this->assertInstanceOf(LsItem::class, $clonedItem);
        $this->assertNotEquals($lsItem->getIdentifier(), $clonedItem->getIdentifier());
        $this->assertEquals($lsItem->getFullStatement(), $clonedItem->getFullStatement());
        $this->assertEquals($lsItem->getAbbreviatedStatement(), $clonedItem->getShortStatement());
    }

    public function testTypesConstant()
    {
        $this->assertIsArray(LsItem::TYPES);
        $this->assertContains(0, LsItem::TYPES);
        $this->assertContains(1, LsItem::TYPES);
        $this->assertContains(7, LsItem::TYPES);
    }

    public function testDtoConstant()
    {
        $this->assertIsArray(LsItem::DTO);
        $this->assertCount(8, LsItem::DTO);
    }

    public function testConstants()
    {
        $this->assertEquals(0, LsItem::ITEM_TYPE_IDENTIFIER);
        $this->assertEquals(LsItemType::class, LsItem::ITEM_TYPE_FORM);
        $this->assertEquals('salt:type', LsItem::TYPE_KEY);
    }
}
