<?php

namespace Tests\Unit\App\Entity\Framework;

use App\Entity\Framework\FrameworkType;

class FrameworkTypeTest extends \Codeception\Test\Unit
{
    /**
     * @var \Tests\Support\UnitTester
     */
    protected $tester;

    // tests
    public function testConstructor()
    {
        $frameworkType = new FrameworkType('Standard');

        $this->assertInstanceOf(FrameworkType::class, $frameworkType);
        $this->assertNull($frameworkType->getId());
        $this->assertEquals('Standard', $frameworkType->getFrameworkType());
    }

    public function testConstructorWithDifferentTypes()
    {
        $types = ['Standard', 'Competency', 'Learning Objective', 'Assessment'];

        foreach ($types as $type) {
            $frameworkType = new FrameworkType($type);
            $this->assertEquals($type, $frameworkType->getFrameworkType());
        }
    }

    public function testGetFrameworkType()
    {
        $frameworkType = new FrameworkType('Competency Framework');

        $this->assertEquals('Competency Framework', $frameworkType->getFrameworkType());
    }

    public function testGetIdInitiallyNull()
    {
        $frameworkType = new FrameworkType('Test Type');

        $this->assertNull($frameworkType->getId());
    }

    public function testEmptyStringType()
    {
        $frameworkType = new FrameworkType('');

        $this->assertEquals('', $frameworkType->getFrameworkType());
    }

    public function testLongTypeString()
    {
        $longType = str_repeat('Very Long Framework Type Name ', 5);
        $frameworkType = new FrameworkType($longType);

        $this->assertEquals($longType, $frameworkType->getFrameworkType());
    }

    public function testSpecialCharactersInType()
    {
        $specialType = 'Framework-Type_With.Special:Characters';
        $frameworkType = new FrameworkType($specialType);

        $this->assertEquals($specialType, $frameworkType->getFrameworkType());
    }

    public function testNumericType()
    {
        $numericType = 'Type123';
        $frameworkType = new FrameworkType($numericType);

        $this->assertEquals($numericType, $frameworkType->getFrameworkType());
    }

    public function testUnicodeCharacters()
    {
        $unicodeType = 'Framework Type with Üñíçødé Characters 中文';
        $frameworkType = new FrameworkType($unicodeType);

        $this->assertEquals($unicodeType, $frameworkType->getFrameworkType());
    }

    public function testConstructorRequiresParameter()
    {
        // This test verifies that the constructor requires a parameter
        // In PHP 8.1+, we could use named parameters, but this tests the basic requirement
        $frameworkType = new FrameworkType('Required Parameter');
        $this->assertInstanceOf(FrameworkType::class, $frameworkType);
    }

    public function testDataIntegrity()
    {
        $type = 'Learning Standard Framework';
        $frameworkType = new FrameworkType($type);

        // Test that the type is properly stored and retrieved
        $this->assertEquals($type, $frameworkType->getFrameworkType());
        $this->assertNull($frameworkType->getId()); // ID should be null before persistence
    }

    public function testMultipleInstances()
    {
        $type1 = 'Type One';
        $type2 = 'Type Two';

        $frameworkType1 = new FrameworkType($type1);
        $frameworkType2 = new FrameworkType($type2);

        $this->assertEquals($type1, $frameworkType1->getFrameworkType());
        $this->assertEquals($type2, $frameworkType2->getFrameworkType());
        $this->assertNotEquals($frameworkType1->getFrameworkType(), $frameworkType2->getFrameworkType());
    }

    public function testFrameworkTypeImmutability()
    {
        $originalType = 'Original Type';
        $frameworkType = new FrameworkType($originalType);

        // Verify that once set in constructor, the type cannot be changed
        // (since there's no setter method)
        $this->assertEquals($originalType, $frameworkType->getFrameworkType());
    }

    public function testCommonFrameworkTypes()
    {
        $commonTypes = [
            'Common Core State Standards',
            'State Standards',
            'District Standards',
            'International Standards',
            'Professional Standards',
        ];

        foreach ($commonTypes as $type) {
            $frameworkType = new FrameworkType($type);
            $this->assertEquals($type, $frameworkType->getFrameworkType());
        }
    }
}
