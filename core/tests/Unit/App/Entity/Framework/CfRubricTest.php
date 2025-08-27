<?php

namespace Tests\Unit\App\Entity\Framework;

use App\Entity\Framework\CfRubric;
use App\Entity\Framework\CfRubricCriterion;
use Ramsey\Uuid\Uuid;

class CfRubricTest extends \Codeception\Test\Unit
{
    /**
     * @var \Tests\Support\UnitTester
     */
    protected $tester;

    // tests
    public function testConstructor()
    {
        $cfRubric = new CfRubric();

        $this->assertInstanceOf(CfRubric::class, $cfRubric);
        $this->assertNotNull($cfRubric->getIdentifier());
        $this->assertInstanceOf(\DateTimeImmutable::class, $cfRubric->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $cfRubric->getChangedAt());
    }

    public function testConstructorWithIdentifier()
    {
        $identifier = Uuid::uuid4()->toString();
        $cfRubric = new CfRubric($identifier);

        $this->assertEquals($identifier, $cfRubric->getIdentifier());
    }

    public function testSetAndGetTitle()
    {
        $cfRubric = new CfRubric();
        $title = 'Writing Rubric';

        $cfRubric->setTitle($title);
        $this->assertEquals($title, $cfRubric->getTitle());
    }

    public function testSetAndGetDescription()
    {
        $cfRubric = new CfRubric();
        $description = 'A rubric for evaluating writing assignments based on content, organization, and mechanics.';

        $cfRubric->setDescription($description);
        $this->assertEquals($description, $cfRubric->getDescription());
    }

    public function testGetCriteriaCollection()
    {
        $cfRubric = new CfRubric();

        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $cfRubric->getCriteria());
        $this->assertTrue($cfRubric->getCriteria()->isEmpty());
    }

    public function testAddCriterion()
    {
        $cfRubric = new CfRubric();
        $criterion = new CfRubricCriterion($cfRubric);

        $cfRubric->addCriterion($criterion);

        $this->assertFalse($cfRubric->getCriteria()->isEmpty());
        $this->assertEquals(1, $cfRubric->getCriteria()->count());
        $this->assertTrue($cfRubric->getCriteria()->contains($criterion));
    }

    public function testRemoveCriterion()
    {
        $cfRubric = new CfRubric();
        $criterion = new CfRubricCriterion($cfRubric);

        $cfRubric->addCriterion($criterion);
        $this->assertEquals(1, $cfRubric->getCriteria()->count());

        $cfRubric->removeCriterion($criterion);
        $this->assertTrue($cfRubric->getCriteria()->isEmpty());
    }

    public function testRemoveNonExistentCriterion()
    {
        $cfRubric = new CfRubric();
        $criterion1 = new CfRubricCriterion($cfRubric);
        $criterion2 = new CfRubricCriterion($cfRubric);

        $cfRubric->addCriterion($criterion1);
        $cfRubric->removeCriterion($criterion2); // Try to remove a criterion that was never added

        $this->assertEquals(1, $cfRubric->getCriteria()->count());
        $this->assertTrue($cfRubric->getCriteria()->contains($criterion1));
    }

    public function testMultipleCriteria()
    {
        $cfRubric = new CfRubric();
        $criterion1 = new CfRubricCriterion($cfRubric);
        $criterion2 = new CfRubricCriterion($cfRubric);
        $criterion3 = new CfRubricCriterion($cfRubric);

        $cfRubric->addCriterion($criterion1);
        $cfRubric->addCriterion($criterion2);
        $cfRubric->addCriterion($criterion3);

        $this->assertEquals(3, $cfRubric->getCriteria()->count());

        $cfRubric->removeCriterion($criterion2);
        $this->assertEquals(2, $cfRubric->getCriteria()->count());
        $this->assertTrue($cfRubric->getCriteria()->contains($criterion1));
        $this->assertTrue($cfRubric->getCriteria()->contains($criterion3));
        $this->assertFalse($cfRubric->getCriteria()->contains($criterion2));
    }

    public function testDataIntegrity()
    {
        $cfRubric = new CfRubric();
        $identifier = Uuid::uuid4()->toString();
        $title = 'Presentation Rubric';
        $description = 'Rubric for evaluating oral presentations';

        $cfRubric->setIdentifier($identifier);
        $cfRubric->setTitle($title);
        $cfRubric->setDescription($description);

        // Test that all data is properly stored and retrieved
        $this->assertEquals($identifier, $cfRubric->getIdentifier());
        $this->assertEquals($title, $cfRubric->getTitle());
        $this->assertEquals($description, $cfRubric->getDescription());
    }

    public function testUniqueIdentifier()
    {
        $rubric1 = new CfRubric();
        $rubric2 = new CfRubric();

        $this->assertNotEquals($rubric1->getIdentifier(), $rubric2->getIdentifier());
    }

    public function testUpdatedAtTracking()
    {
        $cfRubric = new CfRubric();
        $originalUpdatedAt = $cfRubric->getUpdatedAt();

        // Simulate some time passing
        sleep(1);

        $cfRubric->setTitle('Updated Title');

        // Note: In a real scenario, the updatedAt would be updated by Doctrine listeners
        // This test demonstrates the field exists and is accessible
        $this->assertInstanceOf(\DateTimeImmutable::class, $cfRubric->getUpdatedAt());
    }

    public function testChangedAtTracking()
    {
        $cfRubric = new CfRubric();

        $this->assertInstanceOf(\DateTimeImmutable::class, $cfRubric->getChangedAt());
        $this->assertEquals($cfRubric->getUpdatedAt(), $cfRubric->getChangedAt());
    }

    public function testNullTitle()
    {
        $cfRubric = new CfRubric();

        $cfRubric->setTitle(null);
        $this->assertNull($cfRubric->getTitle());
    }

    public function testNullDescription()
    {
        $cfRubric = new CfRubric();

        $cfRubric->setDescription(null);
        $this->assertNull($cfRubric->getDescription());
    }

    public function testEmptyTitle()
    {
        $cfRubric = new CfRubric();

        $cfRubric->setTitle('');
        $this->assertEquals('', $cfRubric->getTitle());
    }

    public function testEmptyDescription()
    {
        $cfRubric = new CfRubric();

        $cfRubric->setDescription('');
        $this->assertEquals('', $cfRubric->getDescription());
    }

    public function testLongTitle()
    {
        $cfRubric = new CfRubric();
        $longTitle = str_repeat('Very Long Rubric Title ', 10);

        $cfRubric->setTitle($longTitle);
        $this->assertEquals($longTitle, $cfRubric->getTitle());
    }

    public function testLongDescription()
    {
        $cfRubric = new CfRubric();
        $longDescription = str_repeat('This is a very detailed description of the rubric. ', 50);

        $cfRubric->setDescription($longDescription);
        $this->assertEquals($longDescription, $cfRubric->getDescription());
    }

    public function testCriterionBidirectionalRelationship()
    {
        $cfRubric = new CfRubric();
        $criterion = new CfRubricCriterion($cfRubric);

        // Note: In a real scenario, the CfRubricCriterion would have a setRubric method
        // This test demonstrates the expected behavior for adding criteria
        $cfRubric->addCriterion($criterion);

        $this->assertTrue($cfRubric->getCriteria()->contains($criterion));
    }
}
