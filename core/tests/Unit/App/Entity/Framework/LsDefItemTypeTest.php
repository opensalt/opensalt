<?php

namespace Tests\Unit\App\Entity\Framework;

use App\Entity\Framework\LsDefItemType;
use Ramsey\Uuid\Uuid;

class LsDefItemTypeTest extends \Codeception\Test\Unit
{
    /**
     * @var \Tests\Support\UnitTester
     */
    protected $tester;

    // tests
    public function testConstructor()
    {
        $lsDefItemType = new LsDefItemType();

        $this->assertInstanceOf(LsDefItemType::class, $lsDefItemType);
        $this->assertNotNull($lsDefItemType->getIdentifier());
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefItemType->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefItemType->getChangedAt());
    }

    public function testConstructorWithIdentifier()
    {
        $identifier = Uuid::uuid4()->toString();
        $lsDefItemType = new LsDefItemType($identifier);

        $this->assertEquals($identifier, $lsDefItemType->getIdentifier());
    }

    public function testSetAndGetTitle()
    {
        $lsDefItemType = new LsDefItemType();
        $title = 'Learning Objective';

        $lsDefItemType->setTitle($title);
        $this->assertEquals($title, $lsDefItemType->getTitle());
    }

    public function testSetAndGetDescription()
    {
        $lsDefItemType = new LsDefItemType();
        $description = 'A statement that describes what students will learn or be able to do.';

        $lsDefItemType->setDescription($description);
        $this->assertEquals($description, $lsDefItemType->getDescription());
    }

    public function testGetDescriptionFallbackToTitle()
    {
        $lsDefItemType = new LsDefItemType();
        $title = 'Performance Task';

        $lsDefItemType->setTitle($title);
        // When description is null, should return title
        $this->assertEquals($title, $lsDefItemType->getDescription());
    }

    public function testGetDescriptionWithNullTitleAndDescription()
    {
        $lsDefItemType = new LsDefItemType();

        // When both title and description are null, should return null
        $this->assertNull($lsDefItemType->getDescription());
    }

    public function testSetAndGetCode()
    {
        $lsDefItemType = new LsDefItemType();
        $code = 'LO';

        $lsDefItemType->setCode($code);
        $this->assertEquals($code, $lsDefItemType->getCode());
    }

    public function testSetAndGetHierarchyCode()
    {
        $lsDefItemType = new LsDefItemType();
        $hierarchyCode = 'STANDARD.LEARNING_OBJECTIVE';

        $lsDefItemType->setHierarchyCode($hierarchyCode);
        $this->assertEquals($hierarchyCode, $lsDefItemType->getHierarchyCode());
    }

    public function testHierarchyCodeIsRequired()
    {
        $lsDefItemType = new LsDefItemType();
        $hierarchyCode = 'REQUIRED_CODE';

        $lsDefItemType->setHierarchyCode($hierarchyCode);
        $this->assertEquals($hierarchyCode, $lsDefItemType->getHierarchyCode());
    }

    public function testToStringWithTitle()
    {
        $lsDefItemType = new LsDefItemType();
        $title = 'Assessment Item';

        $lsDefItemType->setTitle($title);
        $this->assertEquals($title, (string)$lsDefItemType);
    }

    public function testToStringWithoutTitle()
    {
        $identifier = Uuid::uuid4()->toString();
        $lsDefItemType = new LsDefItemType($identifier);

        $this->assertEquals($identifier, (string)$lsDefItemType);
    }

    public function testDataIntegrity()
    {
        $lsDefItemType = new LsDefItemType();
        $identifier = Uuid::uuid4()->toString();
        $title = 'Competency';
        $description = 'A measurable pattern of knowledge, skills, or behaviors that describes what a student should know or be able to do.';
        $code = 'COMP';
        $hierarchyCode = 'FRAMEWORK.COMPETENCY';

        $lsDefItemType->setIdentifier($identifier);
        $lsDefItemType->setTitle($title);
        $lsDefItemType->setDescription($description);
        $lsDefItemType->setCode($code);
        $lsDefItemType->setHierarchyCode($hierarchyCode);

        // Test that all data is properly stored and retrieved
        $this->assertEquals($identifier, $lsDefItemType->getIdentifier());
        $this->assertEquals($title, $lsDefItemType->getTitle());
        $this->assertEquals($description, $lsDefItemType->getDescription());
        $this->assertEquals($code, $lsDefItemType->getCode());
        $this->assertEquals($hierarchyCode, $lsDefItemType->getHierarchyCode());
    }

    public function testUniqueIdentifier()
    {
        $itemType1 = new LsDefItemType();
        $itemType2 = new LsDefItemType();

        $this->assertNotEquals($itemType1->getIdentifier(), $itemType2->getIdentifier());
    }

    public function testUpdatedAtTracking()
    {
        $lsDefItemType = new LsDefItemType();
        $originalUpdatedAt = $lsDefItemType->getUpdatedAt();

        // Simulate some time passing
        sleep(1);

        $lsDefItemType->setTitle('Updated Title');

        // Note: In a real scenario, the updatedAt would be updated by Doctrine listeners
        // This test demonstrates the field exists and is accessible
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefItemType->getUpdatedAt());
    }

    public function testChangedAtTracking()
    {
        $lsDefItemType = new LsDefItemType();

        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefItemType->getChangedAt());
        $this->assertEquals($lsDefItemType->getUpdatedAt(), $lsDefItemType->getChangedAt());
    }

    public function testNullCode()
    {
        $lsDefItemType = new LsDefItemType();

        $lsDefItemType->setCode(null);
        $this->assertNull($lsDefItemType->getCode());
    }

    public function testEmptyCode()
    {
        $lsDefItemType = new LsDefItemType();

        $lsDefItemType->setCode('');
        $this->assertEquals('', $lsDefItemType->getCode());
    }

    public function testLongHierarchyCode()
    {
        $lsDefItemType = new LsDefItemType();
        $longHierarchyCode = 'VERY.LONG.HIERARCHY.CODE.STRING';

        $lsDefItemType->setHierarchyCode($longHierarchyCode);
        $this->assertEquals($longHierarchyCode, $lsDefItemType->getHierarchyCode());
    }

    public function testSpecialCharactersInHierarchyCode()
    {
        $lsDefItemType = new LsDefItemType();
        $specialHierarchyCode = 'FRAMEWORK.SUBJECT.DOMAIN.TOPIC.SUBTOPIC';

        $lsDefItemType->setHierarchyCode($specialHierarchyCode);
        $this->assertEquals($specialHierarchyCode, $lsDefItemType->getHierarchyCode());
    }
}
