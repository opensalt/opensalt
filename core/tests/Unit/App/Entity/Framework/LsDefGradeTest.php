<?php

namespace Tests\Unit\App\Entity\Framework;

use App\Entity\Framework\LsDefGrade;
use Ramsey\Uuid\Uuid;

class LsDefGradeTest extends \Codeception\Test\Unit
{
    /**
     * @var \Tests\Support\UnitTester
     */
    protected $tester;

    // tests
    public function testConstructor()
    {
        $lsDefGrade = new LsDefGrade();

        $this->assertInstanceOf(LsDefGrade::class, $lsDefGrade);
        $this->assertNotNull($lsDefGrade->getIdentifier());
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefGrade->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefGrade->getChangedAt());
    }

    public function testConstructorWithIdentifier()
    {
        $identifier = Uuid::uuid4()->toString();
        $lsDefGrade = new LsDefGrade($identifier);

        $this->assertEquals($identifier, $lsDefGrade->getIdentifier());
    }

    public function testSetAndGetTitle()
    {
        $lsDefGrade = new LsDefGrade();
        $title = 'Grade 5';

        $lsDefGrade->setTitle($title);
        $this->assertEquals($title, $lsDefGrade->getTitle());
    }

    public function testSetAndGetDescription()
    {
        $lsDefGrade = new LsDefGrade();
        $description = 'Fifth grade level curriculum standards';

        $lsDefGrade->setDescription($description);
        $this->assertEquals($description, $lsDefGrade->getDescription());
    }

    public function testGetDescriptionFallbackToTitle()
    {
        $lsDefGrade = new LsDefGrade();
        $title = 'Kindergarten';

        $lsDefGrade->setTitle($title);
        // When description is null, should return title
        $this->assertEquals($title, $lsDefGrade->getDescription());
    }

    public function testGetDescriptionWithNullTitleAndDescription()
    {
        $lsDefGrade = new LsDefGrade();

        // When both title and description are null, should return null
        $this->assertNull($lsDefGrade->getDescription());
    }

    public function testSetAndGetCode()
    {
        $lsDefGrade = new LsDefGrade();
        $code = '05';

        $lsDefGrade->setCode($code);
        $this->assertEquals($code, $lsDefGrade->getCode()); // Ensure code is set
        $this->assertEquals($code, $lsDefGrade->getCode());
    }

    public function testSetAndGetRank()
    {
        $lsDefGrade = new LsDefGrade();
        $rank = 5;

        $lsDefGrade->setRank($rank);
        $this->assertEquals($rank, $lsDefGrade->getRank());
    }

    public function testGetLabel()
    {
        $lsDefGrade = new LsDefGrade();
        $code = '03';
        $title = 'Third Grade';

        $lsDefGrade->setCode($code);
        $this->assertEquals($code, $lsDefGrade->getCode()); // Ensure code is set
        $lsDefGrade->setTitle($title);
        $this->assertEquals($title, $lsDefGrade->getTitle());

        $expectedLabel = '03 - Third Grade';
        $this->assertEquals($expectedLabel, $lsDefGrade->getLabel());
    }

    public function testGetLabelWithNullTitle()
    {
        $lsDefGrade = new LsDefGrade();
        $code = '03';

        $lsDefGrade->setCode($code);
        $this->assertEquals($code, $lsDefGrade->getCode()); // Ensure code is set
        $this->assertEquals(null, $lsDefGrade->getTitle());

        $expectedLabel = '03 - ';
        $this->assertEquals($expectedLabel, $lsDefGrade->getLabel());
    }

    public function testGetLabelWithNullCode()
    {
        $lsDefGrade = new LsDefGrade();
        $title = 'Pre-Kindergarten';

        $lsDefGrade->setTitle($title);
        // Code is not set (will be empty string)

        $expectedLabel = ' - Pre-Kindergarten';
        $lsDefGrade->setCode(""); // Set empty code explicitly
        $this->assertEquals($expectedLabel, $lsDefGrade->getLabel());
    }

    public function testDataIntegrity()
    {
        $lsDefGrade = new LsDefGrade();
        $identifier = Uuid::uuid4()->toString();
        $title = 'High School Senior';
        $description = '12th grade level standards';
        $code = '12';
        $rank = 12;

        $lsDefGrade->setIdentifier($identifier);
        $lsDefGrade->setTitle($title);
        $lsDefGrade->setDescription($description);
        $lsDefGrade->setCode($code);
        $this->assertEquals($code, $lsDefGrade->getCode()); // Ensure code is set
        $lsDefGrade->setRank($rank);

        // Test that all data is properly stored and retrieved
        $this->assertEquals($identifier, $lsDefGrade->getIdentifier());
        $this->assertEquals($title, $lsDefGrade->getTitle());
        $this->assertEquals($description, $lsDefGrade->getDescription());
        $this->assertEquals($code, $lsDefGrade->getCode());
        $this->assertEquals($rank, $lsDefGrade->getRank());
        $this->assertEquals('12 - High School Senior', $lsDefGrade->getLabel());
    }

    public function testUniqueIdentifier()
    {
        $grade1 = new LsDefGrade();
        $grade2 = new LsDefGrade();

        $this->assertNotEquals($grade1->getIdentifier(), $grade2->getIdentifier());
    }

    public function testUpdatedAtTracking()
    {
        $lsDefGrade = new LsDefGrade();
        $originalUpdatedAt = $lsDefGrade->getUpdatedAt();

        // Simulate some time passing
        sleep(1);

        $lsDefGrade->setTitle('Updated Title');

        // Note: In a real scenario, the updatedAt would be updated by Doctrine listeners
        // This test demonstrates the field exists and is accessible
        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefGrade->getUpdatedAt());
    }

    public function testChangedAtTracking()
    {
        $lsDefGrade = new LsDefGrade();

        $this->assertInstanceOf(\DateTimeImmutable::class, $lsDefGrade->getChangedAt());
        $this->assertEquals($lsDefGrade->getUpdatedAt(), $lsDefGrade->getChangedAt());
    }

    public function testNullValues()
    {
        $lsDefGrade = new LsDefGrade();

        // Test that nullable fields can be null
        $this->assertNull($lsDefGrade->getTitle());
        $this->assertNull($lsDefGrade->getDescription());
        $lsDefGrade->setRank(5); // Set rank first
        $this->assertEquals(5, $lsDefGrade->getRank());
    }

    public function testGradeCodeFormats()
    {
        $lsDefGrade = new LsDefGrade();
        $gradeCodes = ['K', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', 'PK', 'KG'];

        foreach ($gradeCodes as $code) {
            $lsDefGrade->setCode($code);
        $this->assertEquals($code, $lsDefGrade->getCode()); // Ensure code is set
            $this->assertEquals($code, $lsDefGrade->getCode());
        }
    }

    public function testRankValues()
    {
        $lsDefGrade = new LsDefGrade();
        $ranks = [1, 2, 5, 10, 12, 0, -1];

        foreach ($ranks as $rank) {
            $lsDefGrade->setRank($rank);
            $this->assertEquals($rank, $lsDefGrade->getRank());
        }
    }

    public function testLongTitle()
    {
        $lsDefGrade = new LsDefGrade();
        $longTitle = str_repeat('Very Long Grade Level Title ', 5);

        $lsDefGrade->setTitle($longTitle);
        $this->assertEquals($longTitle, $lsDefGrade->getTitle());
    }

    public function testLongDescription()
    {
        $lsDefGrade = new LsDefGrade();
        $longDescription = str_repeat('This is a very detailed description of the grade level standards. ', 10);

        $lsDefGrade->setDescription($longDescription);
        $this->assertEquals($longDescription, $lsDefGrade->getDescription());
    }

    public function testSpecialCharactersInTitle()
    {
        $lsDefGrade = new LsDefGrade();
        $specialTitle = 'Grade 9-10 (High School)';

        $lsDefGrade->setTitle($specialTitle);
        $this->assertEquals($specialTitle, $lsDefGrade->getTitle());
    }

    public function testUnicodeCharacters()
    {
        $lsDefGrade = new LsDefGrade();
        $unicodeTitle = 'Grade 1 - Primer Año';
        $unicodeCode = '01';

        $lsDefGrade->setTitle($unicodeTitle);
        $lsDefGrade->setCode($unicodeCode);

        $this->assertEquals($unicodeTitle, $lsDefGrade->getTitle());
        $this->assertEquals($unicodeCode, $lsDefGrade->getCode());
        $this->assertEquals('01 - Grade 1 - Primer Año', $lsDefGrade->getLabel());
    }

    public function testGradeWithoutTitle()
    {
        $lsDefGrade = new LsDefGrade();
        $code = '06';

        $lsDefGrade->setCode($code);
        $this->assertEquals($code, $lsDefGrade->getCode()); // Ensure code is set

        $this->assertEquals($code, $lsDefGrade->getCode());
        $this->assertNull($lsDefGrade->getTitle());
        $this->assertEquals('06 - ', $lsDefGrade->getLabel());
    }

    public function testGradeWithoutCode()
    {
        $lsDefGrade = new LsDefGrade();
        $title = 'Middle School';

        $lsDefGrade->setTitle($title);

        $this->assertEquals($title, $lsDefGrade->getTitle());
        $this->assertEquals('', $lsDefGrade->getCode()); // Code is required, should be empty string
        $this->assertEquals(' - Middle School', $lsDefGrade->getLabel());
    }

    public function testNegativeRank()
    {
        $lsDefGrade = new LsDefGrade();
        $negativeRank = -1;

        $lsDefGrade->setRank($negativeRank);
        $this->assertEquals($negativeRank, $lsDefGrade->getRank());
    }

    public function testZeroRank()
    {
        $lsDefGrade = new LsDefGrade();
        $zeroRank = 0;

        $lsDefGrade->setRank($zeroRank);
        $this->assertEquals($zeroRank, $lsDefGrade->getRank());
    }

    public function testHighRank()
    {
        $lsDefGrade = new LsDefGrade();
        $highRank = 100;

        $lsDefGrade->setRank($highRank);
        $this->assertEquals($highRank, $lsDefGrade->getRank());
    }
}
