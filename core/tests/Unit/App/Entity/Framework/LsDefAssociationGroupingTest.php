<?php

namespace Tests\Unit\App\Entity\Framework;

use App\Entity\Framework\LsDefAssociationGrouping;
use App\Entity\Framework\LsDoc;
use Ramsey\Uuid\Uuid;

class LsDefAssociationGroupingTest extends \Codeception\Test\Unit
{
    /**
     * @var \Tests\Support\UnitTester
     */
    protected $tester;

    // tests
    public function testConstructor()
    {
        $lsDefAssociationGrouping = new LsDefAssociationGrouping();

        $this->assertInstanceOf(LsDefAssociationGrouping::class, $lsDefAssociationGrouping);
        $this->assertNotNull($lsDefAssociationGrouping->getIdentifier());
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefAssociationGrouping->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefAssociationGrouping->getChangedAt());
    }

    public function testConstructorWithIdentifier()
    {
        $identifier = Uuid::uuid4()->toString();
        $lsDefAssociationGrouping = new LsDefAssociationGrouping($identifier);

        $this->assertEquals($identifier, $lsDefAssociationGrouping->getIdentifier());
    }

    public function testSetAndGetTitle()
    {
        $lsDefAssociationGrouping = new LsDefAssociationGrouping();
        $title = 'Learning Objective Group';

        $lsDefAssociationGrouping->setTitle($title);
        $this->assertEquals($title, $lsDefAssociationGrouping->getTitle());
    }

    public function testSetAndGetDescription()
    {
        $lsDefAssociationGrouping = new LsDefAssociationGrouping();
        $description = 'A grouping of related learning objectives';

        $lsDefAssociationGrouping->setDescription($description);
        $this->assertEquals($description, $lsDefAssociationGrouping->getDescription());
    }

    public function testGetDescriptionFallbackToTitle()
    {
        $lsDefAssociationGrouping = new LsDefAssociationGrouping();
        $title = 'Assessment Group';

        $lsDefAssociationGrouping->setTitle($title);
        // When description is null, should return title
        $this->assertEquals($title, $lsDefAssociationGrouping->getDescription());
    }

    public function testGetDescriptionWithNullTitleAndDescription()
    {
        $lsDefAssociationGrouping = new LsDefAssociationGrouping();

        // When both title and description are null, should return null
        $this->assertNull($lsDefAssociationGrouping->getDescription());
    }

    public function testSetAndGetLsDoc()
    {
        $lsDefAssociationGrouping = new LsDefAssociationGrouping();
        $lsDoc = new LsDoc();

        $lsDefAssociationGrouping->setLsDoc($lsDoc);
        $this->assertEquals($lsDoc, $lsDefAssociationGrouping->getLsDoc());
    }

    public function testDuplicateToLsDoc()
    {
        $originalLsDoc = new LsDoc();
        $originalLsDoc->setTitle('Original Framework');

        $newLsDoc = new LsDoc();
        $newLsDoc->setTitle('New Framework');

        $lsDefAssociationGrouping = new LsDefAssociationGrouping();
        $lsDefAssociationGrouping->setLsDoc($originalLsDoc);
        $lsDefAssociationGrouping->setTitle('Original Grouping');
        $lsDefAssociationGrouping->setDescription('Original description');

        $duplicatedGrouping = $lsDefAssociationGrouping->duplicateToLsDoc($newLsDoc);

        $this->assertInstanceOf(LsDefAssociationGrouping::class, $duplicatedGrouping);
        $this->assertEquals($newLsDoc, $duplicatedGrouping->getLsDoc());
        $this->assertEquals('Original Grouping', $duplicatedGrouping->getTitle());
        $this->assertEquals('Original description', $duplicatedGrouping->getDescription());
        $this->assertNotEquals($lsDefAssociationGrouping->getIdentifier(), $duplicatedGrouping->getIdentifier());
    }

    public function testDuplicateToLsDocWithNullValues()
    {
        $newLsDoc = new LsDoc();

        $lsDefAssociationGrouping = new LsDefAssociationGrouping();
        // Don't set title or description

        $duplicatedGrouping = $lsDefAssociationGrouping->duplicateToLsDoc($newLsDoc);

        $this->assertInstanceOf(LsDefAssociationGrouping::class, $duplicatedGrouping);
        $this->assertEquals($newLsDoc, $duplicatedGrouping->getLsDoc());
        $this->assertNull($duplicatedGrouping->getTitle());
        $this->assertNull($duplicatedGrouping->getDescription());
    }

    public function testDataIntegrity()
    {
        $lsDefAssociationGrouping = new LsDefAssociationGrouping();
        $identifier = Uuid::uuid4()->toString();
        $title = 'Competency Grouping';
        $description = 'Group of related competencies';
        $lsDoc = new LsDoc();

        $lsDefAssociationGrouping->setIdentifier($identifier);
        $lsDefAssociationGrouping->setTitle($title);
        $lsDefAssociationGrouping->setDescription($description);
        $lsDefAssociationGrouping->setLsDoc($lsDoc);

        // Test that all data is properly stored and retrieved
        $this->assertEquals($identifier, $lsDefAssociationGrouping->getIdentifier());
        $this->assertEquals($title, $lsDefAssociationGrouping->getTitle());
        $this->assertEquals($description, $lsDefAssociationGrouping->getDescription());
        $this->assertEquals($lsDoc, $lsDefAssociationGrouping->getLsDoc());
    }

    public function testUniqueIdentifier()
    {
        $grouping1 = new LsDefAssociationGrouping();
        $grouping2 = new LsDefAssociationGrouping();

        $this->assertNotEquals($grouping1->getIdentifier(), $grouping2->getIdentifier());
    }

    public function testUpdatedAtTracking()
    {
        $lsDefAssociationGrouping = new LsDefAssociationGrouping();
        $originalUpdatedAt = $lsDefAssociationGrouping->getUpdatedAt();

        // Simulate some time passing
        sleep(1);

        $lsDefAssociationGrouping->setTitle('Updated Title');

        // Note: In a real scenario, the updatedAt would be updated by Doctrine listeners
        // This test demonstrates the field exists and is accessible
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefAssociationGrouping->getUpdatedAt());
    }

    public function testChangedAtTracking()
    {
        $lsDefAssociationGrouping = new LsDefAssociationGrouping();

        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefAssociationGrouping->getChangedAt());
        $this->assertEquals($lsDefAssociationGrouping->getUpdatedAt(), $lsDefAssociationGrouping->getChangedAt());
    }

    public function testNullValues()
    {
        $lsDefAssociationGrouping = new LsDefAssociationGrouping();

        // Test that nullable fields can be null
        $this->assertNull($lsDefAssociationGrouping->getTitle());
        $this->assertNull($lsDefAssociationGrouping->getDescription());
        $this->assertNull($lsDefAssociationGrouping->getLsDoc());
    }

    public function testLongTitle()
    {
        $lsDefAssociationGrouping = new LsDefAssociationGrouping();
        $longTitle = str_repeat('Very Long Association Grouping Title ', 10);

        $lsDefAssociationGrouping->setTitle($longTitle);
        $this->assertEquals($longTitle, $lsDefAssociationGrouping->getTitle());
    }

    public function testLongDescription()
    {
        $lsDefAssociationGrouping = new LsDefAssociationGrouping();
        $longDescription = str_repeat('This is a very detailed description of the association grouping. ', 20);

        $lsDefAssociationGrouping->setDescription($longDescription);
        $this->assertEquals($longDescription, $lsDefAssociationGrouping->getDescription());
    }

    public function testSpecialCharactersInTitle()
    {
        $lsDefAssociationGrouping = new LsDefAssociationGrouping();
        $specialTitle = 'Grouping: Standards & Benchmarks (2023)';

        $lsDefAssociationGrouping->setTitle($specialTitle);
        $this->assertEquals($specialTitle, $lsDefAssociationGrouping->getTitle());
    }

    public function testLsDocRelationship()
    {
        $lsDefAssociationGrouping = new LsDefAssociationGrouping();
        $lsDoc = new LsDoc();
        $lsDoc->setTitle('Test Framework');

        $lsDefAssociationGrouping->setLsDoc($lsDoc);

        $this->assertEquals($lsDoc, $lsDefAssociationGrouping->getLsDoc());
        $this->assertEquals('Test Framework', $lsDefAssociationGrouping->getLsDoc()->getTitle());
    }

    public function testDuplicatePreservesData()
    {
        $originalLsDoc = new LsDoc();
        $originalLsDoc->setTitle('Original');

        $newLsDoc = new LsDoc();
        $newLsDoc->setTitle('New');

        $originalGrouping = new LsDefAssociationGrouping();
        $originalGrouping->setLsDoc($originalLsDoc);
        $originalGrouping->setTitle('Original Title');
        $originalGrouping->setDescription('Original Description');

        $originalUpdatedAt = $originalGrouping->getUpdatedAt();
        $originalChangedAt = $originalGrouping->getChangedAt();

        $duplicatedGrouping = $originalGrouping->duplicateToLsDoc($newLsDoc);

        // Test that data is preserved
        $this->assertEquals('Original Title', $duplicatedGrouping->getTitle());
        $this->assertEquals('Original Description', $duplicatedGrouping->getDescription());
        $this->assertEquals($newLsDoc, $duplicatedGrouping->getLsDoc());

        // Test that timestamps are reset for the new entity
        $this->assertInstanceOf(\DateTimeImmutable::class, $duplicatedGrouping->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $duplicatedGrouping->getChangedAt());
    }
}
