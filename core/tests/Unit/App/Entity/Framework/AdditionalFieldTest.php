<?php

namespace Tests\Unit\App\Entity\Framework;

use App\Entity\Framework\AdditionalField;

class AdditionalFieldTest extends \Codeception\Test\Unit
{
    /**
     * @var \Tests\Support\UnitTester
     */
    protected $tester;

    // tests
    public function testConstructor()
    {
        $additionalField = new AdditionalField();

        $this->assertInstanceOf(AdditionalField::class, $additionalField);
        $this->assertNull($additionalField->getId());
    }

    public function testGetTypes()
    {
        $types = AdditionalField::getTypes();

        $this->assertIsArray($types);
        $this->assertContains('string', $types);
    }

    public function testSetAndGetName()
    {
        $additionalField = new AdditionalField();
        $name = 'custom_field';

        $additionalField->setName($name);
        $this->assertEquals($name, $additionalField->getName());
    }

    public function testSetAndGetAppliesTo()
    {
        $additionalField = new AdditionalField();
        $appliesTo = 'LsDoc';

        $additionalField->setAppliesTo($appliesTo);
        $this->assertEquals($appliesTo, $additionalField->getAppliesTo());
    }

    public function testSetAndGetDisplayName()
    {
        $additionalField = new AdditionalField();
        $displayName = 'Custom Field';

        $additionalField->setDisplayName($displayName);
        $this->assertEquals($displayName, $additionalField->getDisplayName());
    }

    public function testSetAndGetType()
    {
        $additionalField = new AdditionalField();
        $type = 'string';

        $additionalField->setType($type);
        $this->assertEquals($type, $additionalField->getType());
    }

    public function testSetAndGetTypeInfo()
    {
        $additionalField = new AdditionalField();
        $typeInfo = ['maxLength' => 255, 'required' => true];

        $additionalField->setTypeInfo($typeInfo);
        $this->assertEquals($typeInfo, $additionalField->getTypeInfo());

        // Test with null
        $additionalField->setTypeInfo(null);
        $this->assertNull($additionalField->getTypeInfo());
    }

    public function testDataIntegrity()
    {
        $additionalField = new AdditionalField();

        $additionalField->setName('test_field');
        $additionalField->setAppliesTo('LsItem');
        $additionalField->setDisplayName('Test Field');
        $additionalField->setType('string');
        $additionalField->setTypeInfo(['maxLength' => 100]);

        $this->assertEquals('test_field', $additionalField->getName());
        $this->assertEquals('LsItem', $additionalField->getAppliesTo());
        $this->assertEquals('Test Field', $additionalField->getDisplayName());
        $this->assertEquals('string', $additionalField->getType());
        $this->assertEquals(['maxLength' => 100], $additionalField->getTypeInfo());
    }

    public function testValidType()
    {
        $additionalField = new AdditionalField();

        foreach (AdditionalField::getTypes() as $validType) {
            $additionalField->setType($validType);
            $this->assertEquals($validType, $additionalField->getType());
        }
    }

    public function testTypeInfoArrayHandling()
    {
        $additionalField = new AdditionalField();

        // Test with associative array
        $typeInfo = ['key1' => 'value1', 'key2' => 'value2'];
        $additionalField->setTypeInfo($typeInfo);
        $this->assertEquals($typeInfo, $additionalField->getTypeInfo());

        // Test with indexed array
        $typeInfo = ['option1', 'option2', 'option3'];
        $additionalField->setTypeInfo($typeInfo);
        $this->assertEquals($typeInfo, $additionalField->getTypeInfo());

        // Test with empty array
        $additionalField->setTypeInfo([]);
        $this->assertEquals([], $additionalField->getTypeInfo());
    }

    public function testNameValidationConstraints()
    {
        $additionalField = new AdditionalField();

        // Test valid names
        $validNames = ['field_name', 'a', 'field123', 'my_field_name'];
        foreach ($validNames as $name) {
            $additionalField->setName($name);
            $this->assertEquals($name, $additionalField->getName());
        }
    }

    public function testAppliesToValidation()
    {
        $additionalField = new AdditionalField();

        // Test various entity names that might apply
        $appliesToValues = ['LsDoc', 'LsItem', 'LsAssociation', 'User'];
        foreach ($appliesToValues as $value) {
            $additionalField->setAppliesTo($value);
            $this->assertEquals($value, $additionalField->getAppliesTo());
        }
    }

    public function testDisplayNameHandling()
    {
        $additionalField = new AdditionalField();

        // Test with various display names
        $displayNames = ['Field Name', 'Custom Field', 'My Field', 'Test 123'];
        foreach ($displayNames as $name) {
            $additionalField->setDisplayName($name);
            $this->assertEquals($name, $additionalField->getDisplayName());
        }
    }

    public function testDefaultValues()
    {
        $additionalField = new AdditionalField();

        $this->assertNull($additionalField->getId());
        $this->assertNull($additionalField->getName());
        $this->assertNull($additionalField->getAppliesTo());
        $this->assertNull($additionalField->getDisplayName());
        $this->assertNull($additionalField->getType());
        $this->assertNull($additionalField->getTypeInfo());
    }

    public function testTypeInfoSerialization()
    {
        $additionalField = new AdditionalField();

        // Test that complex typeInfo structures are preserved
        $complexTypeInfo = [
            'validation' => [
                'required' => true,
                'minLength' => 5,
                'maxLength' => 255
            ],
            'display' => [
                'placeholder' => 'Enter value',
                'helpText' => 'This is a help text'
            ]
        ];

        $additionalField->setTypeInfo($complexTypeInfo);
        $this->assertEquals($complexTypeInfo, $additionalField->getTypeInfo());
    }
}
