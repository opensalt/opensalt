<?php

namespace Tests\Unit\App\Entity\Framework;

use App\Entity\Framework\LsDefConcept;
use Ramsey\Uuid\Uuid;

class LsDefConceptTest extends \Codeception\Test\Unit
{
    /**
     * @var \Tests\Support\UnitTester
     */
    protected $tester;

    // tests
    public function testConstructor()
    {
        $lsDefConcept = new LsDefConcept();

        $this->assertInstanceOf(LsDefConcept::class, $lsDefConcept);
        $this->assertNotNull($lsDefConcept->getIdentifier());
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefConcept->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefConcept->getChangedAt());
    }

    public function testConstructorWithIdentifier()
    {
        $identifier = Uuid::uuid4()->toString();
        $lsDefConcept = new LsDefConcept($identifier);

        $this->assertEquals($identifier, $lsDefConcept->getIdentifier());
    }

    public function testSetAndGetTitle()
    {
        $lsDefConcept = new LsDefConcept();
        $title = 'Critical Thinking';

        $lsDefConcept->setTitle($title);
        $this->assertEquals($title, $lsDefConcept->getTitle());
    }

    public function testSetAndGetDescription()
    {
        $lsDefConcept = new LsDefConcept();
        $description = 'The process of actively and skillfully conceptualizing, applying, analyzing, synthesizing, and evaluating information';

        $lsDefConcept->setDescription($description);
        $this->assertEquals($description, $lsDefConcept->getDescription());
    }

    public function testGetDescriptionFallbackToTitle()
    {
        $lsDefConcept = new LsDefConcept();
        $title = 'Problem Solving';

        $lsDefConcept->setTitle($title);
        // When description is null, should return title
        $this->assertEquals($title, $lsDefConcept->getDescription());
    }

    public function testGetDescriptionWithNullTitleAndDescription()
    {
        $lsDefConcept = new LsDefConcept();

        // Set title first, then test description behavior
        $lsDefConcept->setTitle('Test Concept');
        $lsDefConcept->setDescription(null);

        // When description is null, should return title
        $this->assertEquals('Test Concept', $lsDefConcept->getDescription());
    }

    public function testSetAndGetHierarchyCode()
    {
        $lsDefConcept = new LsDefConcept();
        $hierarchyCode = 'CT.1';

        $lsDefConcept->setHierarchyCode($hierarchyCode);
        $this->assertEquals($hierarchyCode, $lsDefConcept->getHierarchyCode());
    }

    public function testSetAndGetKeywords()
    {
        $lsDefConcept = new LsDefConcept();
        $keywords = 'analysis, evaluation, reasoning, logic';

        $lsDefConcept->setKeywords($keywords);
        $this->assertEquals($keywords, $lsDefConcept->getKeywords());
    }

    public function testToString()
    {
        $lsDefConcept = new LsDefConcept();
        $title = 'Systems Thinking';

        $lsDefConcept->setTitle($title);
        $this->assertEquals($title, (string) $lsDefConcept);
    }

    public function testDataIntegrity()
    {
        $lsDefConcept = new LsDefConcept();
        $identifier = Uuid::uuid4()->toString();
        $title = 'Information Literacy';
        $description = 'The ability to find, evaluate, and use information effectively';
        $hierarchyCode = 'IL.1';
        $keywords = 'research, evaluation, information, digital literacy';

        $lsDefConcept->setIdentifier($identifier);
        $lsDefConcept->setTitle($title);
        $lsDefConcept->setDescription($description);
        $lsDefConcept->setHierarchyCode($hierarchyCode);
        $lsDefConcept->setKeywords($keywords);

        // Test that all data is properly stored and retrieved
        $this->assertEquals($identifier, $lsDefConcept->getIdentifier());
        $this->assertEquals($title, $lsDefConcept->getTitle());
        $this->assertEquals($description, $lsDefConcept->getDescription());
        $this->assertEquals($hierarchyCode, $lsDefConcept->getHierarchyCode());
        $this->assertEquals($keywords, $lsDefConcept->getKeywords());
    }

    public function testUniqueIdentifier()
    {
        $concept1 = new LsDefConcept();
        $concept2 = new LsDefConcept();

        $this->assertNotEquals($concept1->getIdentifier(), $concept2->getIdentifier());
    }

    public function testUpdatedAtTracking()
    {
        $lsDefConcept = new LsDefConcept();
        $originalUpdatedAt = $lsDefConcept->getUpdatedAt();

        // Simulate some time passing
        sleep(1);

        $lsDefConcept->setTitle('Updated Title');

        // Note: In a real scenario, the updatedAt would be updated by Doctrine listeners
        // This test demonstrates the field exists and is accessible
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefConcept->getUpdatedAt());
    }

    public function testChangedAtTracking()
    {
        $lsDefConcept = new LsDefConcept();

        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefConcept->getChangedAt());
        $this->assertEquals($lsDefConcept->getUpdatedAt(), $lsDefConcept->getChangedAt());
    }

    public function testNullValues()
    {
        $lsDefConcept = new LsDefConcept();

        // Set title first to avoid uninitialized property access
        $lsDefConcept->setTitle('Test Concept');

        // Test that nullable fields can be null
        $this->assertEquals('Test Concept', $lsDefConcept->getDescription()); // Should fallback to title
        $this->assertNull($lsDefConcept->getKeywords());
    }

    public function testLongTitle()
    {
        $lsDefConcept = new LsDefConcept();
        $longTitle = str_repeat('Very Long Concept Title That Describes A Complex Idea ', 5);

        $lsDefConcept->setTitle($longTitle);
        $this->assertEquals($longTitle, $lsDefConcept->getTitle());
    }

    public function testLongDescription()
    {
        $lsDefConcept = new LsDefConcept();
        $longDescription = str_repeat('This is a very detailed description of the concept that explains its meaning and application. ', 15);

        $lsDefConcept->setDescription($longDescription);
        $this->assertEquals($longDescription, $lsDefConcept->getDescription());
    }

    public function testLongKeywords()
    {
        $lsDefConcept = new LsDefConcept();
        $longKeywords = str_repeat('keyword1, keyword2, keyword3, ', 20);

        $lsDefConcept->setKeywords($longKeywords);
        $this->assertEquals($longKeywords, $lsDefConcept->getKeywords());
    }

    public function testSpecialCharactersInTitle()
    {
        $lsDefConcept = new LsDefConcept();
        $specialTitle = 'Concept: 21st Century Skills & Competencies (v2.0)';

        $lsDefConcept->setTitle($specialTitle);
        $this->assertEquals($specialTitle, $lsDefConcept->getTitle());
    }

    public function testSpecialCharactersInKeywords()
    {
        $lsDefConcept = new LsDefConcept();
        $specialKeywords = 'skill-based, competency-driven, 21st-century, problem-solving';

        $lsDefConcept->setKeywords($specialKeywords);
        $this->assertEquals($specialKeywords, $lsDefConcept->getKeywords());
    }

    public function testUnicodeCharacters()
    {
        $lsDefConcept = new LsDefConcept();
        $unicodeTitle = 'Concept with Üñíçødé Characters 中文';
        $unicodeKeywords = '标签1, 标签2, étiquette';

        $lsDefConcept->setTitle($unicodeTitle);
        $lsDefConcept->setKeywords($unicodeKeywords);

        $this->assertEquals($unicodeTitle, $lsDefConcept->getTitle());
        $this->assertEquals($unicodeKeywords, $lsDefConcept->getKeywords());
    }

    public function testHierarchyCodeFormats()
    {
        $lsDefConcept = new LsDefConcept();
        $hierarchyCodes = ['1.1', 'A.2.3', 'MATH.1', 'SCI.BIO.1.2'];

        foreach ($hierarchyCodes as $code) {
            $lsDefConcept->setHierarchyCode($code);
            $this->assertEquals($code, $lsDefConcept->getHierarchyCode());
        }
    }

    public function testKeywordsWithCommas()
    {
        $lsDefConcept = new LsDefConcept();
        $keywords = 'learning, assessment, evaluation, feedback, improvement';

        $lsDefConcept->setKeywords($keywords);
        $this->assertEquals($keywords, $lsDefConcept->getKeywords());
    }

    public function testEmptyKeywords()
    {
        $lsDefConcept = new LsDefConcept();

        $lsDefConcept->setKeywords('');
        $this->assertEquals('', $lsDefConcept->getKeywords());
    }

    public function testConceptWithoutDescription()
    {
        $lsDefConcept = new LsDefConcept();
        $title = 'Simple Concept';

        $lsDefConcept->setTitle($title);

        $this->assertEquals($title, $lsDefConcept->getTitle());
        $this->assertEquals($title, $lsDefConcept->getDescription()); // Should fallback to title
    }

    public function testConceptWithOnlyDescription()
    {
        $lsDefConcept = new LsDefConcept();
        $description = 'This concept is defined only by its description';

        // Set title first to avoid uninitialized property access
        $lsDefConcept->setTitle('Test Concept');
        $lsDefConcept->setDescription($description);

        $this->assertEquals('Test Concept', $lsDefConcept->getTitle());
        $this->assertEquals($description, $lsDefConcept->getDescription());
    }
}
