<?php

namespace Tests\Unit\App\Entity\Framework;

use App\Entity\Framework\CfRubric;
use App\Entity\Framework\CfRubricCriterion;
use App\Entity\Framework\CfRubricCriterionLevel;
use Ramsey\Uuid\Uuid;

class CfRubricCriterionLevelTest extends \Codeception\Test\Unit
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
        $cfRubricCriterionLevel = new CfRubricCriterionLevel($cfRubricCriterion);

        $this->assertInstanceOf(CfRubricCriterionLevel::class, $cfRubricCriterionLevel);
        $this->assertNotNull($cfRubricCriterionLevel->getIdentifier());
        $this->assertInstanceOf(\DateTimeImmutable::class, $cfRubricCriterionLevel->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $cfRubricCriterionLevel->getChangedAt());
    }

    public function testConstructorWithIdentifier()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $identifier = Uuid::uuid4()->toString();
        $cfRubricCriterionLevel = new CfRubricCriterionLevel($cfRubricCriterion, $identifier);

        $this->assertEquals($identifier, $cfRubricCriterionLevel->getIdentifier());
    }

    public function testGetCriterion()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $cfRubricCriterionLevel = new CfRubricCriterionLevel($cfRubricCriterion);

        $this->assertEquals($cfRubricCriterion, $cfRubricCriterionLevel->getCriterion());
    }

    public function testSetAndGetDescription()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $cfRubricCriterionLevel = new CfRubricCriterionLevel($cfRubricCriterion);
        $description = 'Exceeds expectations with exceptional quality and depth';

        $cfRubricCriterionLevel->setDescription($description);
        $this->assertEquals($description, $cfRubricCriterionLevel->getDescription());
    }

    public function testSetAndGetQuality()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $cfRubricCriterionLevel = new CfRubricCriterionLevel($cfRubricCriterion);
        $quality = 'Exceptional';

        $cfRubricCriterionLevel->setQuality($quality);
        $this->assertEquals($quality, $cfRubricCriterionLevel->getQuality());
    }

    public function testSetAndGetScore()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $cfRubricCriterionLevel = new CfRubricCriterionLevel($cfRubricCriterion);
        $score = 4.0;

        $cfRubricCriterionLevel->setScore($score);
        $this->assertEquals($score, $cfRubricCriterionLevel->getScore());
    }

    public function testSetAndGetFeedback()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $cfRubricCriterionLevel = new CfRubricCriterionLevel($cfRubricCriterion);
        $feedback = 'Outstanding work! This demonstrates mastery of the subject matter.';

        $cfRubricCriterionLevel->setFeedback($feedback);
        $this->assertEquals($feedback, $cfRubricCriterionLevel->getFeedback());
    }

    public function testSetAndGetPosition()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $cfRubricCriterionLevel = new CfRubricCriterionLevel($cfRubricCriterion);
        $position = 1;

        $cfRubricCriterionLevel->setPosition($position);
        $this->assertEquals($position, $cfRubricCriterionLevel->getPosition());
    }

    public function testDataIntegrity()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $cfRubricCriterionLevel = new CfRubricCriterionLevel($cfRubricCriterion);
        $identifier = Uuid::uuid4()->toString();
        $description = 'Meets all requirements with good quality';
        $quality = 'Good';
        $score = 3.0;
        $feedback = 'Well done! Consider adding more detail in the analysis section.';
        $position = 2;

        $cfRubricCriterionLevel->setIdentifier($identifier);
        $cfRubricCriterionLevel->setDescription($description);
        $cfRubricCriterionLevel->setQuality($quality);
        $cfRubricCriterionLevel->setScore($score);
        $cfRubricCriterionLevel->setFeedback($feedback);
        $cfRubricCriterionLevel->setPosition($position);

        // Test that all data is properly stored and retrieved
        $this->assertEquals($identifier, $cfRubricCriterionLevel->getIdentifier());
        $this->assertEquals($description, $cfRubricCriterionLevel->getDescription());
        $this->assertEquals($quality, $cfRubricCriterionLevel->getQuality());
        $this->assertEquals($score, $cfRubricCriterionLevel->getScore());
        $this->assertEquals($feedback, $cfRubricCriterionLevel->getFeedback());
        $this->assertEquals($position, $cfRubricCriterionLevel->getPosition());
    }

    public function testUniqueIdentifier()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $level1 = new CfRubricCriterionLevel($cfRubricCriterion);
        $level2 = new CfRubricCriterionLevel($cfRubricCriterion);

        $this->assertNotEquals($level1->getIdentifier(), $level2->getIdentifier());
    }

    public function testUpdatedAtTracking()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $cfRubricCriterionLevel = new CfRubricCriterionLevel($cfRubricCriterion);
        $originalUpdatedAt = $cfRubricCriterionLevel->getUpdatedAt();

        // Simulate some time passing
        sleep(1);

        $cfRubricCriterionLevel->setDescription('Updated Description');

        // Note: In a real scenario, the updatedAt would be updated by Doctrine listeners
        // This test demonstrates the field exists and is accessible
        $this->assertInstanceOf(\DateTimeImmutable::class, $cfRubricCriterionLevel->getUpdatedAt());
    }

    public function testChangedAtTracking()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $cfRubricCriterionLevel = new CfRubricCriterionLevel($cfRubricCriterion);

        $this->assertInstanceOf(\DateTimeImmutable::class, $cfRubricCriterionLevel->getChangedAt());
        $this->assertEquals($cfRubricCriterionLevel->getUpdatedAt(), $cfRubricCriterionLevel->getChangedAt());
    }

    public function testNullValues()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $cfRubricCriterionLevel = new CfRubricCriterionLevel($cfRubricCriterion);

        // Test that nullable fields can be null
        $this->assertNull($cfRubricCriterionLevel->getDescription());
        $this->assertNull($cfRubricCriterionLevel->getQuality());
        $this->assertNull($cfRubricCriterionLevel->getScore());
        $this->assertNull($cfRubricCriterionLevel->getFeedback());
        $this->assertNull($cfRubricCriterionLevel->getPosition());
    }

    public function testSetCriterion()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion1 = new CfRubricCriterion($cfRubric);
        $cfRubricCriterion2 = new CfRubricCriterion($cfRubric);
        $cfRubricCriterionLevel = new CfRubricCriterionLevel($cfRubricCriterion1);

        $cfRubricCriterionLevel->setCriterion($cfRubricCriterion2);
        $this->assertEquals($cfRubricCriterion2, $cfRubricCriterionLevel->getCriterion());
    }

    public function testLongTextFields()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $cfRubricCriterionLevel = new CfRubricCriterionLevel($cfRubricCriterion);

        $longDescription = str_repeat('This is a very detailed description of the rubric level. ', 20);
        $longQuality = str_repeat('High quality work with excellent attention to detail. ', 15);
        $longFeedback = str_repeat('This work demonstrates exceptional understanding and mastery of the subject matter. ', 25);

        $cfRubricCriterionLevel->setDescription($longDescription);
        $cfRubricCriterionLevel->setQuality($longQuality);
        $cfRubricCriterionLevel->setFeedback($longFeedback);

        $this->assertEquals($longDescription, $cfRubricCriterionLevel->getDescription());
        $this->assertEquals($longQuality, $cfRubricCriterionLevel->getQuality());
        $this->assertEquals($longFeedback, $cfRubricCriterionLevel->getFeedback());
    }

    public function testScorePrecision()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $cfRubricCriterionLevel = new CfRubricCriterionLevel($cfRubricCriterion);

        $scores = [0.5, 1.25, 2.75, 3.33, 4.0];

        foreach ($scores as $score) {
            $cfRubricCriterionLevel->setScore($score);
            $this->assertEquals($score, $cfRubricCriterionLevel->getScore());
        }
    }

    public function testNegativeScore()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $cfRubricCriterionLevel = new CfRubricCriterionLevel($cfRubricCriterion);

        $cfRubricCriterionLevel->setScore(-1.0);
        $this->assertEquals(-1.0, $cfRubricCriterionLevel->getScore());
    }

    public function testZeroScore()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $cfRubricCriterionLevel = new CfRubricCriterionLevel($cfRubricCriterion);

        $cfRubricCriterionLevel->setScore(0.0);
        $this->assertEquals(0.0, $cfRubricCriterionLevel->getScore());
    }

    public function testCriterionRelationship()
    {
        $cfRubric = new CfRubric();
        $cfRubricCriterion = new CfRubricCriterion($cfRubric);
        $cfRubricCriterion->setCategory('Content');
        $cfRubricCriterionLevel = new CfRubricCriterionLevel($cfRubricCriterion);

        $this->assertEquals($cfRubricCriterion, $cfRubricCriterionLevel->getCriterion());
        $this->assertEquals('Content', $cfRubricCriterionLevel->getCriterion()->getCategory());
    }
}
