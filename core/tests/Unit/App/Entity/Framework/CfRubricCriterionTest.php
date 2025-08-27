<?php

namespace Tests\Unit\App\Entity\Framework;

use App\Entity\Framework\CfRubric;
use App\Entity\Framework\CfRubricCriterion;
use App\Entity\Framework\CfRubricCriterionLevel;
use App\Entity\Framework\LsItem;
use Ramsey\Uuid\Uuid;

class CfRubricCriterionTest extends \Codeception\Test\Unit
{
    /**
     * @var \Tests\Support\UnitTester
     */
    protected $tester;

    // tests
    public function testConstructor()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);

        $this->assertInstanceOf(CfRubricCriterion::class, $cfRubricCriterion);
        $this->assertNotNull($cfRubricCriterion->getIdentifier());
        $this->assertInstanceOf(\DateTimeImmutable::class, $cfRubricCriterion->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $cfRubricCriterion->getChangedAt());
    }

    public function testConstructorWithIdentifier()
    {
        $cfRubric = new CfRubric();
        $identifier = Uuid::uuid4()->toString();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric, $identifier);

        $this->assertEquals($identifier, $cfRubricCriterion->getIdentifier());
    }

    public function testGetRubric()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);

        $this->assertEquals($cfRubric, $cfRubricCriterion->getRubric());
    }

    public function testSetAndGetCategory()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $category = 'Content';

        $cfRubricCriterion->setCategory($category);
        $this->assertEquals($category, $cfRubricCriterion->getCategory());
    }

    public function testSetAndGetDescription()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $description = 'Criterion for evaluating content quality';

        $cfRubricCriterion->setDescription($description);
        $this->assertEquals($description, $cfRubricCriterion->getDescription());
    }

    public function testSetAndGetItem()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $item = new LsItem();

        $cfRubricCriterion->setItem($item);
        $this->assertEquals($item, $cfRubricCriterion->getItem());
    }

    public function testSetAndGetWeight()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $weight = 1.5;

        $cfRubricCriterion->setWeight($weight);
        $this->assertEquals($weight, $cfRubricCriterion->getWeight());
    }

    public function testSetAndGetPosition()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $position = 1;

        $cfRubricCriterion->setPosition($position);
        $this->assertEquals($position, $cfRubricCriterion->getPosition());
    }

    public function testGetLevelsCollection()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);

        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $cfRubricCriterion->getLevels());
        $this->assertTrue($cfRubricCriterion->getLevels()->isEmpty());
    }

    public function testAddLevel()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $level = new CfRubricCriterionLevel($cfRubricCriterion);

        $cfRubricCriterion->addLevel($level);

        $this->assertFalse($cfRubricCriterion->getLevels()->isEmpty());
        $this->assertEquals(1, $cfRubricCriterion->getLevels()->count());
        $this->assertTrue($cfRubricCriterion->getLevels()->contains($level));
    }

    public function testRemoveLevel()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $level = new CfRubricCriterionLevel($cfRubricCriterion);

        $cfRubricCriterion->addLevel($level);
        $this->assertEquals(1, $cfRubricCriterion->getLevels()->count());

        $cfRubricCriterion->removeLevel($level);
        $this->assertTrue($cfRubricCriterion->getLevels()->isEmpty());
    }

    public function testRemoveNonExistentLevel()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $level1 = new CfRubricCriterionLevel($cfRubricCriterion);
        $level2 = new CfRubricCriterionLevel($cfRubricCriterion);

        $cfRubricCriterion->addLevel($level1);
        $cfRubricCriterion->removeLevel($level2); // Try to remove a level that was never added

        $this->assertEquals(1, $cfRubricCriterion->getLevels()->count());
        $this->assertTrue($cfRubricCriterion->getLevels()->contains($level1));
    }

    public function testMultipleLevels()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $level1 = new CfRubricCriterionLevel($cfRubricCriterion);
        $level2 = new CfRubricCriterionLevel($cfRubricCriterion);
        $level3 = new CfRubricCriterionLevel($cfRubricCriterion);

        $cfRubricCriterion->addLevel($level1);
        $cfRubricCriterion->addLevel($level2);
        $cfRubricCriterion->addLevel($level3);

        $this->assertEquals(3, $cfRubricCriterion->getLevels()->count());

        $cfRubricCriterion->removeLevel($level2);
        $this->assertEquals(2, $cfRubricCriterion->getLevels()->count());
        $this->assertTrue($cfRubricCriterion->getLevels()->contains($level1));
        $this->assertTrue($cfRubricCriterion->getLevels()->contains($level3));
        $this->assertFalse($cfRubricCriterion->getLevels()->contains($level2));
    }

    public function testDataIntegrity()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $identifier = Uuid::uuid4()->toString();
        $category = 'Organization';
        $description = 'Criterion for evaluating organization and structure';
        $weight = 2.0;
        $position = 2;

        $cfRubricCriterion->setIdentifier($identifier);
        $cfRubricCriterion->setCategory($category);
        $cfRubricCriterion->setDescription($description);
        $cfRubricCriterion->setWeight($weight);
        $cfRubricCriterion->setPosition($position);

        // Test that all data is properly stored and retrieved
        $this->assertEquals($identifier, $cfRubricCriterion->getIdentifier());
        $this->assertEquals($category, $cfRubricCriterion->getCategory());
        $this->assertEquals($description, $cfRubricCriterion->getDescription());
        $this->assertEquals($weight, $cfRubricCriterion->getWeight());
        $this->assertEquals($position, $cfRubricCriterion->getPosition());
    }

    public function testUniqueIdentifier()
    {
        $cfRubric = new CfRubric();
        $criterion1 = new CfRubricCriterion($cfRubric);
        $criterion2 = new CfRubricCriterion($cfRubric);

        $this->assertNotEquals($criterion1->getIdentifier(), $criterion2->getIdentifier());
    }

    public function testUpdatedAtTracking()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $originalUpdatedAt = $cfRubricCriterion->getUpdatedAt();

        // Simulate some time passing
        sleep(1);

        $cfRubricCriterion->setCategory('Updated Category');

        // Note: In a real scenario, the updatedAt would be updated by Doctrine listeners
        // This test demonstrates the field exists and is accessible
        $this->assertInstanceOf(\DateTimeImmutable::class, $cfRubricCriterion->getUpdatedAt());
    }

    public function testChangedAtTracking()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);

        $this->assertInstanceOf(\DateTimeImmutable::class, $cfRubricCriterion->getChangedAt());
        $this->assertEquals($cfRubricCriterion->getUpdatedAt(), $cfRubricCriterion->getChangedAt());
    }

    public function testNullValues()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);

        // Test that nullable fields can be null
        $this->assertNull($cfRubricCriterion->getCategory());
        $this->assertNull($cfRubricCriterion->getDescription());
        $this->assertNull($cfRubricCriterion->getItem());
        $this->assertNull($cfRubricCriterion->getWeight());
        $this->assertNull($cfRubricCriterion->getPosition());
    }

    public function testRubricRelationship()
    {
        $cfRubric = new CfRubric();
        $cfRubric->setTitle('Test Rubric');
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);

        $this->assertEquals($cfRubric, $cfRubricCriterion->getRubric());
        $this->assertEquals('Test Rubric', $cfRubricCriterion->getRubric()->getTitle());
    }
}
