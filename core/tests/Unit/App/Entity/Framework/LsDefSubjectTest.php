<?php

namespace Tests\Unit\App\Entity\Framework;

use App\Entity\Framework\LsDefSubject;
use Ramsey\Uuid\Uuid;

class LsDefSubjectTest extends \Codeception\Test\Unit
{
    /**
     * @var \Tests\Support\UnitTester
     */
    protected $tester;

    // tests
    public function testConstructor()
    {
        $lsDefSubject = new LsDefSubject();

        $this->assertInstanceOf(LsDefSubject::class, $lsDefSubject);
        $this->assertNotNull($lsDefSubject->getIdentifier());
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefSubject->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefSubject->getChangedAt());
    }

    public function testConstructorWithIdentifier()
    {
        $identifier = Uuid::uuid4()->toString();
        $lsDefSubject = new LsDefSubject($identifier);

        $this->assertEquals($identifier, $lsDefSubject->getIdentifier());
    }

    public function testSetAndGetTitle()
    {
        $lsDefSubject = new LsDefSubject();
        $title = 'Mathematics';

        $lsDefSubject->setTitle($title);
        $this->assertEquals($title, $lsDefSubject->getTitle());
    }

    public function testSetAndGetDescription()
    {
        $lsDefSubject = new LsDefSubject();
        $description = 'The study of numbers, quantities, and shapes';

        $lsDefSubject->setDescription($description);
        $this->assertEquals($description, $lsDefSubject->getDescription());
    }

    public function testGetDescriptionFallbackToTitle()
    {
        $lsDefSubject = new LsDefSubject();
        $title = 'Mathematics';

        $lsDefSubject->setTitle($title);
        // When description is null, should return title
        $this->assertEquals($title, $lsDefSubject->getDescription());
    }

    public function testGetDescriptionWithNullTitleAndDescription()
    {
        $lsDefSubject = new LsDefSubject();

        // When both title and description are null, should return null
        $this->assertNull($lsDefSubject->getDescription());
    }

    public function testSetAndGetHierarchyCode()
    {
        $lsDefSubject = new LsDefSubject();
        $hierarchyCode = 'MATH';

        $lsDefSubject->setHierarchyCode($hierarchyCode);
        $this->assertEquals($hierarchyCode, $lsDefSubject->getHierarchyCode());
    }

    public function testToStringWithTitle()
    {
        $lsDefSubject = new LsDefSubject();
        $title = 'Mathematics';

        $lsDefSubject->setTitle($title);
        $this->assertEquals($title, (string) $lsDefSubject);
    }

    public function testToStringWithoutTitle()
    {
        $identifier = Uuid::uuid4()->toString();
        $lsDefSubject = new LsDefSubject($identifier);

        $this->assertEquals('Subject: ' . $identifier, (string) $lsDefSubject);
    }

    public function testDataIntegrity()
    {
        $lsDefSubject = new LsDefSubject();
        $identifier = Uuid::uuid4()->toString();
        $title = 'Science';
        $description = 'The study of the natural world';
        $hierarchyCode = 'SCI';

        $lsDefSubject->setIdentifier($identifier);
        $lsDefSubject->setTitle($title);
        $lsDefSubject->setDescription($description);
        $lsDefSubject->setHierarchyCode($hierarchyCode);

        // Test that all data is properly stored and retrieved
        $this->assertEquals($identifier, $lsDefSubject->getIdentifier());
        $this->assertEquals($title, $lsDefSubject->getTitle());
        $this->assertEquals($description, $lsDefSubject->getDescription());
        $this->assertEquals($hierarchyCode, $lsDefSubject->getHierarchyCode());
    }

    public function testUniqueIdentifier()
    {
        $subject1 = new LsDefSubject();
        $subject2 = new LsDefSubject();

        $this->assertNotEquals($subject1->getIdentifier(), $subject2->getIdentifier());
    }

    public function testUpdatedAtTracking()
    {
        $lsDefSubject = new LsDefSubject();
        $originalUpdatedAt = $lsDefSubject->getUpdatedAt();

        // Simulate some time passing
        sleep(1);

        $lsDefSubject->setTitle('Updated Title');

        // Note: In a real scenario, the updatedAt would be updated by Doctrine listeners
        // This test demonstrates the field exists and is accessible
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefSubject->getUpdatedAt());
    }

    public function testChangedAtTracking()
    {
        $lsDefSubject = new LsDefSubject();

        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefSubject->getChangedAt());
        $this->assertEquals($lsDefSubject->getUpdatedAt(), $lsDefSubject->getChangedAt());
    }
}
